<script setup>
import { ref, computed } from 'vue';
import { useListsStore } from '@/store/lists';

const props = defineProps({
	id: { type: String, required: true },
	source: String,
	bibData: Object,
});

const listsStore = useListsStore();
const saving = ref(false);

const allShelves = [
	{ id: 'completed', name: 'Completed' },
	{ id: 'in_progress', name: 'In Progress' },
	{ id: 'for_later', name: 'For Later' },
];

const availableShelves = computed(() =>
	allShelves.filter((shelf) => shelf.id !== listsStore.isOnShelf(props.id))
);

const displayLabel = computed(() => {
	if (saving.value) return 'Saving..';
	const shelfId = listsStore.isOnShelf(props.id);
	if (shelfId) return 'In: ' + allShelves.find((shelf) => shelf.id === shelfId).name;
	return 'Add to bookshelf';
});

async function addToShelf(shelf) {
	saving.value = true;
	await listsStore.addToShelf({
		shelf,
		bibId: props.id,
		source: props.source,
	});
	saving.value = false;
}

async function remove() {
	saving.value = true;
	await listsStore.removeFromShelf({
		shelf: listsStore.isOnShelf(props.id),
		bibId: props.id,
	});
	saving.value = false;
}
</script>

<template>
	<slot
		:saving="saving"
		:display-label="displayLabel"
		:available-shelves="availableShelves"
		:is-in-shelf="listsStore.isOnShelf(id) !== false"
		:add="addToShelf"
		:remove="remove"
	/>
</template>
