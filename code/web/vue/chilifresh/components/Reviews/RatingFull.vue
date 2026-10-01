<template>
	<div class="chilipac-rating" v-if="rating">
		<StarRating :rating="rating.average" :muted="!isRated" :label="label" />

		<a
			v-if="reviewIsbn"
			href="#"
			class="chilipac-rating-summary"
			:class="{ 'chilipac-rating-prompt': !hasReviews }"
			@click.prevent="promptForReview"
		>{{ summary }}</a>
		<div v-else class="chilipac-rating-summary">{{ summary }}</div>
	</div>

	<!-- Kept outside the block above so reloading the counts cannot tear the dialog down -->
	<NewReviewModal
		v-if="showReviewModal"
		:isbn="reviewIsbn"
		@saved="reviewsStore.reloadRating(id, isbns)"
		@close="showReviewModal = false"
	/>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useReviewsStore } from '@/store/reviews';
import StarRating from './StarRating.vue';
import NewReviewModal from './NewReviewModal.vue';
import { reviewSummary } from './reviewSummary';
import { useReviewPrompt } from './useReviewPrompt';

const props = defineProps({
	id: { type: String, required: true },
	isbns: { type: Array, default: () => [] },
});

const reviewsStore = useReviewsStore();
const { showReviewModal, promptForReview } = useReviewPrompt();

const rating = computed(() => reviewsStore.ratingFor(props.id));
const isRated = computed(() => rating.value.ratingCount > 0);

// Matches the count the batched search results endpoint reports, so both pages agree
const hasReviews = computed(() => rating.value.readerReviewCount > 0);
const summary = computed(() => reviewSummary(rating.value.readerReviewCount));

// Reviews are filed against a single ISBN; the grouped work's primary one comes first
const reviewIsbn = computed(() => (props.isbns.length ? String(props.isbns[0]) : null));

const label = computed(() => (
	isRated.value
		? `Average rating ${rating.value.average.toFixed(1)} out of 5, ${rating.value.ratingCount} rating(s)`
		: 'Not yet rated'
));

onMounted(() => reviewsStore.loadRating(props.id, props.isbns));
</script>
