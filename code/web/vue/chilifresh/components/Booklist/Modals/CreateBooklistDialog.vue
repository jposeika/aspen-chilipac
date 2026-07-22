<template>
	<Teleport to="body">
		<div class="chilipac-overlay" @click.self="close">
			<div class="chilipac-dialog">
				<div class="modal-header">
					<button type="button" class="close" @click.prevent="close">&times;</button>
					<h4 class="modal-title">Create a New List</h4>
				</div>

				<form @submit.prevent="createBooklist">
					<div class="modal-body">
						<div class="form-group">
							<label for="chilipac-create-name">Name of your booklist</label>
							<input
								type="text"
								class="form-control"
								id="chilipac-create-name"
								v-model="form.name"
								required
							/>
						</div>
						<div class="form-group">
							<label for="chilipac-create-description">Description</label>
							<textarea
								class="form-control"
								id="chilipac-create-description"
								v-model="form.description"
							></textarea>
						</div>
					</div>

					<div class="modal-footer">
						<button type="button" class="btn btn-default" @click.prevent="close">Cancel</button>
						<button type="submit" class="btn btn-primary" :disabled="saving">Create</button>
					</div>
				</form>
			</div>
		</div>
	</Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useListsStore } from '@/store/lists';

const props = defineProps({
	source: String,
});
const emit = defineEmits(['close']);

const listsStore = useListsStore();
const saving = ref(false);
const form = ref({
	name: null,
	description: null,
	privacy: 'draft',
	event_src: props.source ?? null,
});

function close() {
	emit('close');
}

async function createBooklist() {
	saving.value = true;
	try {
		await listsStore.createBooklist(form.value);
		close();
	} catch (error) {
		console.error('Failed to create booklist', error);
	} finally {
		saving.value = false;
	}
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
