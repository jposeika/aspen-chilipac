<script setup>
import { onMounted } from 'vue';

const props = defineProps({
	modelValue: { required: true },
	itemCount: { type: Number, required: true },
	minimumItemCount: { type: Number, default: 3 },
});
const emit = defineEmits(['update:modelValue']);

function canOnlyBeDraft() {
	return props.itemCount < props.minimumItemCount;
}

function isValidState(state) {
	return ['draft', 'private', 'public'].includes(state);
}

function changeState(state) {
	if (props.modelValue === state) {
		return;
	}
	if (!isValidState(state)) {
		return;
	}
	if (['private', 'public'].includes(state) && canOnlyBeDraft()) {
		return;
	}
	emit('update:modelValue', state);
}

onMounted(() => {
	if (canOnlyBeDraft() || !isValidState(props.modelValue)) {
		changeState('draft');
	}
});
</script>

<template>
	<slot :can-only-be-draft="canOnlyBeDraft" :change-state="changeState" />
</template>
