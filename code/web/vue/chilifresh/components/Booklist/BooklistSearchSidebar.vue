<template>
	<div class="booklistSidebar" v-if="loading || booklists.length">
		<h3 class="sidebar-label">{{ title }}</h3>
		<div v-if="loading" class="booklistSidebar__status">
			Loading booklists&hellip;
		</div>
		<template v-else>
			<ul class="booklistSidebar__list">
				<li class="booklistSidebar__item" v-for="booklist in booklists" :key="booklist.id">
					<a class="booklistSidebar__title" :href="booklist.public_url" target="_blank" rel="noopener">
						{{ booklist.name }}
					</a>
					<div class="booklistSidebar__meta">
						<span class="booklistSidebar__author" v-if="author(booklist)">
							{{ author(booklist).nickname }}
						</span>
						<span class="booklistSidebar__count">{{ booklist.item_count }} items</span>
					</div>
				</li>
			</ul>
			<a class="booklistSidebar__more" v-if="showAllUrl" :href="showAllUrl">
				See all booklists
			</a>
		</template>
	</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import queryString from 'query-string';
import api from '@/api/chilipac';

const props = defineProps({
	// Search query the catalog results were produced from.
	s: {
		type: String,
		default: '',
	},
	count: {
		type: Number,
		default: 5,
	},
	title: {
		type: String,
		default: 'Booklists',
	},
});

const loading = ref(false);
const booklists = ref([]);
const pagination = ref(null);

// Only offer the full booklist search page when there is more to see.
const showAllUrl = computed(() => {
	const total = pagination.value ? pagination.value.total : 0;
	if (!total || total <= booklists.value.length) {
		return null;
	}
	return `/Booklists/Search?${queryString.stringify({ s: props.s })}`;
});

function author(booklist) {
	return booklist.author && booklist.author.data ? booklist.author.data : null;
}

async function load() {
	if (!props.s) {
		return;
	}
	loading.value = true;
	try {
		const response = await api.booklist().search({
			s: props.s,
			count: props.count,
		});
		booklists.value = response.data.data;
		pagination.value = response.data.meta ? response.data.meta.pagination : null;
	} catch (error) {
		// The catalog results stand on their own; drop the sidebar block if
		// ChiliPAC is unreachable rather than showing an error next to them.
		booklists.value = [];
		pagination.value = null;
	} finally {
		loading.value = false;
	}
}

onMounted(load);
</script>

<style scoped>
.booklistSidebar {
	margin-top: 1.5em;
	margin-bottom: 1.5em;
}

.booklistSidebar__status {
	color: #666;
	font-size: 0.9em;
}

.booklistSidebar__list {
	list-style: none;
	margin: 0;
	padding: 0;
}

.booklistSidebar__item {
	padding: 0.5em 0;
	border-bottom: 1px solid #f0f0f0;
}

.booklistSidebar__item:last-child {
	border-bottom: none;
}

.booklistSidebar__title {
	display: block;
	font-weight: 700;
	line-height: 1.3;
}

.booklistSidebar__meta {
	display: flex;
	justify-content: space-between;
	gap: 0.5em;
	margin-top: 0.15em;
	font-size: 0.85em;
	color: #666;
}

.booklistSidebar__author {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.booklistSidebar__count {
	flex: 0 0 auto;
	white-space: nowrap;
}

.booklistSidebar__more {
	display: inline-block;
	margin-top: 0.5em;
	font-size: 0.9em;
}
</style>
