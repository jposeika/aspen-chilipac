import axios from "axios"
import Booklist from "./booklist"
import Bookshelf from "./bookshelf"

class ChilipacApi {
    get(uri, params) {
        return axios.get(uri, {
            params
        })
    }

    post(uri, data) {
        return axios.post(uri, data)
    }

    put(uri, data) {
        return axios.put(uri, data)
    }

    delete(uri) {
        return axios.delete(uri)
    }

    booklist(booklistId = null) {
        return new Booklist(this, booklistId)
    }

    bookshelf() {
        return new Bookshelf(this)
    }
}

export default new ChilipacApi()
