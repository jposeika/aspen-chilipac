<template>
	<span class="chilipac-stars" :class="{ 'chilipac-stars-muted': muted }" role="img" :aria-label="label">
		<span class="ui-rater-starsOff" :style="{ width: STARS_WIDTH + 'px' }">
			<span class="ui-rater-starsOn" :style="{ width: onWidth + 'px' }"></span>
		</span>
	</span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
	rating: { type: Number, default: 0 },
	label: { type: String, default: '' },
	// Dims the stars for a title ChiliFresh has no rating for, so an unrated title reads
	// differently from one rated zero
	muted: { type: Boolean, default: false },
});

// Reuses Aspen's own star sprite so ChiliFresh ratings look native. The sprite repeats on the
// x axis every 18px, so the width has to be exactly five stars' worth; anything else cuts the
// row short. That makes it a constant rather than a prop.
const STARS_WIDTH = 90;

const onWidth = computed(() => (STARS_WIDTH * Math.min(Math.max(props.rating, 0), 5)) / 5);
</script>

<style>
.chilipac-stars {
	display: inline-block;
	line-height: 0;
}

.chilipac-stars-muted {
	opacity: 0.35;
}
</style>
