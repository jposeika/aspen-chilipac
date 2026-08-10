<template>
	<section class="connections" v-if="hasContent">
		<div class="connections__header">
			<h2 class="connections__title">{{ title }}</h2>
			<i class="fas fa-exclamation-circle connections__info" :title="info" :aria-label="info" role="img"></i>
		</div>

		<div class="connections__section" v-if="booklists.length">
			<h3 class="connections__label">{{ booklistsTitle }}</h3>
			<div class="connections__grid">
				<component
					:is="booklist.url ? 'a' : 'div'"
					class="connections__booklist"
					v-for="booklist in booklists"
					:key="booklist.key"
					:href="booklist.url"
					:target="booklist.url ? '_blank' : null"
					:rel="booklist.url ? 'noopener' : null"
				>
					<span class="connections__tile" :style="tileStyle(booklist.user)">
						<img
							v-if="booklist.user.image"
							:src="booklist.user.image"
							:alt="booklist.user.name"
						/>
						<template v-else>{{ initials(booklist.user.name) }}</template>
					</span>
					<span class="connections__booklistText">
						<span class="connections__booklistName">{{ booklist.name }}</span>
						<span class="connections__booklistUser">{{ booklist.user.label }}</span>
						<span class="connections__booklistDate" v-if="booklist.age">{{ booklist.age }}</span>
					</span>
				</component>
			</div>
		</div>

		<div class="connections__section" v-if="users.length">
			<h3 class="connections__label">{{ usersTitle }}</h3>
			<div class="connections__users">
				<component
					:is="user.url ? 'a' : 'span'"
					class="connections__tile connections__tile--user"
					v-for="user in users"
					:key="user.key"
					:href="user.url"
					:title="user.name"
					:style="tileStyle(user)"
				>
					<img v-if="user.image" :src="user.image" :alt="user.name" />
					<template v-else>{{ initials(user.name) }}</template>
				</component>
			</div>
		</div>
	</section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '@/api/chilipac';

const props = defineProps({
	// Search query the catalog results were produced from.
	s: {
		type: String,
		default: '',
	},
	title: {
		type: String,
		default: 'Connections',
	},
	info: {
		type: String,
		default: 'Booklists and readers connected to your search.',
	},
	booklistsTitle: {
		type: String,
		default: 'User booklists having related items',
	},
	usersTitle: {
		type: String,
		default: 'Users related to this item',
	},
});

// Tile backgrounds for users without a picture, picked from the nickname so a
// user keeps the same colour everywhere the card is shown.
const tileColors = [
	{ background: '#e6005c', color: '#fff' },
	{ background: '#dbe4f0', color: '#7d8ba1' },
	{ background: '#a8dbb0', color: '#fff' },
	{ background: '#8794a8', color: '#fff' },
	{ background: '#f5b800', color: '#fff' },
	{ background: '#2196f3', color: '#fff' },
	{ background: '#2b3445', color: '#fff' },
	{ background: '#00bcd4', color: '#fff' },
];

const booklists = ref([]);
const users = ref([]);

const hasContent = computed(() => booklists.value.length > 0 || users.value.length > 0);

function initials(name) {
	return (name || '?').trim().slice(0, 2);
}

function tileStyle(user) {
	if (user.image) {
		return null;
	}
	let hash = 0;
	const name = user.name || '';
	for (let i = 0; i < name.length; i++) {
		hash = (hash * 31 + name.charCodeAt(i)) % 100000;
	}
	return tileColors[hash % tileColors.length];
}

// ChiliPAC wraps both collections and single relations in a data envelope;
// unwrap whichever level we were handed.
function unwrap(payload) {
	if (payload && payload.data !== undefined) {
		return payload.data;
	}
	return payload;
}

function listOf(response) {
	const data = unwrap(response ? response.data : null);
	return Array.isArray(data) ? data : [];
}

function normalizeUser(raw) {
	const user = unwrap(raw) || {};
	const name = user.nickname || user.name || user.username || '';
	const library = user.library || user.library_name || user.branch || '';
	return {
		id: user.id || null,
		name,
		image: user.image || user.avatar || '',
		library,
		label: library ? `${name}, ${library}` : name,
		url: user.id ? `/Profile/${user.id}` : null,
	};
}

function timeAgo(value) {
	if (!value) {
		return '';
	}
	const parsed = new Date(value);
	if (isNaN(parsed.getTime())) {
		return '';
	}
	const seconds = Math.round((Date.now() - parsed.getTime()) / 1000);
	const units = [
		['year', 31536000],
		['month', 2592000],
		['week', 604800],
		['day', 86400],
		['hour', 3600],
		['minute', 60],
	];
	const formatter = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
	for (const [unit, unitSeconds] of units) {
		const amount = Math.floor(seconds / unitSeconds);
		if (amount >= 1) {
			return formatter.format(-amount, unit);
		}
	}
	return formatter.format(0, 'minute');
}

async function loadBooklists() {
	const response = await api.booklist().connections({ s: props.s });
	booklists.value = listOf(response).map((booklist, index) => ({
		key: booklist.id || `booklist-${index}`,
		name: booklist.name || booklist.title || '',
		url: booklist.public_url || booklist.url || null,
		user: normalizeUser(booklist.author || booklist.user),
		age: timeAgo(booklist.updated || booklist.created || booklist.date),
	}));
}

async function loadUsers() {
	const response = await api.user().connections({ s: props.s });
	users.value = listOf(response).map((raw, index) => {
		const user = normalizeUser(raw);
		return {
			...user,
			key: user.id || `user-${index}`,
		};
	});
}

// Each half stands on its own: a failing or unreachable endpoint drops its
// section instead of taking the whole card - or the search results - down.
onMounted(() => {
	if (!props.s) {
		return;
	}
	loadBooklists().catch(() => {
		booklists.value = [];
	});
	loadUsers().catch(() => {
		users.value = [];
	});
});
</script>

<style scoped>
.connections {
	margin: 2em 0;
	padding: 1.5em;
	background: #efefef;
	border-radius: 4px;
}

.connections__header {
	display: flex;
	align-items: center;
	gap: 0.5em;
	padding-bottom: 0.75em;
	border-bottom: 1px solid #ddd;
}

.connections__title {
	margin: 0;
	font-size: 1.6em;
	font-weight: 700;
	color: #1a1a1a;
}

.connections__info {
	color: #6b2d4f;
	font-size: 1em;
	cursor: help;
}

.connections__section + .connections__section {
	border-top: 1px solid #ddd;
}

.connections__label {
	margin: 0 0 1em;
	font-size: 1em;
	font-weight: 400;
	color: #333;
}

.connections__section {
	padding: 1em 0 0.5em;
}

.connections__grid {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: 1.5em 1em;
}

@media (max-width: 991px) {
	.connections__grid {
		grid-template-columns: repeat(2, 1fr);
	}
}

@media (max-width: 600px) {
	.connections__grid {
		grid-template-columns: 1fr;
	}
}

.connections__booklist {
	display: flex;
	align-items: flex-start;
	gap: 0.75em;
	min-width: 0;
	color: inherit;
}

a.connections__booklist:hover,
a.connections__booklist:focus {
	color: inherit;
	text-decoration: none;
}

a.connections__booklist:hover .connections__booklistUser,
a.connections__booklist:focus .connections__booklistUser {
	text-decoration: underline;
}

.connections__tile {
	display: flex;
	align-items: center;
	justify-content: center;
	flex: 0 0 auto;
	width: 58px;
	height: 58px;
	overflow: hidden;
	background: #dbe4f0;
	color: #7d8ba1;
	font-size: 1.5em;
	font-weight: 700;
	line-height: 1;
}

.connections__tile img {
	width: 100%;
	height: 100%;
	object-fit: cover;
}

.connections__booklistText {
	display: flex;
	flex-direction: column;
	min-width: 0;
}

.connections__booklistName,
.connections__booklistDate {
	font-size: 0.85em;
	color: #888;
}

.connections__booklistName {
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.connections__booklistUser {
	font-weight: 700;
	color: #1a1a1a;
	line-height: 1.25;
}

.connections__users {
	display: flex;
	flex-wrap: wrap;
	gap: 0.6em;
}

.connections__tile--user {
	width: 52px;
	height: 52px;
	font-size: 1.35em;
}

a.connections__tile--user:hover,
a.connections__tile--user:focus {
	text-decoration: none;
	opacity: 0.85;
}
</style>
