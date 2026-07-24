{strip}
	{if !empty($chiliPacRecentReviews)}
		{include file="ChiliFresh/carousel.tpl" carouselId="chilipac-recent-reviews" carouselTitle="Recently Reviewed" carouselItems=$chiliPacRecentReviews}
	{/if}
	{if !empty($chiliPacRecentRatings)}
		{include file="ChiliFresh/carousel.tpl" carouselId="chilipac-recent-ratings" carouselTitle="Recently Rated" carouselItems=$chiliPacRecentRatings}
	{/if}
	{if !empty($chiliPacHighestRated)}
		{include file="ChiliFresh/carousel.tpl" carouselId="chilipac-highest-rated" carouselTitle="Highest Rated" carouselItems=$chiliPacHighestRated}
	{/if}
	{if !empty($chiliPacTrending)}
		{include file="ChiliFresh/carousel.tpl" carouselId="chilipac-trending" carouselTitle="Trending" carouselItems=$chiliPacTrending}
	{/if}
{/strip}
