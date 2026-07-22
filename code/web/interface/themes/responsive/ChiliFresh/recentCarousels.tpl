{strip}
	{if !empty($chiliPacRecentReviews)}
		{include file="ChiliFresh/recentCarousel.tpl" carouselId="chilipac-recent-reviews" carouselTitle="Recently Reviewed" carouselItems=$chiliPacRecentReviews}
	{/if}
	{if !empty($chiliPacRecentRatings)}
		{include file="ChiliFresh/recentCarousel.tpl" carouselId="chilipac-recent-ratings" carouselTitle="Recently Rated" carouselItems=$chiliPacRecentRatings}
	{/if}
{/strip}
