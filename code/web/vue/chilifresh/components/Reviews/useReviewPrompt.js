import { ref, computed } from 'vue';

/**
 * The summary line under the stars is how a patron starts a review. Reviewing needs a ChiliPAC
 * session, so a logged out patron gets Aspen's login modal instead; the page reloads on success
 * and picks the token up, the same way the booklist and bookshelf buttons work.
 */
export function useReviewPrompt() {
	const showReviewModal = ref(false);
	const hasToken = computed(() => !!window.ChiliPAC?.token);

	function promptForReview() {
		if (!hasToken.value) {
			window.AspenDiscovery.Account.ajaxLogin();
			return;
		}
		showReviewModal.value = true;
	}

	return {
		showReviewModal,
		promptForReview,
	};
}
