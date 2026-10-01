<template>
	<div class="chilipac-rating" v-if="rating">
		<StarRating :rating="rating.rating" :muted="!isRated" :label="label" />

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
		@saved="reviewsStore.reloadWorkRating(workId, workIsbns)"
		@close="showReviewModal = false"
	/>
</template>

<script setup>
import { computed } from 'vue';
import { useReviewsStore } from '@/store/reviews';
import StarRating from './StarRating.vue';
import NewReviewModal from './NewReviewModal.vue';
import { reviewSummary } from './reviewSummary';
import { useReviewPrompt } from './useReviewPrompt';

const props = defineProps({
	// Grouped work permanent id; ratings are resolved for the whole page by InitRatings
	workId: { type: String, required: true },
});

const reviewsStore = useReviewsStore();
const { showReviewModal, promptForReview } = useReviewPrompt();

const rating = computed(() => reviewsStore.workRating(props.workId));
const isRated = computed(() => rating.value.rating > 0);
const hasReviews = computed(() => rating.value.reviewCount > 0);
const summary = computed(() => reviewSummary(rating.value.reviewCount));

// Reviews are filed against one ISBN. The edition ChiliFresh already knows about is the best
// place for it; failing that the work's primary ISBN. A record with neither cannot be reviewed.
const workIsbns = computed(() => rating.value?.isbns ?? []);
const reviewIsbn = computed(() => rating.value?.isbn || workIsbns.value[0] || null);

const label = computed(() => (
	isRated.value
		? `Average rating ${rating.value.rating.toFixed(1)} out of 5`
		: 'Not yet rated'
));
</script>

<style>
.chilipac-rating-summary {
	display: block;
	font-size: 0.9em;
	line-height: 1.4;
	color: #767676;
}
</style>
