<template>
    <div class="filterCard">
        <div class="filterCard__header">Filter</div>
        <div class="filterCard__body">
            <fieldset class="filterFieldset">
                <legend>Filter by Library</legend>
                <div class="filterFieldset__grid">
                    <div class="radio">
                        <input
                            type="radio"
                            name="library"
                            id="library-1"
                            v-model="params.library"
                            value="all"
                        />
                        <label for="library-1">All Libraries</label>
                    </div>
                    <div class="radio">
                        <input
                            type="radio"
                            name="library"
                            id="library-2"
                            v-model="params.library"
                            value="own"
                        />
                        <label for="library-2">Local Library</label>
                    </div>
                </div>
            </fieldset>

            <fieldset class="filterFieldset">
                <legend>Filter by Type</legend>
                <div class="filterFieldset__grid">
                    <div class="radio">
                        <input
                            type="radio"
                            name="type"
                            id="type-1"
                            v-model="params.lists"
                            value="all"
                        />
                        <label for="type-1">All Lists</label>
                    </div>
                    <div class="radio">
                        <input
                            type="radio"
                            name="type"
                            id="type-2"
                            v-model="params.lists"
                            value="staff"
                        />
                        <label for="type-2">Staff Lists</label>
                    </div>
                </div>
            </fieldset>

            <fieldset class="filterFieldset" v-if="hasQuery">
                <legend>Filter by User</legend>
                <div class="filterFieldset__grid">
                    <Multiselect
                        v-model="params.user"
                        :options="userOptions"
                        :multiple="true"
                        :close-on-select="false"
                        :searchable="true"
                        track-by="id"
                        label="nickname"
                        placeholder="Select users"
                    />
                </div>
            </fieldset>

            <fieldset class="filterFieldset" v-if="store.years.length > 0">
                <legend>Filter by Year</legend>
                <div class="filterFieldset__grid">
                    <div class="checkbox" v-for="year in store.years" :key="year">
                        <input
                            type="checkbox"
                            name="year"
                            v-model="params.year"
                            :value="year"
                            :id="`filter_year_${year}`"
                        />
                        <label :for="`filter_year_${year}`">{{ year }}</label>
                    </div>
                </div>
            </fieldset>

            <fieldset class="filterFieldset">
                <legend>Target audience</legend>
                <div class="filterFieldset__grid">
                    <div class="checkbox">
                        <input
                            type="checkbox"
                            name="year"
                            v-model="params.audience"
                            value="children"
                            id="audience_children"
                        />
                        <label for="audience_children">Children</label>
                    </div>
                    <div class="checkbox">
                        <input
                            type="checkbox"
                            name="year"
                            v-model="params.audience"
                            value="young_adult"
                            id="audience_young"
                        />
                        <label for="audience_young">Young adult</label>
                    </div>
                    <div class="checkbox">
                        <input
                            type="checkbox"
                            name="year"
                            v-model="params.audience"
                            value="adult"
                            id="audience_adult"
                        />
                        <label for="audience_adult">Adult</label>
                    </div>
                </div>
            </fieldset>

            <fieldset class="filterFieldset">
                <legend>Order by</legend>
                <div class="filterFieldset__grid">
                    <div class="select">
                        <select v-model="params.orderby" aria-label="Order by">
                            <option
                                :value="order.value"
                                v-for="order in orderOptions"
                                :key="order.value"
                            >
                                {{ order.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </fieldset>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import Multiselect from 'vue-multiselect'
import 'vue-multiselect/dist/vue-multiselect.css'
import queryString from 'query-string'
import api from '@/api/chilipac'
import { useBooklistSearchStore } from '@/store/booklistSearch'

const store = useBooklistSearchStore()
const types = window.ChiliPAC.booklistTypes

// User (author) options for the multiselect, fetched from the API and only
// relevant when there is a search query.
const userOptions = ref([])
// Usernames taken from the URL, reconciled to option objects once they load.
let pendingUsernames = []

const baseOrderOptions = [
    { value: 'recently_added', name: 'Recently added first' },
    { value: 'recently_updated', name: 'Recently updated first' },
]

// Relevance sorting is only meaningful when there is a search query. The query is
// owned by the shared store (set by the results-page search form / the URL), so the
// option list and default order react to it here rather than being passed from PHP.
const hasQuery = computed(() => {
    const s = store.params.s
    return typeof s === 'string' && s.trim() !== ''
})

const orderOptions = computed(() =>
    hasQuery.value
        ? [...baseOrderOptions, { value: 'relevance', name: 'Relevance' }]
        : baseOrderOptions
)

// Note: the free-text search query (s) is owned by the results-page search form
// and the shared store, so it is intentionally not part of the filter's params.
const params = reactive({
    //st: 'booklists',
    type: [],
    lists: 'all',
    library: 'all',
    year: [],
    user: [],
    audience: [],
    orderby: 'recently_added',
})

// Guard so syncing the controls from the URL (initial load / back-forward)
// does not re-trigger a search via the watcher below.
let suppressWatch = false

// Fetch the available users for the current query + filters. The endpoint only
// makes sense with a search term, so bail out (and clear the options) otherwise.
async function fetchUsers() {
    if (!hasQuery.value) {
        userOptions.value = []
        return
    }
    const response = await api.booklist().searchUsers({
        s: store.params.s,
        years: params.year,
        lists: params.lists,
        type: params.type.map((t) => t.id),
    })
    userOptions.value = response.data.data

    // Restore any users selected via the URL now that the options are available.
    if (pendingUsernames.length) {
        suppressWatch = true
        params.user = userOptions.value.filter((u) => pendingUsernames.includes(u.nickname))
        pendingUsernames = []
        nextTick(() => {
            suppressWatch = false
        })
    }
}

// Refetch user options whenever the query or the filters it depends on change.
const userFetchKey = computed(() =>
    JSON.stringify({
        s: store.params.s,
        lists: params.lists,
        year: params.year,
        type: params.type.map((t) => t.id),
    })
)

watch(userFetchKey, () => {
    fetchUsers()
})

function syncFromUrl() {
    suppressWatch = true

    const parsedQuery = queryString.parse(location.search, {
        arrayFormat: 'index',
    })

    // params.st = parsedQuery.st ? parsedQuery.st : 'booklists'

    params.lists = parsedQuery.lists ? parsedQuery.lists : 'all'

    params.library = parsedQuery.library ? parsedQuery.library : 'all'

    params.type = parsedQuery.type
        ? types.filter((type) => parsedQuery.type.includes(type.id))
        : []

    params.year = parsedQuery.year ? parsedQuery.year : []

    // The user options load asynchronously; remember the usernames and reconcile
    // them to option objects once fetchUsers() resolves.
    params.user = []
    pendingUsernames = parsedQuery.user ? parsedQuery.user : []

    params.audience = parsedQuery.audience ? parsedQuery.audience : []

    // Default to relevance when the URL carries a search query, otherwise recency.
    const queryPresent =
        typeof parsedQuery.s === 'string' && parsedQuery.s.trim() !== ''
    params.orderby = parsedQuery.orderby
        ? parsedQuery.orderby
        : queryPresent
          ? 'relevance'
          : 'recently_added'

    // Release the guard after the reactive updates have flushed.
    nextTick(() => {
        suppressWatch = false
    })
}

watch(
    params,
    () => {
        if (suppressWatch) {
            return
        }
        const updated = JSON.parse(JSON.stringify(params))
        updated.type = updated.type.map((t) => t.id)
        updated.user = updated.user.map((u) => u.nickname)
        store.applyFilters(updated)
    },
    { deep: true }
)

// A new search term was submitted: the store has cleared the filter params from
// the URL, so re-sync the controls to reset them (syncFromUrl suppresses the
// watcher, so this does not trigger another search).
watch(
    () => store.resetToken,
    () => {
        syncFromUrl()
    }
)

// If the search query is cleared while "Relevance" is selected, fall back to a
// valid sort (relevance is no longer an available option without a query).
watch(hasQuery, (present) => {
    if (!present && params.orderby === 'relevance') {
        params.orderby = 'recently_added'
    }
})

onMounted(() => {
    syncFromUrl()
    fetchUsers()
    window.addEventListener('popstate', syncFromUrl)
})

onUnmounted(() => {
    window.removeEventListener('popstate', syncFromUrl)
})
</script>
