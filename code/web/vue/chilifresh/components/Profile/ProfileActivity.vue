<template>
	<div v-if="loaded">
		<p v-if="!activities.length">There is no recent activity yet.</p>
		<ul v-else class="profileActivity">
			<li v-for="(activity, index) in activities" :key="index" class="profileActivity__item">
				<span class="profileActivity__avatar">
					<img v-if="author(activity).avatar" :src="author(activity).avatar" alt="" />
					<template v-else>{{ initials(author(activity).nickname) }}</template>
				</span>
				<div class="profileActivity__body">
					<div>
						<a v-if="author(activity).id" class="profileActivity__nickname" :href="`/Profile/${encodeURIComponent(author(activity).id)}`">{{ author(activity).nickname }}</a>
						<span v-else class="profileActivity__nickname">{{ author(activity).nickname }}</span>
						{{ ' ' }}<span>{{ activity.description }}</span>
					</div>
					<time class="profileActivity__time" :datetime="isoDate(activity.created)">{{ formatDate(activity.created) }}</time>
				</div>
			</li>
		</ul>
		<button v-if="currentPage < totalPages" class="btn btn-default" :disabled="loading" @click.prevent="loadMore">
			Show more
		</button>
	</div>
	<div v-else-if="error" class="alert alert-danger">{{ error }}</div>
	<div v-else>Loading...</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '@/api/chilipac';

const loaded = ref(false);
const loading = ref(false);
const error = ref(null);
const activities = ref([]);
const pagination = ref(null);

const currentPage = computed(() => pagination.value?.current_page ?? 1);
const totalPages = computed(() => pagination.value?.total_pages ?? pagination.value?.last_page ?? 1);

// The user is a nested resource, so it comes wrapped in its own `data`
function author(activity) {
	return activity.user?.data ?? activity.user ?? {};
}

function initials(nickname) {
	return (nickname ?? '')
		.split(/\s+/)
		.filter(Boolean)
		.slice(0, 2)
		.map((part) => part[0].toUpperCase())
		.join('');
}

// Accept unix timestamps (seconds) as well as date strings
function toDate(created) {
	if (!created) {
		return null;
	}
	const parsed = new Date(typeof created === 'number' || /^\d+$/.test(created) ? Number(created) * 1000 : created);
	return isNaN(parsed.getTime()) ? null : parsed;
}

function isoDate(created) {
	return toDate(created)?.toISOString() ?? '';
}

function formatDate(created) {
	const parsed = toDate(created);
	if (!parsed) {
		return created ?? '';
	}
	return parsed.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}

async function load(page) {
	loading.value = true;
	try {
		const response = await api.profile().activity(page);
		activities.value = page === 1 ? response.data.data : [...activities.value, ...response.data.data];
		pagination.value = response.data.meta?.pagination ?? response.data.meta ?? null;
		loaded.value = true;
	} catch (e) {
		error.value = 'Sorry, recent activity could not be loaded. Please try again later.';
	} finally {
		loading.value = false;
	}
}

function loadMore() {
	load(currentPage.value + 1);
}

onMounted(() => load(1));
</script>

<style scoped>
.profileActivity {
	list-style: none;
	padding: 0;
	margin: 0 0 1em;
}

.profileActivity__item {
	display: flex;
	align-items: flex-start;
	gap: 12px;
	padding: 12px 0;
	border-bottom: 1px solid #e5e5e5;
}

.profileActivity__avatar {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 48px;
	height: 48px;
	border-radius: 50%;
	overflow: hidden;
	background: #ddd;
	color: #555;
	font-weight: bold;
	flex-shrink: 0;
}

.profileActivity__avatar img {
	width: 100%;
	height: 100%;
	object-fit: cover;
}

.profileActivity__body {
	min-width: 0;
}

.profileActivity__nickname {
	font-weight: bold;
}

.profileActivity__time {
	display: block;
	color: #767676;
	font-size: 0.9em;
	margin-top: 2px;
}
</style>
