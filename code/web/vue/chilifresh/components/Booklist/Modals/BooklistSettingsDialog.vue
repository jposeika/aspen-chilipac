<template>
	<Teleport to="body">
		<div class="chilipac-overlay" @click.self="close">
			<div class="chilipac-dialog">
				<div class="modal-header">
					<button type="button" class="close" @click.prevent="close">&times;</button>
					<h4 class="modal-title">Edit booklist</h4>
				</div>

				<form @submit.prevent="updateBooklist">
					<div class="modal-body">
						<div class="form-group">
							<label for="settings-name">Name of your booklist</label>
							<input type="text" class="form-control" id="settings-name" placeholder="Name" required v-model="form.name" />
						</div>

						<template v-if="isStaff">
							<div class="form-group">
								<label for="settings-slug">Custom URL</label>
								<input type="text" class="form-control" id="settings-slug" placeholder="Custom URL, e.g. easy-dinners" maxlength="255" v-model="form.slug" />
							</div>
							<div class="form-group">
								<a href="#" @click.prevent="generateSlug">Generate from name</a>
							</div>
						</template>

						<div class="form-group">
							<label for="settings-description">Description</label>
							<textarea class="form-control" id="settings-description" placeholder="Description" v-model="form.description"></textarea>
						</div>

						<fieldset class="form-group" v-if="types.length">
							<legend class="h5">Type</legend>
							<label class="checkbox-inline" v-for="type in types" :key="type.id">
								<input type="checkbox" :value="type.id" v-model="form.type" /> {{ type.name }}
							</label>
						</fieldset>

						<fieldset class="form-group">
							<legend class="h5">Target audience</legend>
							<label class="checkbox-inline"><input type="checkbox" value="children" v-model="form.audience" /> Children</label>
							<label class="checkbox-inline"><input type="checkbox" value="young_adult" v-model="form.audience" /> Young adult</label>
							<label class="checkbox-inline"><input type="checkbox" value="adult" v-model="form.audience" /> Adult</label>
						</fieldset>

						<div class="form-group">
							<label for="settings-link">Link</label>
							<input type="text" class="form-control" id="settings-link" readonly :value="publicUrl" :disabled="form.privacy == 'draft'" />
						</div>

						<BooklistPrivacySwitch v-model="form.privacy" :item-count="itemCount" v-slot="{ changeState, canOnlyBeDraft }">
							<fieldset class="form-group">
								<legend class="h5">Privacy</legend>
								<div class="btn-group btn-group-justified privacy-switch" role="group" aria-label="Privacy">
									<div class="btn-group" role="group">
										<button type="button" class="btn btn-default privacy-draft" :class="{ active: form.privacy == 'draft' }" @click.prevent="changeState('draft')">Draft</button>
									</div>
									<div class="btn-group" role="group">
										<button type="button" class="btn btn-default privacy-private" :class="{ active: form.privacy == 'private' }" :disabled="canOnlyBeDraft()" @click.prevent="changeState('private')">Private</button>
									</div>
									<div class="btn-group" role="group">
										<button type="button" class="btn btn-default privacy-public" :class="{ active: form.privacy == 'public' }" :disabled="canOnlyBeDraft()" @click.prevent="changeState('public')">Public</button>
									</div>
								</div>
								<p class="help-block" v-if="canOnlyBeDraft()">A booklist needs at least 3 items before it can be made public or private.</p>
							</fieldset>
						</BooklistPrivacySwitch>
					</div>

					<div class="modal-footer">
						<button type="button" class="btn btn-default" @click.prevent="close">Cancel</button>
						<button type="submit" class="btn btn-primary" :disabled="saving">Save</button>
					</div>
				</form>
			</div>
		</div>
	</Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useBooklistsStore } from '@/store/booklists';
import BooklistPrivacySwitch from '../../Base/Booklist/BooklistPrivacySwitch.vue';

const props = defineProps({
	booklist: { type: Object, required: true },
});
const emit = defineEmits(['close']);

const booklistsStore = useBooklistsStore();
const saving = ref(false);
const isStaff = !!window.ChiliPAC?.staff;
const types = window.ChiliPAC?.booklistTypes ?? [];

const form = ref({
	slug: props.booklist.slug ?? null,
	type: props.booklist.type ?? [],
	name: props.booklist.name ?? null,
	description: props.booklist.description ?? null,
	audience: props.booklist.audience ?? [],
	privacy: props.booklist.privacy ?? 'draft',
});

const itemCount = computed(() => {
	if (props.booklist.item_count) {
		return props.booklist.item_count;
	}
	return props.booklist.items ? props.booklist.items.data.length : 0;
});

const publicUrl = computed(() => {
	const base = window.location.protocol + '//' + window.location.hostname + '/booklist/';
	return base + (props.booklist.slug ? props.booklist.slug : props.booklist.id);
});

function generateSlug() {
	form.value.slug = (form.value.name ?? '')
		.toString()
		.toLowerCase()
		.trim()
		.replace(/[*+~.()'"%#$`=;|?<>,!:@]/g, '')
		.replace(/\s+/g, '-')
		.replace(/-+/g, '-');
}

async function updateBooklist() {
	saving.value = true;
	try {
		await booklistsStore.updateBooklist({ id: props.booklist.id, ...form.value });
		close();
	} catch (error) {
		console.error('Failed to update booklist', error);
	} finally {
		saving.value = false;
	}
}

function close() {
	emit('close');
}

onMounted(() => { document.body.style.overflow = 'hidden'; });
onUnmounted(() => { document.body.style.overflow = ''; });
</script>

<style scoped>
.chilipac-overlay {
	position: fixed;
	inset: 0;
	background: rgba(0, 0, 0, 0.5);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 1050;
	overflow-y: auto;
	padding: 20px 0;
}

.chilipac-dialog {
	background: #fff;
	border-radius: 4px;
	width: 100%;
	max-width: 500px;
	box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
}

.privacy-switch .btn:focus,
.privacy-switch .btn:active:focus,
.privacy-switch .btn.active:focus {
	outline: none !important;
	box-shadow: none !important;
}

.privacy-switch .privacy-draft.active,
.privacy-switch .privacy-draft.active:hover,
.privacy-switch .privacy-draft.active:focus,
.privacy-switch .privacy-draft.active:active {
	background-color: #777 !important;
	border-color: #6f6f6f !important;
	color: #fff !important;
}

.privacy-switch .privacy-private.active,
.privacy-switch .privacy-private.active:hover,
.privacy-switch .privacy-private.active:focus,
.privacy-switch .privacy-private.active:active {
	background-color: #f0ad4e !important;
	border-color: #eea236 !important;
	color: #fff !important;
}

.privacy-switch .privacy-public.active,
.privacy-switch .privacy-public.active:hover,
.privacy-switch .privacy-public.active:focus,
.privacy-switch .privacy-public.active:active {
	background-color: #5cb85c !important;
	border-color: #4cae4c !important;
	color: #fff !important;
}
</style>
