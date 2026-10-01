<template>
	<div class="chilipac-rating-input" @mouseleave="hovered = 0">
		<span class="ui-rater-starsOff" :style="{ width: STARS_WIDTH + 'px' }">
			<span
				class="ui-rater-starsOn"
				:class="{ userRated: hovered > 0 }"
				:style="{ width: onWidth + 'px' }"
			></span>
		</span>
		<button
			v-for="star in 5"
			:key="star"
			type="button"
			class="chilipac-rating-input__star"
			:style="{ left: (star - 1) * STAR_WIDTH + 'px', width: STAR_WIDTH + 'px' }"
			:aria-label="star === 1 ? '1 star' : star + ' stars'"
			:aria-pressed="modelValue === star"
			@click="emit('update:modelValue', star)"
			@mouseenter="hovered = star"
			@focus="hovered = star"
			@blur="hovered = 0"
		></button>
	</div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
	modelValue: { type: Number, default: 0 },
});
const emit = defineEmits(['update:modelValue']);

// Aspen's star sprite repeats on the x axis every 18px and cannot be scaled, so a star is
// always 18px and the row is always five of them. See StarRating.vue
const STAR_WIDTH = 18;
const STARS_WIDTH = STAR_WIDTH * 5;

const hovered = ref(0);

// Hovering previews the rating under the cursor without committing to it
const onWidth = computed(() => (hovered.value || props.modelValue) * STAR_WIDTH);
</script>

<style>
.chilipac-rating-input {
	position: relative;
	display: inline-block;
	line-height: 0;
}

/* Transparent hit targets sitting over the sprite, one per star */
.chilipac-rating-input__star {
	position: absolute;
	top: 0;
	height: 18px;
	padding: 0;
	border: 0;
	background: transparent;
	cursor: pointer;
}

.chilipac-rating-input__star:focus {
	outline: 1px dotted #333;
}
</style>
