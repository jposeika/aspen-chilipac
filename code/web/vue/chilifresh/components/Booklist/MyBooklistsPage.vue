<template>
	<div>
		<div class="btn-toolbar" style="margin-bottom: 1em;">
			<button class="btn btn-primary" @click.prevent="showCreateDialog = true">
				Create New Booklist
			</button>
		</div>

		<div class="form-group">
			<label for="booklist-filter">Filter Booklists</label>
			<input type="text" class="form-control" id="booklist-filter" v-model="filterQuery" />
		</div>

		<div v-if="!booklistsStore.isLoaded || !filteredBooklists.length">
			{{ filterText }}
		</div>

		<table v-else class="table table-striped">
			<thead>
				<tr>
					<th>Name</th>
					<th>Created</th>
					<th>Updated</th>
					<th>Items</th>
					<th>Privacy</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="booklist in filteredBooklists" :key="booklist.id">
					<td>
						<a :href="`/Profile/Booklists/${booklist.id}`">{{ booklist.name }}</a>
					</td>
					<td>{{ parseDate(booklist.created) }}</td>
					<td>{{ parseDate(booklist.updated) }}</td>
					<td>{{ booklist.item_count }}</td>
					<td>{{ capitalize(booklist.privacy) }}</td>
					<td>
						<div class="btn-group btn-group-sm">
							<button class="btn btn-default" @click.prevent="openSettings(booklist)">
								Edit
							</button>
							<button class="btn btn-default" @click.prevent="openDelete(booklist)" title="Delete">
								<i class="fas fa-trash" aria-hidden="true"></i>
							</button>
						</div>
					</td>
				</tr>
			</tbody>
		</table>

		<CreateBooklistDialog
			v-if="showCreateDialog"
			source="mgmt"
			@close="showCreateDialog = false"
		/>
		<BooklistSettingsDialog
			v-if="settingsBooklist"
			:booklist="settingsBooklist"
			@close="settingsBooklist = null"
		/>
		<ConfirmationDialog
			v-if="deleteTarget"
			title="Delete booklist"
			text="Are you sure you want to delete this booklist?"
			button-text="Delete"
			@confirm="confirmDelete"
			@close="deleteTarget = null"
		/>
	</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useBooklistsStore } from '@/store/booklists';
import CreateBooklistDialog from './Modals/CreateBooklistDialog.vue';
import BooklistSettingsDialog from './Modals/BooklistSettingsDialog.vue';
import ConfirmationDialog from '@/components/Modals/ConfirmationDialog.vue';

const booklistsStore = useBooklistsStore();
const filterQuery = ref('');
const showCreateDialog = ref(false);
const settingsBooklist = ref(null);
const deleteTarget = ref(null);

const filterText = computed(() => {
	if (!booklistsStore.isLoaded) {
		return 'Loading booklists..';
	}
	return 'There are no matching booklists';
});

const filteredBooklists = computed(() => {
	return booklistsStore.booklists.filter((booklist) => {
		if (!filterQuery.value) {
			return true;
		}
		const query = filterQuery.value.toLowerCase();
		return ['name', 'description', 'privacy'].some(
			(attr) => booklist[attr] && booklist[attr].toLowerCase().includes(query)
		);
	});
});

function parseDate(date) {
	if (!date) {
		return '';
	}
	const parsed = new Date(date);
	if (isNaN(parsed.getTime())) {
		return date;
	}
	return parsed.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function capitalize(value) {
	if (!value) {
		return '';
	}
	return value.charAt(0).toUpperCase() + value.slice(1);
}

function openSettings(booklist) {
	settingsBooklist.value = booklist;
}

function openDelete(booklist) {
	deleteTarget.value = booklist;
}

async function confirmDelete() {
	const target = deleteTarget.value;
	if (!target) {
		return;
	}
	await booklistsStore.deleteBooklist(target.id);
	deleteTarget.value = null;
}

onMounted(() => {
	booklistsStore.getBooklists();
});
</script>
