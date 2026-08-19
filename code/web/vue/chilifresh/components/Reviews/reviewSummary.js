// The line shown under the stars on both the search results and the record page.
// With no reviews yet it invites the patron to add the first one.
export function reviewSummary(reviewCount) {
	const count = Number(reviewCount || 0);
	if (count < 1) {
		return 'Rate or review';
	}
	return count === 1 ? '1 review' : `${count} reviews`;
}

// Review dates come back as plain date strings; fall back to the raw value if unparseable.
export function formatReviewDate(date) {
	const parsed = new Date(date);
	if (isNaN(parsed.getTime())) {
		return date;
	}
	return parsed.toLocaleDateString('en-US', {
		month: 'long',
		day: 'numeric',
		year: 'numeric',
	});
}
