<template>
	<div v-if="loaded">
		<ul class="nav nav-tabs" style="margin-bottom: 1em;">
			<li :class="{ active: shelf === 'completed' }">
				<a :href="shelfUrl('completed')">Completed ({{ meta.item_count.completed }})</a>
			</li>
			<li :class="{ active: shelf === 'in_progress' }">
				<a :href="shelfUrl('in_progress')">In progress ({{ meta.item_count.in_progress }})</a>
			</li>
			<li :class="{ active: shelf === 'for_later' }">
				<a :href="shelfUrl('for_later')">For later ({{ meta.item_count.for_later }})</a>
			</li>
		</ul>

		<div style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 1em;">
			<button class="btn btn-default" @click.prevent="showAddItem = true">
				<i class="fas fa-plus" aria-hidden="true"></i> Add Item
			</button>
			<div class="chilipac-share">
				<span class="chilipac-share__addon">Share shelves</span>
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
			<div class="form-inline">
				<label for="bookshelf-privacy">My bookshelves are</label>
				<select id="bookshelf-privacy" class="form-control" v-model="isPublic">
					<option :value="true">Public</option>
					<option :value="false">Private</option>
				</select>
				<button class="btn btn-default" :disabled="saving" @click.prevent="changePrivacy">Save</button>
			</div>
		</div>

		<hr>

		<template v-if="items.length">
			<div class="actionWings" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 1em;">
				<div class="form-inline">
					<select class="form-control" v-model="action" :disabled="checked.length === 0 || saving">
						<option value="delete">Delete</option>
						<option v-for="other in otherShelves" :value="`move:${other.id}`" :key="other.id">
							Move to: {{ other.name }}
						</option>
					</select>
					<button class="btn btn-default" :disabled="checked.length === 0 || saving" @click.prevent="bulkAction">
						Apply
					</button>
				</div>
				<div class="form-inline">
					<label for="bookshelf-sort">Sort by</label>
					<select id="bookshelf-sort" class="form-control" v-model="sort" @change="sortBy">
						<option value="date">Date</option>
						<option value="author">Author</option>
						<option value="title">Title</option>
					</select>
				</div>
			</div>

			<div class="listGrid">
				<div class="listCard" v-for="(item, index) in items" :key="item.bib_id">
					<div class="listCard__checkbox">
						<input type="checkbox" :id="`item_${index}_selected`" :value="item.bib_id" v-model="checked" />
					</div>
					<a :href="itemUrl(item)" class="listCard__image" tabindex="-1">
						<img :src="coverUrl(item)" alt="" />
					</a>
					<div class="listCard__content">
						<a :href="itemUrl(item)" class="listCard__title">{{ item.title }}</a>
						<div class="listCard__info">{{ item.author }}</div>
					</div>
				</div>
			</div>

			<nav v-if="totalPages > 1" class="text-center" style="margin-top: 1em;">
				<button class="btn btn-default btn-sm" :disabled="currentPage <= 1" @click.prevent="goPage(currentPage - 1)">
					Previous
				</button>
				<span style="margin: 0 1em;">Page {{ currentPage }} of {{ totalPages }}</span>
				<button class="btn btn-default btn-sm" :disabled="currentPage >= totalPages" @click.prevent="goPage(currentPage + 1)">
					Next
				</button>
			</nav>
		</template>

		<p v-else class="text-muted text-center" style="margin-top: 1.5em; font-size: 1.6em;">
			Add your first item to this shelf.
		</p>

		<BookshelfAddItemDialog
			v-if="showAddItem"
			:shelf="shelf"
			:record-source="recordSource"
			@changed="addItemChanged = true"
			@close="closeAddItem"
		/>
	</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '@/api/chilipac';
import BookshelfAddItemDialog from './Modals/BookshelfAddItemDialog.vue';

const props = defineProps({
	shelf: { type: String, default: 'completed' },
	recordSource: { type: String, default: 'ils' },
	recordUrlComponent: { type: String, default: 'Record' },
});

const allShelves = [
	{ id: 'completed', name: 'Completed' },
	{ id: 'in_progress', name: 'In Progress' },
	{ id: 'for_later', name: 'For Later' },
];

const loaded = ref(false);
const saving = ref(false);
const isPublic = ref(false);
const action = ref('delete');
const sort = ref('date');
const page = ref(1);
const checked = ref([]);
const items = ref([]);
const meta = ref({});
const showAddItem = ref(false);
const addItemChanged = ref(false);
const permalink = ref(null);

const otherShelves = computed(() => allShelves.filter((s) => s.id !== props.shelf));
const publicUrl = computed(() => meta.value.public_url);
const currentPage = computed(() => meta.value.pagination?.current_page ?? 1);
const totalPages = computed(() => meta.value.pagination?.total_pages ?? meta.value.pagination?.last_page ?? 1);

function shelfUrl(shelf) {
	return `/Profile/Bookshelf/${shelf}`;
}

// Use Aspen's cover system and record details page for catalog items.
function coverUrl(item) {
	let url = `/bookcover.php?id=${encodeURIComponent(props.recordSource + ':' + item.bib_id)}&size=medium`;
	if (item.isbn) {
		url += `&isn=${encodeURIComponent(item.isbn)}`;
	}
	return url;
}

function itemUrl(item) {
	return `/${props.recordUrlComponent}/${encodeURIComponent(item.bib_id)}/Home`;
}

function navigate(params) {
	const search = new URLSearchParams();
	Object.entries(params).forEach(([key, value]) => {
		if (value != null) {
			search.set(key, value);
		}
	});
	window.location.search = search.toString();
}

function sortBy() {
	navigate({ sort: sort.value });
}

function goPage(target) {
	navigate({ sort: sort.value, page: target });
}

function changePrivacy() {
	saving.value = true;
	api.bookshelf()
		.setPrivacy(isPublic.value)
		.then(() => {
			meta.value.public = isPublic.value;
		})
		.finally(() => {
			saving.value = false;
		});
}

function bulkAction() {
	saving.value = true;
	const request = action.value === 'delete'
		? api.bookshelf().bulkDelete(props.shelf, checked.value)
		: api.bookshelf().bulkMove(action.value.split(':')[1], checked.value);
	request.then(() => {
		window.location.reload();
	});
}

function copyLinkToClipboard() {
	if (navigator.clipboard) {
		navigator.clipboard.writeText(publicUrl.value);
	} else if (permalink.value) {
		permalink.value.select();
		document.execCommand('copy');
	}
}

function closeAddItem() {
	showAddItem.value = false;
	if (addItemChanged.value) {
		window.location.reload();
	}
}

onMounted(() => {
	const params = new URLSearchParams(window.location.search);
	sort.value = params.get('sort') || 'date';
	page.value = Number(params.get('page')) || 1;

	api.bookshelf()
		.get(props.shelf, sort.value, page.value)
		.then((response) => {
			items.value = response.data.data;
			meta.value = response.data.meta;
			isPublic.value = meta.value.public;
			loaded.value = true;
		});
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

.form-inline label {
	margin-right: 8px;
}

.form-inline .form-control + .btn {
	margin-left: 8px;
}
</style>
