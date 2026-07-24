<template>
	<Teleport to="body">
		<div class="chilipac-overlay" @click.self="close">
			<div class="chilipac-dialog">
				<div class="modal-header">
					<button type="button" class="close" @click.prevent="close">&times;</button>
					<h4 class="modal-title">{{ title }}</h4>
				</div>
				<div class="modal-body">
					<p>{{ text }}</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" @click.prevent="close">Cancel</button>
					<button type="button" class="btn btn-primary" :disabled="working" @click.prevent="confirm">
						{{ buttonText }}
					</button>
				</div>
			</div>
		</div>
	</Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

defineProps({
	title: { type: String, default: 'Confirm' },
	text: { type: String, default: 'Are you sure?' },
	buttonText: { type: String, default: 'Confirm' },
});
const emit = defineEmits(['confirm', 'close']);

const working = ref(false);

function close() {
	emit('close');
}

function confirm() {
	working.value = true;
	emit('confirm');
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
