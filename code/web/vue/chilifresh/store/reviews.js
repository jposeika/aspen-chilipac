import { defineStore } from 'pinia';
import api from '@/api/chilipac';

function normalizeCounts(bibId, data) {
	const ratings = data.ratings || {};
	const fromRatings = Number(ratings.from_ratings_count || 0);
	const fromReviews = Number(ratings.from_reviews_count || 0);
	return {
		bibId,
		average: Number(ratings.total || 0),
		// Ratings given on their own plus those that came in with a review
		ratingCount: fromRatings + fromReviews,
		fromRatingsCount: fromRatings,
		fromReviewsCount: fromReviews,
		readerReviewCount: Number(data.reader_review_count || 0),
		staffReviewCount: Number(data.staff_review_count || 0),
		proReviewCount: Number(data.pro_review_count || 0),
		recommendationCount: Number(data.recommendation_count || 0),
	};
}

const emptyWorkRating = { rating: 0, reviewCount: 0, isbn: null, isbns: [] };

export const useReviewsStore = defineStore('reviews', {
	state: () => ({
		// Per grouped work, resolved from the batched ISBN lookup on search pages
		works: {},
		worksLoaded: false,
		// Per bib id, from the detailed single-title endpoint on record pages
		ratings: {},
		inFlight: {},
	}),

	getters: {
		// Null until the batch resolves, so widgets stay blank rather than flashing "unrated"
		workRating: (state) => (workId) => (state.worksLoaded ? (state.works[workId] ?? emptyWorkRating) : null),
		ratingFor: (state) => (bibId) => state.ratings[bibId] ?? null,
	},

	actions: {
		/**
		 * Resolve ratings for a page of results in one request.
		 * @param {Object} works Grouped work id => ordered list of that work's ISBNs.
		 */
		async loadWorkRatings(works) {
			const isbns = [...new Set(Object.values(works).flat().map(String))];
			if (!isbns.length) {
				this.worksLoaded = true;
				return;
			}
			try {
				const byIsbn = indexByIsbn(await api.reviews().getCountsForIsbns(isbns));
				Object.entries(works).forEach(([workId, workIsbns]) => {
					this.works[workId] = resolveWorkRating(workIsbns, byIsbn);
				});
			} catch (e) {
				console.error('Could not load ChiliFresh ratings', e);
			}
			this.worksLoaded = true;
		},

		/**
		 * Re-read one work's rating, for after the patron posts a review of it.
		 * @param {String} workId Grouped work id.
		 * @param {Array} isbns That work's ISBNs, primary first.
		 */
		async reloadWorkRating(workId, isbns) {
			if (!isbns.length) {
				return;
			}
			try {
				const byIsbn = indexByIsbn(await api.reviews().getCountsForIsbns(isbns.map(String)));
				this.works[workId] = resolveWorkRating(isbns, byIsbn);
			} catch (e) {
				console.error('Could not reload ChiliFresh rating', e);
			}
		},

		async loadRating(bibId, isbns = []) {
			if (this.ratings[bibId] !== undefined) {
				return this.ratings[bibId];
			}
			return this.fetchRating(bibId, isbns);
		},

		/**
		 * Re-read a title's counts after the patron posts their own review. The counts already
		 * held stay in place until the new ones land: blanking them first would unmount the
		 * rating widget, and with it the review dialog the patron just posted from.
		 */
		async reloadRating(bibId, isbns = []) {
			return this.fetchRating(bibId, isbns);
		},

		async fetchRating(bibId, isbns = []) {
			// The rating widget and the reviews panel can both ask for the same title at once
			if (this.inFlight[bibId]) {
				return this.inFlight[bibId];
			}
			const request = api.reviews().getCounts(bibId, isbns)
				.then((response) => {
					this.ratings[bibId] = normalizeCounts(bibId, response.data.data || {});
					return this.ratings[bibId];
				})
				.catch((e) => {
					console.error('Could not load ChiliFresh ratings', e);
					// Record the miss so the widget resolves instead of hanging on "loading",
					// but keep counts already on screen if this was a reload
					this.ratings[bibId] ??= normalizeCounts(bibId, {});
					return this.ratings[bibId];
				})
				.finally(() => {
					delete this.inFlight[bibId];
				});
			this.inFlight[bibId] = request;
			return request;
		},
	},
});

// The response keys ISBNs as numbers; Aspen holds them as strings
function indexByIsbn(response) {
	const byIsbn = {};
	(response.data.data || []).forEach((entry) => {
		byIsbn[String(entry.isbn)] = entry;
	});
	return byIsbn;
}

// Editions of the same work share a rating on the ChiliFresh side, so the first ISBN that
// carries one speaks for the work. ISBNs arrive primary-first.
function resolveWorkRating(workIsbns, byIsbn) {
	for (const isbn of workIsbns) {
		const entry = byIsbn[String(isbn)];
		if (entry && Number(entry.rating) > 0) {
			return {
				rating: Number(entry.rating),
				reviewCount: Number(entry.review_count || 0),
				isbn: String(isbn),
				isbns: workIsbns.map(String),
			};
		}
	}
	// Carries the ISBNs even with no rating to match, so a review can still be posted
	return { ...emptyWorkRating, isbns: workIsbns.map(String) };
}
