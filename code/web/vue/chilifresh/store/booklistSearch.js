import { defineStore } from 'pinia';
import api from '@/api/chilipac';
import queryString from 'query-string';

// Shared source of truth for the booklist search page. The filter component (in
// the sidebar) and the results component (in the main content area) are mounted
// as separate Vue apps but share this store through the single pinia instance,
// so filter changes drive the results and both stay in sync with the URL.
export const useBooklistSearchStore = defineStore('booklistSearch', {
	state: () => ({
		loading: false,
		loaded: false,
		results: [],
		pagination: null,
		years: [],
		params: {},
		page: 1,
		count: 9,
		// Incremented whenever a brand new search term is submitted, so the sidebar
		// filter can reset its controls in response.
		resetToken: 0,
	}),

	getters: {
		currentPage: (state) => (state.pagination ? state.pagination.current_page : state.page),
		totalPages: (state) => (state.pagination ? state.pagination.total_pages : 1),
	},

	actions: {
		setCount(count) {
			if (count) {
				this.count = count;
			}
		},

		// Keep the browser URL in sync without reloading the page.
		updateUrl() {
			const query = { ...this.params };
			if (this.page > 1) {
				query.page = this.page;
			}
			const qs = queryString.stringify(query, { arrayFormat: 'index' });
			window.history.pushState({}, '', qs ? `?${qs}` : window.location.pathname);
		},

		async search() {
			this.loading = true;
			try {
				const response = await api.booklist().search({
					...this.params,
					count: this.count,
					page: this.page,
				});
				const meta = response.data.meta;
				this.results = response.data.data;
				this.pagination = meta ? meta.pagination : null;
				this.years = meta && meta.years ? meta.years : [];
			} finally {
				this.loading = false;
				this.loaded = true;
			}
		},

		// Initial load / back-forward navigation: adopt params from the URL and fetch.
		async initSearch(params, page = 1) {
			this.params = { ...params };
			this.page = page;
			await this.search();
		},

		// Filter changed: merge over the current params (so the search query set by
		// the results form is preserved), reset to the first page, push the URL, and fetch.
		async applyFilters(params) {
			this.params = { ...this.params, ...params };
			this.page = 1;
			this.updateUrl();
			await this.search();
		},

		// Search form submitted: start a fresh search with only the query text,
		// clearing any sidebar filter selections, reset to the first page, push the
		// URL, and fetch. The reset token tells the filter to reset its controls.
		async applyQuery(s) {
			this.params = { s };
			this.page = 1;
			this.resetToken++;
			this.updateUrl();
			await this.search();
		},

		async goToPage(page) {
			this.page = page;
			this.updateUrl();
			await this.search();
		},
	},
});
