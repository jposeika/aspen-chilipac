<template>
	<Teleport to="body">
		<div class="chilipac-overlay" @click.self="close">
			<div class="chilipac-dialog">
				<div class="modal-header">
					<button type="button" class="close" @click.prevent="close">&times;</button>
					<h4 class="modal-title">{{ item.data.annotation ? 'Edit notes' : 'Add notes' }}</h4>
				</div>

				<form @submit.prevent="saveAnnotation">
					<div class="modal-body">
						<div class="form-group">
							<label for="item-notes">Notes</label>
							<textarea class="form-control" id="item-notes" rows="6" v-model="annotation"></textarea>
						</div>
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
import { ref, onMounted, onUnmounted } from 'vue';
import { useBooklistsStore } from '@/store/booklists';

const props = defineProps({
	booklistId: { type: [String, Number], required: true },
	item: { type: Object, required: true },
});
const emit = defineEmits(['close']);

const booklistsStore = useBooklistsStore();
const saving = ref(false);
const annotation = ref(props.item.data.annotation);

async function saveAnnotation() {
	saving.value = true;
	try {
		await booklistsStore.addItemAnnotation({
			booklistId: props.booklistId,
			itemId: props.item.id,
			annotation: annotation.value,
		});
		close();
	} catch (error) {
		console.error('Failed to save annotation', error);
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
}

.chilipac-dialog {
	background: #fff;
	border-radius: 4px;
	width: 100%;
	max-width: 500px;
	box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
}
</style>
