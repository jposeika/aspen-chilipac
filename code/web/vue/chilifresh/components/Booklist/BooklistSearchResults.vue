<template>
	<div class="booklistResults">
		<form class="booklistSearchForm" @submit.prevent="submitSearch">
			<div class="input-group">
				<input
					type="text"
					class="form-control"
					v-model="query"
					placeholder="Search booklists"
					aria-label="Search booklists"
				/>
				<span class="input-group-btn">
					<button class="btn btn-primary" type="submit">Search</button>
				</span>
			</div>
		</form>

		<div v-if="store.loading" class="booklistResults__status">
			Loading booklists&hellip;
		</div>
		<div v-else-if="!store.results.length" class="booklistResults__status">
			No booklists match your filters.
		</div>
		<template v-else>
			<div class="booklistResults__grid">
				<div class="booklistCard" v-for="booklist in store.results" :key="booklist.id">
					<div class="booklistCard__body">
						<div class="booklistCard__heading">
							<h2 class="booklistCard__title">
								<a :href="booklist.public_url" target="_blank" rel="noopener">
									{{ booklist.name }}
								</a>
							</h2>
							<span v-if="booklist.staff" class="booklistCard__badge">Staff</span>
						</div>
						<p class="booklistCard__created" v-if="booklist.created">
							Created: {{ formatDate(booklist.created) }}
						</p>
						<p class="booklistCard__desc" v-if="booklist.description">
							{{ booklist.description }}
						</p>
					</div>
					<div class="booklistCard__footer">
						<div class="booklistCard__author" v-if="author(booklist)">
							<img
								v-if="author(booklist).image"
								:src="author(booklist).image"
								:alt="author(booklist).nickname"
								class="booklistCard__avatar"
							/>
							<a :href="`/Profile/${author(booklist).id}`">
								{{ author(booklist).nickname }}
							</a>
						</div>
						<span class="booklistCard__count">{{ booklist.item_count }} items</span>
					</div>
				</div>
			</div>

			<nav
				class="booklistPagination"
				v-if="store.totalPages > 1"
				aria-label="Booklist search pages"
			>
				<button
					class="btn btn-default"
					:disabled="store.currentPage <= 1"
					@click="go(store.currentPage - 1)"
				>
					Previous
				</button>
				<button
					v-for="page in pageWindow"
					:key="page"
					class="btn"
					:class="page === store.currentPage ? 'btn-primary' : 'btn-default'"
					@click="go(page)"
				>
					{{ page }}
				</button>
				<button
					class="btn btn-default"
					:disabled="store.currentPage >= store.totalPages"
					@click="go(store.currentPage + 1)"
				>
					Next
				</button>
			</nav>
		</template>
	</div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import queryString from 'query-string';
import { useBooklistSearchStore } from '@/store/booklistSearch';

const props = defineProps({
	count: {
		type: Number,
		default: 9,
	},
});

const store = useBooklistSearchStore();
const query = ref('');

function submitSearch() {
	store.applyQuery(query.value);
}

// Windowed list of page numbers centred on the current page.
const pageWindow = computed(() => {
	const total = store.totalPages;
	const current = store.currentPage;
	const span = 2;
	const start = Math.max(1, current - span);
	const end = Math.min(total, current + span);
	const pages = [];
	for (let p = start; p <= end; p++) {
		pages.push(p);
	}
	return pages;
});

function author(booklist) {
	return booklist.author && booklist.author.data ? booklist.author.data : null;
}

function formatDate(date) {
	const parsed = new Date(date);
	if (isNaN(parsed.getTime())) {
		return date;
	}
	return parsed.toLocaleDateString('en-US', {
		month: 'short',
		day: 'numeric',
		year: 'numeric',
	});
}

function go(page) {
	if (page < 1 || page > store.totalPages || page === store.currentPage) {
		return;
	}
	store.goToPage(page);
	window.scrollTo({ top: 0, behavior: 'smooth' });
}

function loadFromUrl() {
	const parsed = queryString.parse(location.search, { arrayFormat: 'index' });
	const page = parsed.page ? parseInt(parsed.page, 10) : 1;
	delete parsed.page;
	query.value = parsed.s || '';
	store.setCount(props.count);
	store.initSearch(parsed, page);
}

onMounted(() => {
	loadFromUrl();
	window.addEventListener('popstate', loadFromUrl);
});

onUnmounted(() => {
	window.removeEventListener('popstate', loadFromUrl);
});
</script>

<style scoped>
.booklistSearchForm {
	margin-bottom: 1.5em;
}

.booklistResults__status {
	padding: 2em 0;
	color: #666;
}

.booklistResults__grid {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 1em;
}

@media (max-width: 991px) {
	.booklistResults__grid {
		grid-template-columns: repeat(2, 1fr);
	}
}

@media (max-width: 600px) {
	.booklistResults__grid {
		grid-template-columns: 1fr;
	}
}

.booklistCard {
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 1em;
	background: #fff;
	transition: box-shadow 0.15s ease, transform 0.15s ease, border-color 0.15s ease;
}

.booklistCard:hover {
	border-color: #ccc;
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
	transform: translateY(-2px);
}

.booklistCard__heading {
	display: flex;
	align-items: flex-start;
	justify-content: space-between;
	gap: 0.5em;
}

.booklistCard__title {
	font-size: 1.05em;
	font-weight: 700;
	margin: 0 0 0.25em;
	line-height: 1.3;
}

.booklistCard__title a {
	color: #1a1a1a;
}

.booklistCard__title a:hover,
.booklistCard__title a:focus {
	color: #000;
}

.booklistCard__created {
	margin: 0 0 0.5em;
	font-size: 0.8em;
	color: #999;
}

.booklistCard__badge {
	flex: 0 0 auto;
	font-size: 0.7em;
	text-transform: uppercase;
	letter-spacing: 0.03em;
	background: #eee;
	border-radius: 3px;
	padding: 0.15em 0.5em;
}

.booklistCard__desc {
	margin: 0;
	color: #444;
	font-size: 0.9em;
	display: -webkit-box;
	-webkit-line-clamp: 4;
	-webkit-box-orient: vertical;
	overflow: hidden;
}

.booklistCard__footer {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-top: 1em;
	padding-top: 0.75em;
	border-top: 1px solid #f0f0f0;
	font-size: 0.85em;
	color: #666;
}

.booklistCard__author {
	display: flex;
	align-items: center;
	gap: 0.5em;
	min-width: 0;
}

.booklistCard__author span,
.booklistCard__author a {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.booklistCard__avatar {
	width: 28px;
	height: 28px;
	border-radius: 50%;
	object-fit: cover;
	flex: 0 0 auto;
}

.booklistCard__count {
	flex: 0 0 auto;
	white-space: nowrap;
}

.booklistPagination {
	display: flex;
	flex-wrap: wrap;
	gap: 0.35em;
	justify-content: center;
	margin-top: 1.5em;
}
</style>
