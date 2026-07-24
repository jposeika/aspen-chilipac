<template>
	<div v-if="booklistsStore.isLoaded && booklist">
		<div class="titleBlock" style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 1em;">
			<h1 style="margin: 0; margin-right: auto;">{{ booklist.name }}</h1>
			<button class="btn btn-default" @click.prevent="showSettings = true">
				<i class="fas fa-edit" aria-hidden="true"></i> Edit
			</button>
			<a class="btn btn-default" :href="`/booklist/${booklist.id}/export/pdf`">
				<i class="fas fa-download" aria-hidden="true"></i> Download PDF
			</a>
		</div>

		<p v-if="booklist.description">{{ booklist.description }}</p>

		<div style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 1em;">
			<button class="btn btn-default" :disabled="savingOrder" @click.prevent="showAddItem = true">
				<i class="fas fa-plus" aria-hidden="true"></i> Add Item
			</button>
			<button class="btn btn-default" :disabled="savingOrder" @click.prevent="showAddWebsite = true">
				<i class="fas fa-plus" aria-hidden="true"></i> Add Website
			</button>
			<button class="btn btn-default" :disabled="selectedItems.length == 0 || savingOrder" @click.prevent="showDeleteItems = true">
				<i class="fas fa-trash" aria-hidden="true"></i> Delete Selected
			</button>
			<div v-if="booklist.privacy !== 'draft'" class="chilipac-share">
				<span class="chilipac-share__addon">Share list</span>
				<input
					type="text"
					class="form-control"
					ref="permalink"
					readonly
					:value="publicUrl"
					@focus="$event.target.select()"
				/>
				<button type="button" class="btn btn-default" aria-label="Copy link" @click.prevent="copyLinkToClipboard">
					<i class="fas fa-copy" aria-hidden="true"></i>
				</button>
			</div>
			<p v-else class="text-muted" style="margin: 0;">
				Make this booklist private or public to share it.
			</p>
		</div>

		<hr>

		<div class="actionWings" style="display: flex; justify-content: space-between; align-items: center;">
			<h2>{{ displayItems.length }} items</h2>
			<div class="form-inline">
				<label for="booklist-sort">Order by:</label>
				<select id="booklist-sort" class="form-control" v-model="sortby">
					<option value="default">Default</option>
					<option value="date">Date added</option>
					<option value="title:asc">Title A-Z</option>
					<option value="title:desc">Title Z-A</option>
					<option value="author:asc">Author A-Z</option>
					<option value="author:desc">Author Z-A</option>
				</select>
			</div>
		</div>

		<div class="listGrid">
			<div
				class="listCard"
				v-for="(item, index) in displayItems"
				:key="item.id"
				:data-index="index"
				:draggable="sortby === 'default'"
				:class="{ 'is-dragging': dragIndex === index }"
				@dragstart="onDragStart(index)"
				@dragover.prevent="onDragOver(index)"
				@dragend="onDragEnd"
			>
				<div class="listCard__checkbox">
					<input type="checkbox" :id="`listItem${item.id}`" :value="item.id" v-model="selectedItems" />
				</div>
				<a :href="item.data.url" class="listCard__image" tabindex="-1">
					<img :src="coverUrl(item)" alt="" />
				</a>
				<div class="listCard__content">
					<a :href="item.data.url" :target="item.type == 'item' ? '_self' : '_blank'" class="listCard__title">
						{{ item.data.title }}
					</a>
					<div class="listCard__info">{{ item.data.author }}</div>
					<div class="listCard__rating" v-if="item.type == 'item'">
						<a :href="`${item.data.url}#reviews`">{{ reviewsLinkText(item) }}</a>
					</div>
					<div v-if="item.data.annotation" class="annotation">
						{{ item.data.annotation }}
					</div>
					<!-- TODO: item annotation (add/edit notes) dialog not yet converted -->
				</div>
				<div class="listCard__actions" v-if="sortby === 'default'">
					<span
						class="sortable-handle"
						title="Drag to reorder"
						@touchstart.prevent="onTouchStart(index)"
						@touchmove.prevent="onTouchMove"
						@touchend="onDragEnd"
						@touchcancel="onDragEnd"
					>
						<i class="fas fa-arrows-alt-v" aria-hidden="true"></i>
					</span>
				</div>
			</div>
		</div>

		<BooklistSettingsDialog
			v-if="showSettings"
			:booklist="booklist"
			@close="showSettings = false"
		/>
		<BooklistAddItemDialog
			v-if="showAddItem"
			:booklist="booklist"
			:record-source="recordSource"
			@close="showAddItem = false"
		/>
		<BooklistAddWebsiteDialog
			v-if="showAddWebsite"
			:booklist="booklist"
			@close="showAddWebsite = false"
		/>
		<ConfirmationDialog
			v-if="showDeleteItems"
			title="Delete items"
			:text="deleteItemsConfirmationText"
			button-text="Delete"
			@confirm="confirmDeleteSelected"
			@close="showDeleteItems = false"
		/>
	</div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useBooklistsStore } from '@/store/booklists';
import BooklistSettingsDialog from './Modals/BooklistSettingsDialog.vue';
import BooklistAddItemDialog from './Modals/BooklistAddItemDialog.vue';
import BooklistAddWebsiteDialog from './Modals/BooklistAddWebsiteDialog.vue';
import ConfirmationDialog from '@/components/Modals/ConfirmationDialog.vue';

const props = defineProps({
	id: { type: String, required: true },
	recordSource: { type: String, default: 'ils' },
});

const booklistsStore = useBooklistsStore();
const sortby = ref('default');
const selectedItems = ref([]);
const savingOrder = ref(false);
const showSettings = ref(false);
const showAddItem = ref(false);
const showAddWebsite = ref(false);
const showDeleteItems = ref(false);
const permalink = ref(null);
const dragIndex = ref(null);

const booklist = computed(() => booklistsStore.booklist);

// The breadcrumb is rendered server side as a generic "Booklist" label since the
// name is only known once this component fetches it. Update it here (and whenever
// the name changes, e.g. after editing) rather than making a second backend call.
watch(() => booklist.value?.name, (name) => {
	if (!name) {
		return;
	}
	const crumb = document.querySelector('.breadcrumbs .breadcrumb li:last-child a');
	if (crumb) {
		crumb.textContent = name;
	}
});

// The items in the currently selected sort order. Dragging is only allowed in
// the default order, in which case reordering is persisted.
const sortedItems = computed(() => {
	const items = booklist.value?.items?.data;
	if (!items) {
		return [];
	}
	if (sortby.value === 'default') {
		return items;
	}
	if (sortby.value === 'date') {
		return items.toSorted((a, b) => a.data.added_ts - b.data.added_ts);
	}
	const [field, order] = sortby.value.split(':');
	return items.toSorted((a, b) => {
		const left = (a.data[field] ?? '').toUpperCase();
		const right = (b.data[field] ?? '').toUpperCase();
		if (left < right) {
			return order === 'asc' ? -1 : 1;
		}
		if (left > right) {
			return order === 'asc' ? 1 : -1;
		}
		return 0;
	});
});

// A local working copy so drag reordering does not mutate the store directly.
const displayItems = ref([]);
watch(sortedItems, (value) => { displayItems.value = [...value]; }, { immediate: true });

function moveDraggedItemTo(targetIndex) {
	if (dragIndex.value === null || dragIndex.value === targetIndex) {
		return;
	}
	const items = [...displayItems.value];
	const [moved] = items.splice(dragIndex.value, 1);
	items.splice(targetIndex, 0, moved);
	displayItems.value = items;
	dragIndex.value = targetIndex;
}

// Mouse drag-and-drop (HTML5)
function onDragStart(index) {
	if (sortby.value !== 'default') {
		return;
	}
	dragIndex.value = index;
}

function onDragOver(index) {
	moveDraggedItemTo(index);
}

// Touch drag-and-drop: HTML5 drag events do not fire on touch devices, so we
// track the finger position and find the card underneath it.
function onTouchStart(index) {
	if (sortby.value !== 'default') {
		return;
	}
	dragIndex.value = index;
}

function onTouchMove(event) {
	if (dragIndex.value === null) {
		return;
	}
	const touch = event.touches[0];
	const element = document.elementFromPoint(touch.clientX, touch.clientY);
	const card = element ? element.closest('.listCard') : null;
	if (!card || card.dataset.index === undefined) {
		return;
	}
	const targetIndex = Number(card.dataset.index);
	if (!Number.isNaN(targetIndex)) {
		moveDraggedItemTo(targetIndex);
	}
}

function onDragEnd() {
	if (dragIndex.value === null) {
		return;
	}
	dragIndex.value = null;
	savingOrder.value = true;
	booklistsStore
		.reorderItems({ booklistId: booklist.value.id, items: displayItems.value })
		.finally(() => (savingOrder.value = false));
}

const publicUrl = computed(() => {
	const base = window.location.protocol + '//' + window.location.hostname + '/booklist/';
	return base + (booklist.value.slug ? booklist.value.slug : booklist.value.id);
});

const deleteItemsConfirmationText = computed(
	() => `Are you sure you want to delete selected (${selectedItems.value.length}) items?`
);

// Use Aspen's cover system for catalog items; other item types (e.g. websites)
// keep the image supplied by ChiliPAC.
function coverUrl(item) {
	if (item.type === 'item' && item.data.bib_id) {
		let url = `/bookcover.php?id=${encodeURIComponent(props.recordSource + ':' + item.data.bib_id)}&size=medium`;
		if (item.data.isbn) {
			url += `&isn=${encodeURIComponent(item.data.isbn)}`;
		}
		return url;
	}
	return item.data.image;
}

function reviewsLinkText(item) {
	if (item.rating.review_count == 0) {
		return 'Be the first to review';
	}
	if (item.rating.review_count == 1) {
		return '1 review';
	}
	return item.rating.review_count + ' reviews';
}

function copyLinkToClipboard() {
	if (navigator.clipboard) {
		navigator.clipboard.writeText(publicUrl.value);
	} else if (permalink.value) {
		permalink.value.select();
		document.execCommand('copy');
	}
}

async function confirmDeleteSelected() {
	await booklistsStore.deleteItems({ booklistId: booklist.value.id, ids: selectedItems.value });
	selectedItems.value = [];
	showDeleteItems.value = false;
}

onMounted(() => {
	booklistsStore.getBooklist(props.id);
});
</script>

<style scoped>
.listGrid {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.listCard {
	display: flex;
	align-items: flex-start;
	gap: 12px;
	padding: 10px;
	border: 1px solid #ddd;
	border-radius: 4px;
}

.listCard__image img {
	width: 60px;
	height: auto;
}

.listCard__content {
	flex: 1;
}

.listCard__title {
	font-weight: bold;
}

.listCard__actions {
	margin-left: auto;
}

.listCard[draggable="true"] .sortable-handle {
	cursor: grab;
}

.listCard.is-dragging {
	opacity: 0.5;
}

.chilipac-share {
	display: flex;
	max-width: 400px;
}

.chilipac-share .form-control {
	flex: 1;
	border-radius: 0;
	border-left: 0;
	border-right: 0;
}

.chilipac-share__addon {
	display: flex;
	align-items: center;
	padding: 0 12px;
	white-space: nowrap;
	color: #555;
	background-color: #eee;
	border: 1px solid #ccc;
	border-top-left-radius: 4px;
	border-bottom-left-radius: 4px;
}

.chilipac-share .btn {
	border-top-left-radius: 0;
	border-bottom-left-radius: 0;
}

.annotation {
	font-style: italic;
	color: #808080;
}
</style>
