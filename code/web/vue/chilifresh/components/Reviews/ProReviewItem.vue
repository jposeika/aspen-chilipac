<template>
	<div class="reviewCard">
		<div class="reviewCard__header" v-if="review.author">
			<span class="reviewCard__author">{{ review.author }}</span>
		</div>
		<div class="reviewCard__content">
			<h3 class="reviewCard__title" v-if="review.title">{{ review.title }}</h3>
			<div class="reviewCard__text">
				<p>
					{{ review.text }}
					<a v-if="review.link" :href="review.link" target="_blank" rel="noopener">read more</a>
				</p>
			</div>
			<div class="reviewCard__contentFooter" v-if="review.date">
				<div class="reviewCard__contentLabel">Published: {{ review.date }}</div>
			</div>
		</div>
	</div>
</template>

<script setup>
defineProps({
	review: { type: Object, required: true },
});
</script>
