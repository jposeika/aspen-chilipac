export default class UserApi {
	constructor(client) {
		this.client = client
	}

	connections(params) {
		return this.client.get('user/connections', { params })
	}
}
