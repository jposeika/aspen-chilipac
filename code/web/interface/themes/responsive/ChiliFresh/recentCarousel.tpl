{strip}
	<div class="chilipac-carousel row">
		<div class="col-xs-12">
			<div class="browse-category-group-heading">
				<h2>{translate text=$carouselTitle isPublicFacing=true}</h2>
			</div>
			<div class="swiper" id="swiper-{$carouselId}">
				<div class="swiper-navigation-container">
					<div class="swiper-button-prev"></div>
				</div>
				<div class="swiper-wrapper">
					{foreach from=$carouselItems item=carouselItem}
						<div class="swiper-slide browse-thumbnail">
							<a href="/{$chiliPacRecordUrlComponent}/{$carouselItem.bib_id|escape:url}/Home" title="{$carouselItem.title|escape}{if !empty($carouselItem.author)} by {$carouselItem.author|escape}{/if}">
								<img src="/bookcover.php?id={$chiliPacRecordSource|escape:url}:{$carouselItem.bib_id|escape:url}&amp;size=medium{if !empty($carouselItem.isbn)}&amp;isn={$carouselItem.isbn|escape:url}{/if}" alt="{$carouselItem.title|escape}{if !empty($carouselItem.author)} by {$carouselItem.author|escape}{/if}" loading="lazy">
							</a>
						</div>
					{/foreach}
				</div>
				<div class="swiper-navigation-container">
					<div class="swiper-button-next"></div>
				</div>
			</div>
		</div>
	</div>
{/strip}
<script type="text/javascript">
	$(function () {ldelim}
		new Swiper('#swiper-{$carouselId}', {ldelim}
			slidesPerView: 7,
			slidesPerGroup: 7,
			breakpoints: {ldelim}
				1: {ldelim}slidesPerView: 3, slidesPerGroup: 3{rdelim},
				640: {ldelim}slidesPerView: 4, slidesPerGroup: 4{rdelim},
				1024: {ldelim}slidesPerView: 5, slidesPerGroup: 5{rdelim},
				1300: {ldelim}slidesPerView: 6, slidesPerGroup: 6{rdelim},
				1600: {ldelim}slidesPerView: 7, slidesPerGroup: 7{rdelim}
			{rdelim},
			spaceBetween: 20,
			direction: 'horizontal',
			a11y: {ldelim}enabled: true{rdelim},
			navigation: {ldelim}
				nextEl: '#swiper-{$carouselId} .swiper-button-next',
				prevEl: '#swiper-{$carouselId} .swiper-button-prev'
			{rdelim}
		{rdelim});
	{rdelim});
</script>
