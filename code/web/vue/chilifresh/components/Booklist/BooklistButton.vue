<template>
	<div class="chilipac-booklist">
		<template v-if="!hasToken">
			<button type="button" class="btn btn-sm btn-default btn-wrap btn-block" @click.prevent="openLoginDialog">
				Add to booklist
			</button>
		</template>

		<template v-else-if="listsStore.isLoaded">
			<BaseBooklistButton :id="id" :bib-data="bibData" :source="source" v-slot="{
				displayLabel, lists, availableLists, currentLists,
				add, remove, saving, hasMorePages, loadMore,
			}">
				<div class="dropdown" :class="{ open: show }">
					<button
						ref="toggleButton"
						type="button"
						class="btn btn-sm btn-default btn-wrap btn-block dropdown-toggle"
						:disabled="saving"
						@click.prevent="show = !show"
					>
						{{ truncate(displayLabel, 20) }} <span class="caret"></span>
					</button>

					<ul class="dropdown-menu">
						<li v-for="list in availableLists" :key="list.id">
							<a href="#" @click.prevent="add(list)">
								Add to: {{ truncate(list.name, 24) }}
							</a>
						</li>

						<li v-if="hasMorePages">
							<a href="#" @click.prevent="loadMore()">Load more...</a>
						</li>

						<template v-if="currentLists && currentLists.length">
							<li role="separator" class="divider" v-if="availableLists.length"></li>
							<li>
								<a href="#" @click.prevent="remove(currentLists[0])">
									Remove from: {{ truncate(currentLists[0].name, 24) }}
								</a>
							</li>
						</template>

						<li role="separator" class="divider" v-if="lists.length > 0"></li>
						<li>
							<a href="#" @click.prevent="openCreateBooklistDialog">Create Booklist</a>
						</li>
					</ul>
				</div>
			</BaseBooklistButton>

			<CreateBooklistDialog
				v-if="showCreateDialog"
				:source="source"
				@close="showCreateDialog = false"
			/>
		</template>
	</div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useListsStore } from '@/store/lists';
import BaseBooklistButton from '../Base/Booklist/BooklistButton.vue';
import CreateBooklistDialog from './Modals/CreateBooklistDialog.vue';

const props = defineProps({
	id: { type: String, required: true },
	source: String,
	bibData: Object,
});

const listsStore = useListsStore();
const show = ref(false);
const showCreateDialog = ref(false);
const toggleButton = ref(null);

const hasToken = computed(() => !!window.ChiliPAC?.token);

function truncate(str, num) {
	if (!str || str.length <= num) return str;
	return str.slice(0, num) + '..';
}

function openLoginDialog() {
	window.location.href = '/rl?back=' + encodeURIComponent(window.location.href);
}

function openCreateBooklistDialog() {
	show.value = false;
	showCreateDialog.value = true;
}

function onDocumentClick(e) {
	if (toggleButton.value && !toggleButton.value.contains(e.target)) {
		show.value = false;
	}
}

onMounted(() => document.body.addEventListener('click', onDocumentClick));
onUnmounted(() => document.body.removeEventListener('click', onDocumentClick));
</script>

<style>
.chilipac-booklist .dropdown-menu > li > a {
	text-decoration: none !important;
}

.chilipac-booklist .dropdown-menu > li > a:hover,
.chilipac-booklist .dropdown-menu > li > a:focus {
	background-color: rgba(0, 0, 0, 0.06) !important;
}
</style>
