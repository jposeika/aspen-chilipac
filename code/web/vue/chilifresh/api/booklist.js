import queryString from 'query-string'

export default class BooklistApi {
	constructor(client, booklistId = null) {
		this.client = client
		this.booklistId = booklistId
	}

	search(params) {
		return this.client.get('booklist/search', {
			params,
			// Serialize arrays as type[0]=.. to match the filter/URL format.
			paramsSerializer: (p) => queryString.stringify(p, { arrayFormat: 'index' }),
		})
	}

	connections(params) {
		return this.client.get('booklist/connections', { params })
	}

	searchUsers(params) {
		return this.client.get('booklist/users/search', {
			params,
			paramsSerializer: (p) => queryString.stringify(p, { arrayFormat: 'index' }),
		})
	}

	get() {
		return this.client.get(`booklist/${this.booklistId}`)
	}

	// Public (unauthenticated) view of a booklist. Returns 404 when the booklist
	// does not exist and 403 when it exists but is not public.
	getPublic(params) {
		return this.client.get(`public/booklist/${this.booklistId}`, { params })
	}

	create(data) {
		return this.client.post("booklist", data)
	}

	delete() {
		return this.client.delete(`booklist/${this.booklistId}`)
	}

	update(data) {
		return this.client.put(`booklist/${this.booklistId}`, data)
	}

	addItem(data) {
		return this.client.post(`booklist/${this.booklistId}/items`, data)
	}

	addWebsite(data) {
		return this.client.post(`booklist/${this.booklistId}/items`, {
			type: "website",
			...data
		})
	}

	addDbSource(data) {
		return this.client.post(`booklist/${this.booklistId}/items`, {
			type: "dbsource",
			...data
		})
	}

	removeItem(itemId) {
		return this.client.delete(`booklist/${this.booklistId}/items/${itemId}`)
	}

	getAll() {
		return this.client.get("booklist")
	}

	getLatest(user = null) {
		if (user === null) {
			return this.client.get("booklist/latest")
		} else {
			return this.client.get(`user/${user}/booklist/latest`)
		}
	}

	reorder(ids) {
		return this.client.put(`booklist/${this.booklistId}/reorder`, {
			order: ids
		})
	}

	deleteItems(ids) {
		return this.client.put(`booklist/${this.booklistId}/items/delete`, {
			items: ids
		})
	}

	addAnnotation(itemId, annotation) {
		return this.client.put(
			`booklist/${this.booklistId}/items/${itemId}/annotation`, {
				annotation
			}
		)
	}

	searchBibs(query, page) {
		return this.client.get(`booklist/${this.booklistId}/bibs`, {
			params: {
				query,
				page
			}
		})
	}

	cloneBooklist(data) {
		return this.client.put(`public/booklist/${this.booklistId}/copy`, {
			...data,
		})
	}
}
