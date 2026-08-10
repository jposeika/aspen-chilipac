<template>
	<div class="publicBooklistSidebar" v-if="author">
		<div class="publicBooklistSidebar__author">
			<span class="publicBooklistSidebar__avatar">
				<img v-if="author.image" :src="author.image" :alt="author.nickname" />
				<template v-else>{{ initials(author.nickname) }}</template>
			</span>
			<span class="publicBooklistSidebar__authorText">
				<span class="publicBooklistSidebar__createdBy">Created by</span>
				<a class="publicBooklistSidebar__nickname" :href="authorUrl">{{ author.nickname }}</a>
				<span class="publicBooklistSidebar__location" v-if="location">{{ location }}</span>
			</span>
		</div>

		<div class="publicBooklistSidebar__lists" v-if="moreBooklists.length">
			<button
				type="button"
				class="publicBooklistSidebar__heading"
				:aria-expanded="expanded ? 'true' : 'false'"
				@click.prevent="expanded = !expanded"
			>
				Other Lists by This User
				<i class="fas" :class="expanded ? 'fa-caret-up' : 'fa-caret-down'" aria-hidden="true"></i>
			</button>
			<ul class="publicBooklistSidebar__list" v-show="expanded">
				<li v-for="booklist in moreBooklists" :key="booklist.id">
					<a class="publicBooklistSidebar__listItem" :href="`/Booklist/${encodeURIComponent(booklist.id)}`">
						<img v-if="booklist.image" :src="booklist.image" alt="" class="publicBooklistSidebar__thumb" />
						<span class="publicBooklistSidebar__listName">{{ booklist.name }}</span>
					</a>
				</li>
			</ul>
		</div>
	</div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePublicBooklistStore } from '@/store/publicBooklist';

const store = usePublicBooklistStore();
const expanded = ref(true);

const author = computed(() => store.author);
const moreBooklists = computed(() => store.moreBooklists);

const location = computed(() => {
	if (!author.value) {
		return '';
	}
	return [author.value.city, author.value.country].filter((part) => !!part).join(', ');
});

const authorUrl = computed(() => `/Profile/${encodeURIComponent(author.value.id)}`);

function initials(name) {
	if (!name) {
		return '';
	}
	return name
		.split(/\s+/)
		.filter((part) => !!part)
		.slice(0, 2)
		.map((part) => part[0].toUpperCase())
		.join('');
}
</script>

<style scoped>
.publicBooklistSidebar {
	margin-top: 1.5em;
	margin-bottom: 1.5em;
	border: 1px solid #ddd;
	border-radius: 4px;
	overflow: hidden;
}

.publicBooklistSidebar__author {
	display: flex;
	align-items: center;
	gap: 0.75em;
	padding: 1em;
	background: #f7f7f7;
}

.publicBooklistSidebar__avatar {
	flex: 0 0 auto;
	display: flex;
	align-items: center;
	justify-content: center;
	width: 48px;
	height: 48px;
	border-radius: 4px;
	background: #5a6472;
	color: #fff;
	font-weight: 700;
	overflow: hidden;
}

.publicBooklistSidebar__avatar img {
	width: 100%;
	height: 100%;
	object-fit: cover;
}

.publicBooklistSidebar__authorText {
	display: flex;
	flex-direction: column;
	min-width: 0;
}

.publicBooklistSidebar__createdBy {
	font-size: 0.85em;
	color: #666;
}

.publicBooklistSidebar__nickname {
	font-weight: 700;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.publicBooklistSidebar__location {
	font-size: 0.85em;
	color: #666;
}

.publicBooklistSidebar__heading {
	display: flex;
	align-items: center;
	justify-content: space-between;
	width: 100%;
	padding: 0.75em 1em;
	border: 0;
	border-top: 1px solid #ddd;
	background: #f7f7f7;
	font-weight: 700;
	text-align: left;
}

.publicBooklistSidebar__list {
	list-style: none;
	margin: 0;
	padding: 0;
}

.publicBooklistSidebar__listItem {
	display: flex;
	align-items: center;
	gap: 0.75em;
	padding: 0.5em 1em;
	border-top: 1px solid #eee;
}

.publicBooklistSidebar__thumb {
	flex: 0 0 auto;
	width: 44px;
	height: auto;
}

.publicBooklistSidebar__listName {
	min-width: 0;
}
</style>
