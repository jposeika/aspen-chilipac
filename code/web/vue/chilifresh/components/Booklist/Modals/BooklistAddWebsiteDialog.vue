<template>
	<Teleport to="body">
		<div class="chilipac-overlay" @click.self="close">
			<div class="chilipac-dialog">
				<div class="modal-header">
					<button type="button" class="close" @click.prevent="close">&times;</button>
					<h4 class="modal-title">Add Website</h4>
				</div>

				<form @submit.prevent="addWebsite">
					<div class="modal-body">
						<div class="form-group">
							<label for="add-website-title">Title</label>
							<input type="text" class="form-control" id="add-website-title" required v-model="form.title" />
						</div>
						<div class="form-group">
							<label for="add-website-link">Link</label>
							<input type="text" class="form-control" id="add-website-link" required v-model="form.url" />
						</div>
					</div>

					<div class="modal-footer">
						<button type="button" class="btn btn-default" @click.prevent="close">Cancel</button>
						<button type="submit" class="btn btn-primary" :disabled="adding">Add Website</button>
					</div>
				</form>
			</div>
		</div>
	</Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useBooklistsStore } from '@/store/booklists';

const props = defineProps({
	booklist: { type: Object, required: true },
});
const emit = defineEmits(['close']);

const booklistsStore = useBooklistsStore();
const adding = ref(false);
const form = ref({ title: null, url: null });

async function addWebsite() {
	if (!form.value.title || !form.value.url) {
		return;
	}
	if (!form.value.url.startsWith('http://') && !form.value.url.startsWith('https://')) {
		form.value.url = 'http://' + form.value.url;
	}
	adding.value = true;
	try {
		await booklistsStore.addWebsiteToBooklist({ booklistId: props.booklist.id, data: form.value });
		close();
	} catch (error) {
		console.error('Failed to add website', error);
	} finally {
		adding.value = false;
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
}

.chilipac-dialog {
	background: #fff;
	border-radius: 4px;
	width: 100%;
	max-width: 500px;
	box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
}
</style>
