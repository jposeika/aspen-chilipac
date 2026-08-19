<template>
	<div class="reviewCard">
		<div class="reviewCard__header">
			<a v-if="author && author.id" :href="`/Profile/${author.id}`" class="reviewCard__author">
				<div class="reviewCard__image" v-if="author.avatar">
					<img :src="author.avatar" alt="" />
				</div>
				<span>{{ author.nickname }}</span>
			</a>
			<span v-else class="reviewCard__author">{{ (author && author.nickname) || 'Library Reader' }}</span>

			<StarRating
				v-if="review.rating"
				:rating="Number(review.rating)"
				:width="72"
				:label="`Rated ${review.rating} out of 5`"
			/>
		</div>
		<div class="reviewCard__content">
			<h3 class="reviewCard__title" v-if="review.title">{{ review.title }}</h3>
			<div class="reviewCard__text">
				<p>
					{{ displayText }}
					<a href="#" v-if="canReadMore" @click.prevent="readMore = true">Read more</a>
				</p>
			</div>
			<div class="reviewCard__contentFooter" v-if="review.created">
				<div class="reviewCard__contentLabel">Published: {{ formatDate(review.created) }}</div>
			</div>
		</div>
	</div>
</template>

<script setup>
import { ref, computed } from 'vue';
import StarRating from './StarRating.vue';
import { formatReviewDate } from './reviewSummary';

const props = defineProps({
	review: { type: Object, required: true },
});

const TRUNCATE_AT = 300;

const readMore = ref(false);

const author = computed(() => props.review.author?.data ?? props.review.author ?? null);
const text = computed(() => props.review.text ?? '');
const canReadMore = computed(() => !readMore.value && text.value.length > TRUNCATE_AT);
const displayText = computed(() => (
	canReadMore.value ? text.value.substring(0, TRUNCATE_AT) + '...' : text.value
));

const formatDate = formatReviewDate;
</script>

<style>
.reviewCard {
	padding: 12px 0;
	border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.reviewCard__header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 10px;
	margin-bottom: 6px;
}

.reviewCard__author {
	display: flex;
	align-items: center;
	gap: 8px;
	font-weight: bold;
}

.reviewCard__image img {
	width: 32px;
	height: 32px;
	border-radius: 50%;
	object-fit: cover;
}

.reviewCard__title {
	margin: 0 0 4px;
	font-size: 1.05em;
	font-weight: bold;
}

.reviewCard__text {
	white-space: pre-line;
}

.reviewCard__contentLabel {
	margin-top: 4px;
	font-size: 0.9em;
	color: #767676;
}
</style>
