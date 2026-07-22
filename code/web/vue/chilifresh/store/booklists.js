import { defineStore } from 'pinia';
import api from '@/api/chilipac';

export const useBooklistsStore = defineStore('booklists', {
	state: () => ({
		loaded: false,
		booklists: [],
		latestBooklists: [],
		booklist: {},
	}),

	getters: {
		// Note: state properties (booklists, latestBooklists, booklist) are accessed
		// directly on the store; Pinia getters must not share names with state.
		isLoaded: (state) => state.loaded,
	},

	actions: {
		async getBooklists() {
			const response = await api.booklist().getAll();
			this.loaded = true;
			this.booklists = response.data.data;
		},

		async getLatestBooklists() {
			const response = await api.booklist().getLatest();
			this.loaded = true;
			this.latestBooklists = response.data.data;
		},

		async getBooklist(booklistId) {
			const response = await api.booklist(booklistId).get();
			this.loaded = true;
			this.booklist = response.data.data;
		},

		async deleteBooklist(booklistId) {
			await api.booklist(booklistId).delete();
			this.booklists = this.booklists.filter((b) => b.id != booklistId);
		},

		async updateBooklist(booklist) {
			const response = await api.booklist(booklist.id).update(booklist);
			const data = response.data.data;
			this.booklists = this.booklists.map((b) => (b.id == data.id ? data : b));
			if (this.booklist.id == data.id) {
				const items = this.booklist.items;
				this.booklist = data;
				this.booklist.items = items;
			}
		},

		addNewBooklist(data) {
			this.booklists = [data, ...this.booklists];
		},

		async reorderItems({ booklistId, items }) {
			await api.booklist(booklistId).reorder(items.map((i) => i.id));
			this.booklist.items.data = items;
		},

		async deleteItems({ booklistId, ids }) {
			await api.booklist(booklistId).deleteItems(ids);
			this.booklist.items.data = this.booklist.items.data.filter((item) => !ids.includes(item.id));
		},

		async addItemAnnotation({ booklistId, itemId, annotation }) {
			await api.booklist(booklistId).addAnnotation(itemId, annotation);
			this.booklist.items.data.find((item) => item.id == itemId).data.annotation = annotation;
		},

		async addItemToBooklist({ booklistId, bibId, source }) {
			const response = await api.booklist(booklistId).addItem({ bib_id: bibId, event_src: source });
			const data = response.data.data;
			this.booklist.items.data = [data, ...this.booklist.items.data];
			if (this.booklist.privacy === 'draft' && this.booklist.items.data.length === 5) {
				this.booklist.privacy = 'public';
			}
			return data;
		},

		async addWebsiteToBooklist({ booklistId, data }) {
			const response = await api.booklist(booklistId).addWebsite(data);
			const item = response.data.data;
			this.booklist.items.data = [item, ...this.booklist.items.data];
			return item;
		},

		async addDbSourceToBooklist({ booklistId, data }) {
			const response = await api.booklist(booklistId).addDbSource(data);
			const item = response.data.data;
			this.booklist.items.data = [item, ...this.booklist.items.data];
			return item;
		},

		async removeItemFromBooklist({ booklistId, itemId }) {
			await api.booklist(booklistId).removeItem(itemId);
			this.booklist.items.data = this.booklist.items.data.filter((item) => !([itemId].includes(item.id)));
		},
	},
});
