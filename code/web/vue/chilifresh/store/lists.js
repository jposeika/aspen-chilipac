import { defineStore } from 'pinia';
import api from '@/api/chilipac';
import { useBooklistsStore } from './booklists';

export const useListsStore = defineStore('lists', {
	state: () => ({
		loaded: false,
		user_booklists: [],
		user_booklist_count: 0,
		user_booklists_page: 1,
		bibs_on_booklists: [],
		bibs_on_bookshelves: [],
	}),

	getters: {
		isLoaded: (state) => state.loaded,
		isOnShelf: (state) => (bibId) => {
			const item = state.bibs_on_bookshelves.find((item) => item.bib_id == bibId);
			return item !== undefined ? item.bookshelf : false;
		},
		userLists: (state) => state.user_booklists,
		isInBooklists: (state) => (bibId) => {
			const items = state.bibs_on_booklists.filter((item) => item.bib_id == bibId);
			return items !== undefined ? items : [];
		},
		userListCount: (state) => state.user_booklist_count,
		userListPage: (state) => state.user_booklists_page,
	},

	actions: {
		async loadBibs(bibs) {
			const response = await api.get('bibs/check', { bib: bibs });
			const data = response.data.data;
			this.loaded = true;
			this.user_booklists = data.user_booklists.data;
			this.bibs_on_booklists = data.booklists.data;
			this.bibs_on_bookshelves = data.bookshelves.data;
			this.user_booklist_count = response.data.meta.booklist_count;
		},

		async loadMoreBooklists({ bibs }) {
			this.user_booklists_page++;
			const response = await api.get('bibs/check', {
				bib: bibs,
				page: this.user_booklists_page,
			});
			this.user_booklist_count = response.data.meta.booklist_count;
			this.user_booklists = [...this.user_booklists, ...response.data.data.user_booklists.data];
		},

		async addToShelf({ shelf, bibId, source }) {
			await api.bookshelf().addItem(shelf, bibId, source);
			const item = this.bibs_on_bookshelves.find((item) => item.bib_id == bibId);
			if (item !== undefined) {
				item.bookshelf = shelf;
			} else {
				this.bibs_on_bookshelves.push({ bib_id: parseInt(bibId, 10), bookshelf: shelf });
			}
		},

		async removeFromShelf({ shelf, bibId }) {
			await api.bookshelf().removeItem(shelf, bibId);
			this.bibs_on_bookshelves = this.bibs_on_bookshelves.filter((item) => item.bib_id != bibId);
		},

		async addToBooklist({ booklistId, booklistName, bibId, source }) {
			const response = await api.booklist(booklistId).addItem({ bib_id: bibId, event_src: source });
			this.bibs_on_booklists = [{
				bib_id: bibId,
				booklist_id: booklistId,
				id: response.data.data.id,
				name: booklistName,
			}, ...this.bibs_on_booklists];
		},

		async removeFromBooklist({ booklistId, itemId }) {
			await api.booklist(booklistId).removeItem(itemId);
			this.bibs_on_booklists = this.bibs_on_booklists.filter(
				(item) => !(item.booklist_id == booklistId && item.id == itemId),
			);
		},

		async createBooklist(data) {
			const response = await api.booklist().create(data);
			const { id, name } = response.data.data;
			this.user_booklists = [{ id, name }, ...this.user_booklists];
			useBooklistsStore().addNewBooklist(response.data.data);
			return response.data;
		},
	},
});
