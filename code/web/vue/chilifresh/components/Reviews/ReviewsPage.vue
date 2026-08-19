<template>
	<div class="chilipac-reviews" ref="root">
		<div class="chilipac-reviews__wings">
			<nav class="tabNav" aria-label="Review types">
				<a
					v-for="tab in tabs"
					:key="tab.key"
					href="#"
					class="tabNav__item"
					:class="{ tabNav__item_active: activeTab === tab.key }"
					@click.prevent="changeTab(tab.key)"
				>
					<span class="tabNav__label">{{ tab.label }}</span>
					<span class="tabNav__count">{{ tab.count }}</span>
				</a>
			</nav>

			<div class="chilipac-reviews__sort">
				<label class="sr-only" for="chilipacReviewSort">Sort reviews</label>
				<select
					id="chilipacReviewSort"
					class="form-control input-sm"
					v-model="sortBy"
					:disabled="loading"
				>
					<option v-for="sort in availableSortBy" :value="sort.value" :key="sort.value">
						{{ sort.label }}
					</option>
				</select>
			</div>
		</div>

		<div v-if="loading" class="chilipac-reviews__status">Loading reviews&hellip;</div>
		<div v-else-if="!reviews.length" class="chilipac-reviews__status">
			There are no {{ activeTabLabel }} for this title yet.
		</div>
		<div v-else class="chilipac-reviews__grid">
			<component
				:is="activeTab === 'pro' ? ProReviewItem : ReviewItem"
				v-for="(review, key) in reviews"
				:review="review"
				:key="key"
			/>
		</div>

		<nav class="booklistPagination" v-if="totalPages > 1" aria-label="Review pages">
			<button class="btn btn-default" :disabled="currentPage <= 1 || loadingPage" @click="changePage(currentPage - 1)">
				Previous
			</button>
			<button
				v-for="page in pageWindow"
				:key="page"
				class="btn"
				:class="page === currentPage ? 'btn-primary' : 'btn-default'"
				:disabled="loadingPage"
				@click="changePage(page)"
			>
				{{ page }}
			</button>
			<button class="btn btn-default" :disabled="currentPage >= totalPages || loadingPage" @click="changePage(currentPage + 1)">
				Next
			</button>
		</nav>
	</div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import api from '@/api/chilipac';
import { useReviewsStore } from '@/store/reviews';
import ReviewItem from './ReviewItem.vue';
import ProReviewItem from './ProReviewItem.vue';

const props = defineProps({
	id: { type: String, required: true },
	isbns: { type: Array, default: () => [] },
});

const availableSortBy = [
	{ value: 'newest', label: 'Newest first' },
	{ value: 'oldest', label: 'Oldest first' },
	{ value: 'rating_highest', label: 'Highest rated first' },
	{ value: 'rating_lowest', label: 'Lowest rated first' },
	{ value: 'most_useful', label: 'Most useful first' },
];

const reviewsStore = useReviewsStore();

const root = ref(null);
const activeTab = ref('reader');
const sortBy = ref('newest');
const loading = ref(false);
const loadingPage = ref(false);
const reviews = ref([]);
const currentPage = ref(1);
const totalPages = ref(1);

// Counts come from the same store entry the rating widget filled, so this costs no extra request
const counts = computed(() => reviewsStore.ratingFor(props.id));

const tabs = computed(() => [
	{ key: 'reader', label: 'User reviews', count: counts.value?.readerReviewCount ?? 0 },
	{ key: 'pro', label: 'Professional reviews', count: counts.value?.proReviewCount ?? 0 },
	{ key: 'staff', label: 'Staff reviews', count: counts.value?.staffReviewCount ?? 0 },
]);

const activeTabLabel = computed(() => (
	tabs.value.find((tab) => tab.key === activeTab.value)?.label.toLowerCase() ?? 'reviews'
));

// Windowed list of page numbers centred on the current page.
const pageWindow = computed(() => {
	const span = 2;
	const start = Math.max(1, currentPage.value - span);
	const end = Math.min(totalPages.value, currentPage.value + span);
	const pages = [];
	for (let p = start; p <= end; p++) {
		pages.push(p);
	}
	return pages;
});

function applyResponse(data) {
	reviews.value = data.data ?? [];
	const pagination = data.meta?.pagination ?? {};
	currentPage.value = Number(pagination.current_page ?? 1);
	totalPages.value = Number(pagination.total_pages ?? 1);
}

async function load(page = 1) {
	try {
		const response = await api.reviews().getReviews(props.id, activeTab.value, {
			page,
			sort: sortBy.value,
			isbns: props.isbns,
		});
		applyResponse(response.data);
	} catch (e) {
		console.error('Could not load ChiliFresh reviews', e);
		reviews.value = [];
		totalPages.value = 1;
	}
}

async function loadActiveTab() {
	loading.value = true;
	await load(1);
	loading.value = false;
}

async function changeTab(tab) {
	if (loading.value || loadingPage.value || activeTab.value === tab) {
		return;
	}
	reviews.value = [];
	totalPages.value = 1;
	activeTab.value = tab;
	await loadActiveTab();
}

async function changePage(page) {
	loadingPage.value = true;
	await load(page);
	loadingPage.value = false;
	await nextTick();
	root.value?.scrollIntoView({ block: 'start' });
}

watch(sortBy, loadActiveTab);

onMounted(async () => {
	// Shares the in-flight request when the rating widget is loading the same title
	await reviewsStore.loadRating(props.id, props.isbns);
	// Open on the first tab that has something in it, preferring staff picks, so a title whose
	// only reviews are professional ones doesn't open on an empty tab
	const preferred = ['staff', 'reader', 'pro'].find(
		(key) => (tabs.value.find((tab) => tab.key === key)?.count ?? 0) > 0,
	);
	if (preferred) {
		activeTab.value = preferred;
	}
	await loadActiveTab();
});
</script>

<style>
.chilipac-reviews__wings {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: space-between;
	gap: 10px;
	margin-bottom: 10px;
}

.tabNav {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
}

.tabNav__item {
	padding: 4px 10px;
	border: 1px solid rgba(0, 0, 0, 0.15);
	border-radius: 3px;
	text-decoration: none !important;
}

.tabNav__item_active {
	background-color: rgba(0, 0, 0, 0.08);
	font-weight: bold;
}

.tabNav__count {
	margin-left: 6px;
	opacity: 0.7;
}

.chilipac-reviews__status {
	padding: 10px 0;
	color: #767676;
}

.chilipac-reviews__sort select {
	width: auto;
	display: inline-block;
}
</style>
