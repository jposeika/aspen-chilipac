<template>
	<Teleport to="body">
		<div class="chilipac-overlay" @click.self="close">
			<div class="chilipac-dialog" role="dialog" aria-labelledby="chilipacReviewTitle">
				<div class="modal-header">
					<button type="button" class="close" @click.prevent="close">&times;</button>
					<h4 class="modal-title" id="chilipacReviewTitle">Add a Review</h4>
				</div>

				<div class="modal-body" v-if="saved">
					<p>Your review has been posted.</p>
				</div>
				<div class="modal-footer" v-if="saved">
					<button type="button" class="btn btn-primary" @click.prevent="close">Close</button>
				</div>

				<form @submit.prevent="postReview" v-else>
					<div class="modal-body">
						<div class="alert alert-danger" v-if="error">{{ error }}</div>

						<div class="form-group">
							<label for="chilipacReviewRating">Your rating</label>
							<div>
								<StarRatingInput id="chilipacReviewRating" v-model="form.rating" />
							</div>
						</div>

						<div class="form-group" :class="{ 'has-error': titleError }">
							<label for="chilipacReviewHeadline">Title</label>
							<input
								type="text"
								class="form-control"
								id="chilipacReviewHeadline"
								v-model="form.title"
								maxlength="255"
							/>
							<span class="help-block" v-if="titleError">Please give your review a title.</span>
						</div>

						<div class="form-group" :class="{ 'has-error': reviewError }">
							<label for="chilipacReviewText">Review</label>
							<textarea
								class="form-control"
								id="chilipacReviewText"
								rows="6"
								v-model="form.review"
							></textarea>
							<span class="help-block" v-if="reviewError">Please write your review.</span>
						</div>

						<div class="form-group">
							<label for="chilipacReviewSuggest">Recommend to a friend?</label>
							<select
								class="form-control"
								id="chilipacReviewSuggest"
								v-model="form.suggest"
							>
								<option value="y">Yes</option>
								<option value="n">No</option>
							</select>
						</div>
					</div>

					<div class="modal-footer">
						<button type="button" class="btn btn-default" @click.prevent="close">Cancel</button>
						<button type="submit" class="btn btn-primary" :disabled="saving">Post Review</button>
					</div>
				</form>
			</div>
		</div>
	</Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import api from '@/api/chilipac';
import bus from '@/eventBus';
import StarRatingInput from './StarRatingInput.vue';

const props = defineProps({
	// ChiliFresh matches reviews to titles by ISBN rather than bib id
	isbn: { type: String, required: true },
});
const emit = defineEmits(['close', 'saved']);

const saving = ref(false);
const saved = ref(false);
const error = ref(null);
const titleError = ref(false);
const reviewError = ref(false);

const form = ref({
	isbn: props.isbn,
	rating: 5,
	title: null,
	review: null,
	suggest: 'y',
});

function close() {
	emit('close');
}

async function postReview() {
	titleError.value = !form.value.title;
	reviewError.value = !form.value.review;
	if (titleError.value || reviewError.value) {
		return;
	}

	saving.value = true;
	error.value = null;
	try {
		await api.reviews().createReview(form.value);
		saved.value = true;
		emit('saved');
		// The reviews panel is mounted separately, so tell it to pick the new review up
		bus.emit('reviewPosted');
	} catch (e) {
		error.value = e.response?.status === 409
			? 'You have already posted a review for this title.'
			: 'Sorry, your review could not be posted. Please try again later.';
	} finally {
		saving.value = false;
	}
}

onMounted(() => { document.body.style.overflow = 'hidden'; });
onUnmounted(() => { document.body.style.overflow = ''; });
</script>

<style scoped>
.chilipac-overlay {
	position: fixed;
	inset: 0;
	background: rgba(0, 0, 0, 0.5);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 1050;
}

.chilipac-dialog {
	background: #fff;
	border-radius: 4px;
	width: 100%;
	max-width: 500px;
	max-height: 90vh;
	overflow-y: auto;
	box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
}
</style>
