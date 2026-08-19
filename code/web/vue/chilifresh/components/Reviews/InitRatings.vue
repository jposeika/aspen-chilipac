<script setup>
import { onMounted } from 'vue';
import { useReviewsStore } from '@/store/reviews';

// Mounted once per results page. Loads the ratings for every result in a single request so the
// individual rating widgets can render from the store.
const props = defineProps({
	works: {
		type: Object,
		required: true,
	},
});

const reviewsStore = useReviewsStore();

onMounted(() => reviewsStore.loadWorkRatings(props.works));
</script>
