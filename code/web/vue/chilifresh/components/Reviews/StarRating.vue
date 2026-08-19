<template>
	<span class="chilipac-stars" :class="{ 'chilipac-stars-muted': muted }" role="img" :aria-label="label">
		<span class="ui-rater-starsOff" :style="{ width: width + 'px' }">
			<span class="ui-rater-starsOn" :style="{ width: onWidth + 'px' }"></span>
		</span>
	</span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
	rating: { type: Number, default: 0 },
	width: { type: Number, default: 90 },
	label: { type: String, default: '' },
	// Dims the stars for a title ChiliFresh has no rating for, so an unrated title reads
	// differently from one rated zero
	muted: { type: Boolean, default: false },
});

// Reuses Aspen's own star sprite so ChiliFresh ratings look native
const onWidth = computed(() => (props.width * Math.min(Math.max(props.rating, 0), 5)) / 5);
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
