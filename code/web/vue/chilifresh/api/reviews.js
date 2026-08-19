export default class ReviewsApi {
	constructor(client) {
		this.client = client
	}

	// Review and rating counts for one title. Aspen groups several records into one work, so the
	// ISBNs of the other editions are passed along to fold their ratings into the same total.
	getCounts(bibId, isbns = []) {
		return this.client.get(`reviews/${bibId}/count`, {
			params: isbns.length ? { isbn: isbns } : {},
		})
	}

	// A page of reviews of one kind: 'reader', 'staff' or 'pro'. Like the count endpoints, this
	// matches on the ISBNs rather than the bib id alone, so the whole grouped work is covered.
	getReviews(bibId, kind, { page = 1, sort = 'newest', isbns = [] } = {}) {
		return this.client.get(`reviews/${bibId}/${kind}`, {
			params: isbns.length ? { page, sort, isbn: isbns } : { page, sort },
		})
	}

	// Ratings for many ISBNs at once, so a page of search results costs a single request.
	// The response is keyed by ISBN: [{ isbn, review_count, rating }, ...]
	getCountsForIsbns(isbns) {
		return this.client.get(`reviews/count`, {
			params: { isbn: isbns },
		})
	}
}
