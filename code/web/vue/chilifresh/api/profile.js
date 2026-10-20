export default class ProfileApi {
	constructor(client) {
		this.client = client
	}

	get() {
		return this.client.get('profile')
	}

	activity(page = 1) {
		return this.client.get('profile/activity', {
			params: {
				page,
			}
		})
	}

	update(data) {
		return this.client.put('profile', data)
	}

	uploadAvatar(file) {
		const data = new FormData()
		data.append('avatar', file)
		return this.client.post('profile/avatar', data)
	}

	deleteAvatar() {
		return this.client.delete('profile/avatar')
	}
}
