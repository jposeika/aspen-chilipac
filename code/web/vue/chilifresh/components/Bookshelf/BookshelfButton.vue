<template>
	<div class="chilipac-booklist">
		<template v-if="!hasToken">
			<button type="button" class="btn btn-sm btn-default btn-wrap btn-block" @click.prevent="openLoginDialog">
				Add to bookshelf
			</button>
		</template>

		<template v-else-if="listsStore.isLoaded">
			<BaseBookshelfButton :id="id" :bib-data="bibData" :source="source" v-slot="{
				displayLabel, availableShelves, isInShelf,
				add, remove, saving,
			}">
				<div class="dropdown" :class="{ open: show }">
					<button
						ref="toggleButton"
						type="button"
						class="btn btn-sm btn-default btn-wrap btn-block dropdown-toggle"
						:disabled="saving"
						@click.prevent="show = !show"
					>
						{{ displayLabel }} <span class="caret"></span>
					</button>

					<ul class="dropdown-menu">
						<li v-for="shelf in availableShelves" :key="shelf.id">
							<a href="#" @click.prevent="add(shelf.id)">
								{{ isInShelf ? 'Move to: ' + shelf.name : 'Add to: ' + shelf.name }}
							</a>
						</li>

						<template v-if="isInShelf">
							<li role="separator" class="divider"></li>
							<li>
								<a href="#" @click.prevent="remove()">Remove from shelf</a>
							</li>
						</template>
					</ul>
				</div>
			</BaseBookshelfButton>
		</template>
	</div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useListsStore } from '@/store/lists';
import BaseBookshelfButton from '../Base/Bookshelf/BookshelfButton.vue';

const props = defineProps({
	id: { type: String, required: true },
	source: String,
	bibData: Object,
});

const listsStore = useListsStore();
const show = ref(false);
const toggleButton = ref(null);

const hasToken = computed(() => !!window.ChiliPAC?.token);

function openLoginDialog() {
	// Shows the Aspen login modal; reloads the page on success so the
	// ChiliPAC session token is picked up
	window.AspenDiscovery.Account.ajaxLogin();
}

function onDocumentClick(e) {
	if (toggleButton.value && !toggleButton.value.contains(e.target)) {
		show.value = false;
	}
}

onMounted(() => document.body.addEventListener('click', onDocumentClick));
onUnmounted(() => document.body.removeEventListener('click', onDocumentClick));
</script>
