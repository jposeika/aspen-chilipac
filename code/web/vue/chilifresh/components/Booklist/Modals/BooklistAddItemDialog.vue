<template>
	<Teleport to="body">
		<div class="chilipac-overlay" @click.self="close">
			<div class="chilipac-dialog">
				<div class="modal-header">
					<button type="button" class="close" @click.prevent="close">&times;</button>
					<h4 class="modal-title">Add Item To Booklist</h4>
				</div>

				<div class="modal-body">
					<form style="margin-bottom: 1em;" @submit.prevent="search">
						<div class="form-group">
							<label for="add-item-search">Search catalog</label>
							<div class="chilipac-search">
								<input type="text" class="form-control" id="add-item-search" v-model="searchQuery" @keyup.enter="search" />
								<button type="submit" class="btn btn-primary" :disabled="searching">Search</button>
							</div>
						</div>
					</form>

					<div ref="results" style="max-height: 500px; overflow-y: auto;">
						<div class="listCard" v-for="item in items" :key="item.bib_id">
							<div class="listCard__image">
								<img :src="coverUrl(item)" alt="" />
							</div>
							<div class="listCard__content">
								<div class="listCard__title">{{ item.title }}</div>
								<div class="listCard__info">{{ item.author }}</div>
							</div>
							<div class="listCard__actions">
								<button
									v-if="item.booklist_item_id === null"
									class="btn btn-default btn-sm"
									:disabled="adding"
									@click.prevent="addItem(item)"
								>
									<i class="fas fa-plus" aria-hidden="true"></i> Add
								</button>
								<button
									v-else
									class="btn btn-default btn-sm"
									:disabled="adding"
									@click.prevent="removeItem(item)"
								>
									<i class="fas fa-trash" aria-hidden="true"></i> Remove
								</button>
							</div>
						</div>
					</div>

					<nav v-if="items.length && totalPages > 1" class="text-center" style="margin-top: 1em;">
						<button class="btn btn-default btn-sm" :disabled="currentPage <= 1 || searching" @click.prevent="changePage(currentPage - 1)">
							Previous
						</button>
						<span style="margin: 0 1em;">Page {{ currentPage }} of {{ totalPages }}</span>
						<button class="btn btn-default btn-sm" :disabled="currentPage >= totalPages || searching" @click.prevent="changePage(currentPage + 1)">
							Next
						</button>
					</nav>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-default" @click.prevent="close">Close</button>
				</div>
			</div>
		</div>
	</Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import api from '@/api/chilipac';
import { useBooklistsStore } from '@/store/booklists';

const props = defineProps({
	booklist: { type: Object, required: true },
	recordSource: { type: String, default: 'ils' },
});
const emit = defineEmits(['close']);

const booklistsStore = useBooklistsStore();
const searching = ref(false);
const adding = ref(false);
const searchQuery = ref('');
const items = ref([]);
const pagination = ref(null);
const results = ref(null);

const currentPage = computed(() => pagination.value?.current_page ?? 1);
const totalPages = computed(() => pagination.value?.total_pages ?? pagination.value?.last_page ?? 1);

function coverUrl(item) {
	if (item.bib_id) {
		let url = `/bookcover.php?id=${encodeURIComponent(props.recordSource + ':' + item.bib_id)}&size=medium`;
		if (item.isbn) {
			url += `&isn=${encodeURIComponent(item.isbn)}`;
		}
		return url;
	}
	return item.image;
}

function search() {
	if (searchQuery.value && searchQuery.value.length > 0) {
		doSearch(1);
	}
}

async function doSearch(page) {
	searching.value = true;
	if (results.value) {
		results.value.scrollTop = 0;
	}
	try {
		const response = await api.booklist(props.booklist.id).searchBibs(searchQuery.value, page);
		items.value = response.data.data;
		pagination.value = response.data.meta.pagination;
	} finally {
		searching.value = false;
	}
}

function changePage(page) {
	doSearch(page);
}

async function addItem(item) {
	adding.value = true;
	try {
		const data = await booklistsStore.addItemToBooklist({
			booklistId: props.booklist.id,
			bibId: item.bib_id,
			source: 'mgmt',
		});
		item.booklist_item_id = data.id;
	} finally {
		adding.value = false;
	}
}

async function removeItem(item) {
	adding.value = true;
	try {
		await booklistsStore.removeItemFromBooklist({
			booklistId: props.booklist.id,
			itemId: item.booklist_item_id,
		});
		item.booklist_item_id = null;
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
	overflow-y: auto;
	padding: 20px 0;
}

.chilipac-dialog {
	background: #fff;
	border-radius: 4px;
	width: 100%;
	max-width: 600px;
	box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
}

.listCard {
	display: flex;
	align-items: flex-start;
	gap: 12px;
	padding: 10px 0;
	border-bottom: 1px solid #eee;
}

.listCard__image img {
	width: 50px;
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

.chilipac-search {
	display: flex;
}

.chilipac-search .form-control {
	flex: 1;
	border-top-right-radius: 0;
	border-bottom-right-radius: 0;
}

.chilipac-search .btn {
	border-top-left-radius: 0;
	border-bottom-left-radius: 0;
}
</style>
