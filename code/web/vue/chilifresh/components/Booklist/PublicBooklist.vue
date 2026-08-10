<template>
	<div class="publicBooklist">
		<div v-if="!store.loaded" class="publicBooklist__status">
			Loading booklist&hellip;
		</div>

		<div v-else-if="store.notFound" class="publicBooklist__status">
			<h1>Booklist not found</h1>
			<p>This booklist does not exist, or it has been deleted.</p>
			<a class="btn btn-primary" href="/Booklists/Search">Browse booklists</a>
		</div>

		<div v-else-if="store.notPublic" class="publicBooklist__status">
			<h1>This booklist is private</h1>
			<p>The person who created this booklist has not shared it publicly.</p>
			<a class="btn btn-primary" href="/Booklists/Search">Browse booklists</a>
		</div>

		<div v-else-if="store.errorStatus !== null" class="publicBooklist__status">
			<h1>Booklist unavailable</h1>
			<p>This booklist could not be loaded right now. Please try again later.</p>
		</div>

		<template v-else-if="booklist">
			<div class="publicBooklist__header">
				<div class="publicBooklist__media">
					<div class="publicBooklist__covers" v-if="store.covers.length">
						<img
							v-for="(cover, index) in store.covers.slice(0, 5).reverse()"
							:key="index"
							:src="cover"
							alt=""
							class="publicBooklist__cover"
						/>
					</div>
					<div class="publicBooklist__share">
						<a
							class="btn btn-default"
							:href="facebookShareUrl"
							target="_blank"
							rel="noopener"
						>
							<i class="fab fa-facebook-f" aria-hidden="true"></i> Share
						</a>
						<a
							class="btn btn-default"
							:href="twitterShareUrl"
							target="_blank"
							rel="noopener"
						>
							<i class="fab fa-twitter" aria-hidden="true"></i> Share
						</a>
					</div>
					<div class="publicBooklist__qr" v-if="qrCode">
						<img :src="qrCode" :alt="`QR code linking to ${shareUrl}`" />
						<span class="publicBooklist__qrCaption">Scan on Mobile</span>
					</div>
				</div>
				<div class="publicBooklist__intro">
					<h1 class="publicBooklist__title">{{ booklist.name }}</h1>
					<p class="publicBooklist__description" v-if="booklist.description">
						{{ booklist.description }}
					</p>
					<div class="publicBooklist__actions">
						<a class="btn btn-default" :href="`/Booklist/${encodeURIComponent(id)}/DownloadPDF`">
							<i class="fas fa-download" aria-hidden="true"></i> Download PDF
						</a>
						<button
							v-if="hasToken"
							type="button"
							class="btn btn-primary"
							:disabled="duplicating"
							@click.prevent="duplicate"
						>
							<i class="fas fa-copy" aria-hidden="true"></i>
							{{ duplicating ? 'Duplicating...' : 'Duplicate This Booklist' }}
						</button>
					</div>
					<p class="publicBooklist__error" v-if="duplicateError">{{ duplicateError }}</p>
				</div>
			</div>

			<hr>

			<div class="publicBooklist__toolbar">
				<h2 class="publicBooklist__count">{{ store.totalItems }} items</h2>
			</div>

			<div class="listGrid" v-if="store.items.length">
				<div class="listCard" v-for="item in store.items" :key="item.id">
					<component
						:is="itemUrl(item) ? 'a' : 'div'"
						class="listCard__image"
						:href="itemUrl(item)"
						:target="linkTarget(item)"
						:rel="linkTarget(item) === '_blank' ? 'noopener' : null"
						tabindex="-1"
					>
						<img :src="coverUrl(item)" alt="" />
					</component>

					<div class="listCard__content">
						<component
							:is="itemUrl(item) ? 'a' : 'span'"
							class="listCard__title"
							:href="itemUrl(item)"
							:target="linkTarget(item)"
							:rel="linkTarget(item) === '_blank' ? 'noopener' : null"
						>
							{{ item.data.title }}
						</component>
						<div class="listCard__info" v-if="item.data.author">{{ item.data.author }}</div>
						<div class="listCard__rating" v-if="hasRating(item)">
							<span class="listCard__stars" :aria-label="ratingLabel(item)">
								<i
									v-for="(star, index) in stars(item.rating.value)"
									:key="index"
									:class="star"
									aria-hidden="true"
								></i>
							</span>
							<span class="listCard__reviews">
								{{ item.rating.review_count ? item.rating.review_count + ' reviews' : 'No reviews yet' }}
							</span>
						</div>
						<div class="annotation" v-if="item.data.annotation">
							{{ item.data.annotation }}
						</div>
					</div>

					<div class="listCard__actions" v-if="catalogBibId(item)">
						<BooklistButton :id="catalogBibId(item)" source="booklist" />
						<BookshelfButton :id="catalogBibId(item)" source="booklist" />
						<button
							v-if="isInCatalog(item)"
							type="button"
							class="btn btn-sm btn-primary btn-wrap btn-block"
							@click.prevent="placeHold(item, $event)"
						>
							<i class="fas fa-clock" aria-hidden="true"></i> Place a hold
						</button>
					</div>
				</div>
			</div>

			<p v-else class="publicBooklist__status">This booklist does not have any items yet.</p>

			<nav
				class="booklistPagination"
				v-if="store.totalPages > 1"
				aria-label="Booklist pages"
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
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { usePublicBooklistStore } from '@/store/publicBooklist';
import { useListsStore } from '@/store/lists';
import qrcode from 'qrcode-generator';
import api from '@/api/chilipac';
import BooklistButton from './BooklistButton.vue';
import BookshelfButton from '@/components/Bookshelf/BookshelfButton.vue';

const props = defineProps({
	id: { type: String, required: true },
	recordSource: { type: String, default: 'ils' },
	recordUrlComponent: { type: String, default: 'Record' },
	// Items requested per page from the API.
	count: { type: Number, default: 10 },
});

const store = usePublicBooklistStore();
const listsStore = useListsStore();
const duplicating = ref(false);
const duplicateError = ref('');
// Bib ids on the current page that Aspen holds in its own catalog.
const catalogBibs = ref(new Set());

const booklist = computed(() => store.booklist);
const hasToken = computed(() => !!window.ChiliPAC?.token);

// The breadcrumb is rendered server side as a generic "Booklist" label since the
// name is only known once this component fetches it.
watch(() => booklist.value?.name, (name) => {
	if (!name) {
		return;
	}
	const crumb = document.querySelector('.breadcrumbs .breadcrumb li:last-child a');
	if (crumb) {
		crumb.textContent = name;
	}
});

// The add to booklist / bookshelf buttons need to know which of the visible bibs
// the logged in user has already saved, so refresh that whenever the page changes.
watch(() => store.items, (items) => {
	const bibs = items.map(catalogBibId).filter((bib) => bib !== null);
	loadCatalogBibs(bibs);
	if (!hasToken.value || !bibs.length) {
		return;
	}
	listsStore.loadBibs(bibs);
});

// A booklist is curated in ChiliPAC and can list items this library does not
// own. Ask Aspen which of them it actually has before offering to link to a
// record page or place a hold, both of which fail on an unknown bib.
async function loadCatalogBibs(bibs) {
	catalogBibs.value = new Set();
	if (!bibs.length) {
		return;
	}
	const query = bibs.map((bib) => `bibs[]=${encodeURIComponent(bib)}`).join('&');
	try {
		const response = await fetch(`/Booklists/AJAX?method=getCatalogBibs&${query}`);
		const data = await response.json();
		catalogBibs.value = new Set((data.bibs || []).map(String));
	} catch (error) {
		// Leave everything treated as not held locally rather than offering
		// actions that would fail.
		catalogBibs.value = new Set();
	}
}

function isInCatalog(item) {
	const bibId = catalogBibId(item);
	return bibId !== null && catalogBibs.value.has(bibId);
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

const shareUrl = computed(() => window.location.origin + window.location.pathname);

const facebookShareUrl = computed(
	() => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}`
);

// A scannable link to this booklist. Rendered as a data URI rather than markup so
// nothing from the address bar can reach the DOM as HTML.
const qrCode = computed(() => {
	try {
		const qr = qrcode(0, 'M');
		qr.addData(shareUrl.value);
		qr.make();
		return qr.createDataURL(6, 0);
	} catch (error) {
		// Only happens if the URL is too long for the largest QR version.
		return '';
	}
});

const twitterShareUrl = computed(() => {
	const text = booklist.value ? booklist.value.name : '';
	return `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(text)}`;
});

// Catalog items carry the bib id they were added from; websites and other item
// types have none and so get no catalog links or actions.
function catalogBibId(item) {
	if (item.type === 'item' && item.data.bib_id) {
		return String(item.data.bib_id);
	}
	return null;
}

// Use Aspen's cover system for items it holds; everything else keeps the image
// supplied by ChiliPAC.
function coverUrl(item) {
	if (isInCatalog(item)) {
		let url = `/bookcover.php?id=${encodeURIComponent(props.recordSource + ':' + catalogBibId(item))}&size=medium`;
		if (item.data.isbn) {
			url += `&isn=${encodeURIComponent(item.data.isbn)}`;
		}
		return url;
	}
	return item.data.image;
}

// Items in the catalog link to Aspen's record details page; everything else
// keeps the URL supplied by ChiliPAC, which may be nothing at all.
function itemUrl(item) {
	if (isInCatalog(item)) {
		return `/${props.recordUrlComponent}/${encodeURIComponent(catalogBibId(item))}/Home`;
	}
	return item.data.url;
}

function linkTarget(item) {
	return isInCatalog(item) ? '_self' : '_blank';
}

// Websites and other non catalog entries are given an empty rating by the API,
// which is not something anyone can rate or review, so only titles show stars.
function hasRating(item) {
	return item.type === 'item' && !!item.rating;
}

// Rating out of five, rounded to the nearest half star. Aspen only ships the
// solid Font Awesome family, so empty stars are solid stars greyed out rather
// than the regular (outline) variant.
function stars(value) {
	const rating = Number(value) || 0;
	const icons = [];
	for (let i = 1; i <= 5; i++) {
		if (rating >= i) {
			icons.push('fas fa-star');
		} else if (rating >= i - 0.5) {
			icons.push('fas fa-star-half-alt');
		} else {
			icons.push('fas fa-star listCard__star--empty');
		}
	}
	return icons;
}

function ratingLabel(item) {
	return `Rated ${item.rating.value} out of 5`;
}

function placeHold(item, event) {
	const bibId = catalogBibId(item);
	if (!isInCatalog(item) || !window.AspenDiscovery?.Record) {
		return;
	}
	window.AspenDiscovery.Record.showPlaceHold(
		props.recordUrlComponent,
		props.recordSource,
		bibId,
		undefined,
		undefined,
		event.currentTarget
	);
}

async function duplicate() {
	duplicating.value = true;
	duplicateError.value = '';
	try {
		const response = await api.booklist(props.id).cloneBooklist({
			name: booklist.value.name,
		});
		const newId = response?.data?.data?.id;
		window.location.href = newId ? `/Profile/Booklists/${encodeURIComponent(newId)}` : '/Profile/Booklists';
	} catch (error) {
		duplicating.value = false;
		duplicateError.value = 'This booklist could not be duplicated. Please try again later.';
	}
}

function go(page) {
	if (page < 1 || page > store.totalPages || page === store.currentPage) {
		return;
	}
	store.goToPage(page);
	window.scrollTo({ top: 0, behavior: 'smooth' });
}

function loadFromUrl() {
	const parsed = new URLSearchParams(window.location.search);
	const page = parsed.get('page') ? parseInt(parsed.get('page'), 10) : 1;
	store.init({
		id: props.id,
		page: Number.isNaN(page) ? 1 : page,
		count: props.count,
	});
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
.publicBooklist__status {
	padding: 2em 0;
	color: #666;
}

.publicBooklist__header {
	display: flex;
	flex-wrap: wrap;
	align-items: flex-start;
	gap: 2em;
	margin-top: 1.5em;
	margin-bottom: 1.5em;
}

.publicBooklist__media {
	flex: 0 0 auto;
	display: flex;
	flex-direction: column;
	gap: 1em;
}

.publicBooklist__covers {
	display: flex;
}

.publicBooklist__share {
	display: flex;
	flex-wrap: wrap;
	justify-content: center;
	gap: 0.5em;
}

.publicBooklist__qr {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 0.35em;
}

.publicBooklist__qr img {
	width: 120px;
	height: 120px;
	/* The code is a small bitmap scaled up; keep the modules crisp. */
	image-rendering: pixelated;
}

.publicBooklist__qrCaption {
	font-size: 0.85em;
	color: #666;
}

/* A fixed height keeps the stack tidy no matter what shape the covers are. */
.publicBooklist__cover {
	width: 115px;
	height: 175px;
	object-fit: cover;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
}

/* The covers are rendered in reverse order and overlapped, so the first item in
   the booklist ends up painted on top of the stack. */
.publicBooklist__cover + .publicBooklist__cover {
	margin-left: -80px;
}

.publicBooklist__intro {
	flex: 1 1 320px;
	min-width: 0;
}

.publicBooklist__title {
	margin-top: 0;
}

.publicBooklist__description {
	color: #444;
}

.publicBooklist__actions {
	display: flex;
	flex-wrap: wrap;
	gap: 0.5em;
	margin-top: 1em;
}

.publicBooklist__error {
	margin-top: 0.75em;
	color: #a94442;
}

.publicBooklist__toolbar {
	display: flex;
	align-items: center;
	justify-content: space-between;
}

.publicBooklist__count {
	margin: 0;
}

.listGrid {
	display: flex;
	flex-direction: column;
	gap: 10px;
	margin-top: 1em;
}

.listCard {
	display: flex;
	align-items: flex-start;
	gap: 12px;
	padding: 10px;
	border: 1px solid #ddd;
	border-radius: 4px;
}

.listCard__image img {
	width: 80px;
	height: auto;
}

.listCard__content {
	flex: 1;
	min-width: 0;
}

.listCard__title {
	display: block;
	font-weight: bold;
}

.listCard__info {
	color: #666;
}

.listCard__rating {
	display: flex;
	align-items: center;
	gap: 0.5em;
	margin-top: 0.25em;
}

.listCard__stars {
	color: #333;
	white-space: nowrap;
}

.listCard__stars .listCard__star--empty {
	color: #ccc;
}

.listCard__reviews {
	font-size: 0.9em;
	color: #666;
}

.listCard__actions {
	flex: 0 0 200px;
	display: flex;
	flex-direction: column;
	gap: 5px;
}

@media (max-width: 767px) {
	.listCard__actions {
		flex-basis: 100%;
	}
}

.annotation {
	margin-top: 5px;
	font-style: italic;
	color: #808080;
}

.booklistPagination {
	display: flex;
	flex-wrap: wrap;
	gap: 0.35em;
	justify-content: center;
	margin-top: 1.5em;
}
</style>
