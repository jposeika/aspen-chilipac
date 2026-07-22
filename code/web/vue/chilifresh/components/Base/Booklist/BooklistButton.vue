<script setup>
import { ref, computed } from 'vue';
import { useListsStore } from '@/store/lists';
import bus from '@/eventBus';

const props = defineProps({
	id: { type: String, required: true },
	source: String,
	bibData: Object,
});

const listsStore = useListsStore();
const saving = ref(false);

const hasMorePages = computed(() =>
	Math.ceil(listsStore.userListCount / 5) > listsStore.userListPage
);

const displayLabel = computed(() => {
	if (saving.value) return 'Saving..';
	const inLists = listsStore.isInBooklists(props.id);
	if (inLists.length) return 'In: ' + inLists[0].name;
	return 'Add to booklist';
});

const availableLists = computed(() => {
	const inLists = listsStore.isInBooklists(props.id);
	if (inLists.length === 0) return listsStore.userLists;
	return listsStore.userLists.filter(
		(list) => inLists.filter((c) => c.booklist_id === list.id).length === 0
	);
});

async function addToBooklist(list) {
	saving.value = true;
	await listsStore.addToBooklist({
		booklistId: list.id,
		booklistName: list.name,
		bibId: props.id,
		source: props.source,
	});
	saving.value = false;
}

async function removeFromBooklist(list) {
	saving.value = true;
	await listsStore.removeFromBooklist({
		booklistId: list.booklist_id,
		itemId: list.id,
	});
	saving.value = false;
}

function loadMore() {
	bus.emit('loadMoreBooklists');
}
</script>

<template>
	<slot
		:saving="saving"
		:display-label="displayLabel"
		:lists="listsStore.userLists"
		:available-lists="availableLists"
		:current-lists="listsStore.isInBooklists(id)"
		:add="addToBooklist"
		:remove="removeFromBooklist"
		:has-more-pages="hasMorePages"
		:load-more="loadMore"
	/>
</template>
