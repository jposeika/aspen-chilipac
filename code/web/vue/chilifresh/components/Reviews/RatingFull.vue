<template>
	<div class="chilipac-rating" v-if="rating">
		<StarRating :rating="rating.average" :muted="!isRated" :label="label" />
		<div class="chilipac-rating-summary" :class="{ 'chilipac-rating-prompt': !hasReviews }">
			{{ summary }}
		</div>
	</div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useReviewsStore } from '@/store/reviews';
import StarRating from './StarRating.vue';
import { reviewSummary } from './reviewSummary';

const props = defineProps({
	id: { type: String, required: true },
	isbns: { type: Array, default: () => [] },
});

const reviewsStore = useReviewsStore();

const rating = computed(() => reviewsStore.ratingFor(props.id));
const isRated = computed(() => rating.value.ratingCount > 0);

// Matches the count the batched search results endpoint reports, so both pages agree
const hasReviews = computed(() => rating.value.readerReviewCount > 0);
const summary = computed(() => reviewSummary(rating.value.readerReviewCount));

const label = computed(() => (
	isRated.value
		? `Average rating ${rating.value.average.toFixed(1)} out of 5, ${rating.value.ratingCount} rating(s)`
		: 'Not yet rated'
));

onMounted(() => reviewsStore.loadRating(props.id, props.isbns));
</script>
