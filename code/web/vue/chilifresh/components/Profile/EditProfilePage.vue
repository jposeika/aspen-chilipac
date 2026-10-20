<template>
	<div v-if="loaded" class="editProfile">
		<section class="editProfile__section">
			<h2 class="h3">Profile picture</h2>
			<div class="alert alert-danger" v-if="avatarError">{{ avatarError }}</div>
			<div class="alert alert-success" v-if="avatarMessage">{{ avatarMessage }}</div>
			<div class="editProfile__avatarRow">
				<span class="editProfile__avatar">
					<img v-if="avatar" :src="avatar" alt="Your profile picture" />
					<template v-else>{{ initials }}</template>
				</span>
				<div>
					<input
						ref="fileInput"
						type="file"
						id="profile-avatar"
						class="sr-only"
						accept="image/*"
						@change="uploadAvatar"
					/>
					<label for="profile-avatar" class="btn btn-default" :class="{ disabled: avatarSaving }">
						<i class="fas fa-upload" aria-hidden="true"></i> {{ avatar ? 'Change picture' : 'Upload picture' }}
					</label>
					<button
						v-if="avatar"
						type="button"
						class="btn btn-default"
						:disabled="avatarSaving"
						@click.prevent="showDeleteAvatar = true"
					>
						<i class="fas fa-trash" aria-hidden="true"></i> Remove picture
					</button>
				</div>
			</div>
		</section>

		<section class="editProfile__section">
			<h2 class="h3">Nickname</h2>
			<form @submit.prevent="saveNickname">
				<div class="alert alert-success" v-if="nicknameMessage">{{ nicknameMessage }}</div>
				<div class="form-group" :class="{ 'has-error': nicknameError }">
					<label for="profile-nickname">Nickname</label>
					<input
						type="text"
						class="form-control"
						id="profile-nickname"
						maxlength="255"
						required
						v-model.trim="nickname"
					/>
					<p class="help-block" v-if="nicknameError">{{ nicknameError }}</p>
					<p class="help-block" v-else>Shown next to your reviews and booklists.</p>
				</div>
				<button type="submit" class="btn btn-primary" :disabled="nicknameSaving || !nickname">Save</button>
			</form>
		</section>

		<ConfirmationDialog
			v-if="showDeleteAvatar"
			title="Remove picture"
			text="Are you sure you want to remove your profile picture?"
			button-text="Remove"
			@confirm="deleteAvatar"
			@close="showDeleteAvatar = false"
		/>
	</div>
	<div v-else-if="loadError" class="alert alert-danger">{{ loadError }}</div>
	<div v-else>Loading...</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '@/api/chilipac';
import ConfirmationDialog from '@/components/Modals/ConfirmationDialog.vue';

const loaded = ref(false);
const loadError = ref(null);
const nickname = ref('');
const avatar = ref(null);

const nicknameSaving = ref(false);
const nicknameError = ref(null);
const nicknameMessage = ref(null);

const fileInput = ref(null);
const avatarSaving = ref(false);
const avatarError = ref(null);
const avatarMessage = ref(null);
const showDeleteAvatar = ref(false);

const initials = computed(() => nickname.value
	.split(/\s+/)
	.filter(Boolean)
	.slice(0, 2)
	.map((part) => part[0].toUpperCase())
	.join(''));

// The API wraps resources in `data`; tolerate a bare object as well
function applyProfile(response) {
	const profile = response.data?.data ?? response.data ?? {};
	if (profile.nickname !== undefined) {
		nickname.value = profile.nickname ?? '';
	}
	avatar.value = profile.avatar || profile.image || null;
}

// Validation failures come back as { message, errors: { field: [..] } }
function errorMessage(e, field, fallback) {
	const data = e.response?.data;
	return data?.errors?.[field]?.[0] ?? data?.message ?? fallback;
}

async function saveNickname() {
	nicknameSaving.value = true;
	nicknameError.value = null;
	nicknameMessage.value = null;
	try {
		applyProfile(await api.profile().update({ nickname: nickname.value }));
		nicknameMessage.value = 'Your nickname has been saved.';
	} catch (e) {
		nicknameError.value = errorMessage(e, 'nickname', 'Sorry, your nickname could not be saved. Please try again later.');
	} finally {
		nicknameSaving.value = false;
	}
}

async function uploadAvatar(event) {
	const file = event.target.files[0];
	if (!file) {
		return;
	}
	avatarSaving.value = true;
	avatarError.value = null;
	avatarMessage.value = null;
	try {
		applyProfile(await api.profile().uploadAvatar(file));
		avatarMessage.value = 'Your profile picture has been updated.';
	} catch (e) {
		avatarError.value = errorMessage(e, 'avatar', 'Sorry, your picture could not be uploaded. Please try again later.');
	} finally {
		avatarSaving.value = false;
		// Let the same file be picked again after a failure
		fileInput.value.value = '';
	}
}

async function deleteAvatar() {
	showDeleteAvatar.value = false;
	avatarSaving.value = true;
	avatarError.value = null;
	avatarMessage.value = null;
	try {
		await api.profile().deleteAvatar();
		avatar.value = null;
		avatarMessage.value = 'Your profile picture has been removed.';
	} catch (e) {
		avatarError.value = errorMessage(e, 'avatar', 'Sorry, your picture could not be removed. Please try again later.');
	} finally {
		avatarSaving.value = false;
	}
}

onMounted(async () => {
	try {
		applyProfile(await api.profile().get());
		loaded.value = true;
	} catch (e) {
		loadError.value = 'Sorry, your profile could not be loaded. Please try again later.';
	}
});
</script>

<style scoped>
.editProfile__section {
	margin-bottom: 2em;
	max-width: 500px;
}

.editProfile__avatarRow {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	gap: 16px;
}

.editProfile__avatarRow .btn + .btn {
	margin-left: 6px;
}

.editProfile__avatar {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 96px;
	height: 96px;
	border-radius: 50%;
	overflow: hidden;
	background: #ddd;
	color: #555;
	font-size: 32px;
	font-weight: bold;
	flex-shrink: 0;
}

.editProfile__avatar img {
	width: 100%;
	height: 100%;
	object-fit: cover;
}
</style>
