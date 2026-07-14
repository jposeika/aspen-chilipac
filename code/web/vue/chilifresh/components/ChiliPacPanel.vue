<template>
	<div class="chilipac-panel">
		<p>{{ title }}</p>
	</div>
</template>

<script>
export default {
	name: 'ChiliPacPanel',
	props: {
		title: {
			type: String,
			default: 'ChiliFresh ChiliPAC',
		},
	},
};
</script>
