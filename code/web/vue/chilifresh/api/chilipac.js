import axios from 'axios'
import Booklist from './booklist'
import Bookshelf from './bookshelf'
import User from './user'

class ChilipacApi {
	constructor() {
		this.client = axios.create({
			baseURL: window.ChiliPAC.baseUrl,
			headers: {
				'Client-Key': window.ChiliPAC.key,
			},
		})

		this.client.interceptors.request.use((config) => {
			if (window.ChiliPAC.token) {
				config.headers['Authorization'] = `Bearer ${window.ChiliPAC.token}`;
			}
			return config;
		})
	}

	get(uri, params) {
		return this.client.get(uri, { params })
	}

	post(uri, data) {
		return this.client.post(uri, data)
	}

	put(uri, data) {
		return this.client.put(uri, data)
	}

	delete(uri) {
		return this.client.delete(uri)
	}

	booklist(booklistId = null) {
		return new Booklist(this.client, booklistId)
	}

	bookshelf() {
		return new Bookshelf(this.client)
	}

	user() {
		return new User(this.client)
	}
}

export default new ChilipacApi()
