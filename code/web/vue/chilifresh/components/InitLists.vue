<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useListsStore } from '@/store/lists';
import bus from '@/eventBus';

const props = defineProps({
	bibs: {
		type: Array,
		required: true,
	},
});

const listsStore = useListsStore();

onMounted(() => {
	if (!window.ChiliPAC.token) {
		return;
	}
	listsStore.loadBibs(props.bibs);
});

function onLoadMoreBooklists() {
	listsStore.loadMoreBooklists({ bibs: props.bibs });
}

bus.on('loadMoreBooklists', onLoadMoreBooklists);

onUnmounted(() => {
	bus.off('loadMoreBooklists', onLoadMoreBooklists);
});
</script>
