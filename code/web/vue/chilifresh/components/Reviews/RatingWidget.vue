<template>
	<div class="chilipac-rating" v-if="rating">
		<StarRating :rating="rating.rating" :muted="!isRated" :label="label" />
		<div class="chilipac-rating-summary" :class="{ 'chilipac-rating-prompt': !hasReviews }">
			{{ summary }}
		</div>
	</div>
</template>

<script setup>
import { computed } from 'vue';
import { useReviewsStore } from '@/store/reviews';
import StarRating from './StarRating.vue';
import { reviewSummary } from './reviewSummary';

const props = defineProps({
	// Grouped work permanent id; ratings are resolved for the whole page by InitRatings
	workId: { type: String, required: true },
});

const reviewsStore = useReviewsStore();

const rating = computed(() => reviewsStore.workRating(props.workId));
const isRated = computed(() => rating.value.rating > 0);
const hasReviews = computed(() => rating.value.reviewCount > 0);
const summary = computed(() => reviewSummary(rating.value.reviewCount));

const label = computed(() => (
	isRated.value
		? `Average rating ${rating.value.rating.toFixed(1)} out of 5`
		: 'Not yet rated'
));
</script>

<style>
.chilipac-rating-summary {
	font-size: 0.9em;
	line-height: 1.4;
	color: #767676;
}
</style>
