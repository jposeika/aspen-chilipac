import { defineStore } from 'pinia';
import api from '@/api/chilipac';

// Shared source of truth for the public booklist page (/Booklist/{id}). The
// sidebar (the author card and their other lists) and the main content area are
// mounted as separate Vue apps but share this store through the single pinia
// instance, so one request to the API populates both.
export const usePublicBooklistStore = defineStore('publicBooklist', {
	state: () => ({
		loading: false,
		loaded: false,
		booklist: null,
		// HTTP status of the failed request; null while the booklist loaded fine.
		// The API returns 404 when the booklist does not exist and 403 when it
		// exists but is not public.
		errorStatus: null,
		id: null,
		page: 1,
		count: null,
	}),

	getters: {
		items: (state) => (state.booklist ? state.booklist.items.data : []),
		pagination: (state) => (state.booklist ? state.booklist.items.meta.pagination : null),
		author: (state) => (state.booklist && state.booklist.author ? state.booklist.author.data : null),
		moreBooklists: (state) => (state.booklist && state.booklist.more_booklists ? state.booklist.more_booklists.data : []),
		covers: (state) => (state.booklist && state.booklist.covers ? state.booklist.covers : []),
		notFound: (state) => state.errorStatus === 404,
		notPublic: (state) => state.errorStatus === 403,
		currentPage() {
			return this.pagination ? this.pagination.current_page : this.page;
		},
		totalPages() {
			return this.pagination ? this.pagination.total_pages : 1;
		},
		totalItems() {
			return this.pagination ? this.pagination.total : 0;
		},
	},

	actions: {
		async load() {
			this.loading = true;
			this.errorStatus = null;
			try {
				const params = {};
				if (this.page > 1) {
					params.page = this.page;
				}
				if (this.count) {
					params.count = this.count;
				}
				const response = await api.booklist(this.id).getPublic(params);
				this.booklist = response.data.data;
			} catch (error) {
				this.booklist = null;
				// Requests that never reached the API (network/CORS) have no
				// response; report those as 0 so the page can tell them apart
				// from a genuine 404/403.
				this.errorStatus = error.response ? error.response.status : 0;
			} finally {
				this.loading = false;
				this.loaded = true;
			}
		},

		// Initial load / back-forward navigation: adopt the id and page from the
		// page props and URL, then fetch.
		init({ id, page, count }) {
			this.id = id;
			this.page = page || 1;
			this.count = count || null;
			return this.load();
		},

		async goToPage(page) {
			this.page = page;
			// Keep the browser URL in sync without reloading the page.
			const path = window.location.pathname;
			window.history.pushState({}, '', page > 1 ? `${path}?page=${page}` : path);
			await this.load();
		},
	},
});
