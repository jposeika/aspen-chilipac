export default class BookshelfApi {
    constructor(client) {
        this.client = client
    }

    get(shelf, sort = "date", page = 1, query = null) {
        return this.client.get(`bookshelf/${shelf}/items`, {
            sort,
            page,
            s: query,
        })
    }

    addItem(shelf, bibId, source) {
        return this.client.post(`bookshelf/${shelf}/items`, {
            bib_id: bibId,
            event_src: source,
        })
    }

    removeItem(shelf, bibId) {
        return this.client.delete(`bookshelf/${shelf}/items/${bibId}`)
    }

    searchBibs(query, page) {
        return this.client.get(`bookshelf/bibs`, {
            query,
            page,
        })
    }

    setPrivacy(isPublic) {
        return this.client.put(`bookshelf/privacy`, {
            public: isPublic,
        })
    }

    bulkMove(shelf, bibs) {
        return this.client.put(`bookshelf/${shelf}/move`, {
            bibs,
        })
    }

    bulkDelete(shelf, bibs) {
        return this.client.put(`bookshelf/${shelf}/delete`, {
            bibs,
        })
    }
}
