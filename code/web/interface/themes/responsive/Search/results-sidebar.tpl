{strip}
	{if !empty($sideRecommendations)}
		<div id="refineSearch">
			{* Narrow Results *}
			{if !empty($sideRecommendations)}
				<div class="row">
					{foreach from=$sideRecommendations item="recommendations"}
						{include file=$recommendations}
					{/foreach}
				</div>
			{/if}
		</div>
	{/if}
	{if !empty($chiliPacBooklistSearchProps)}
		{* Booklists matching the search term *}
		<div data-chilifresh-component="booklist-search-sidebar" data-props='{$chiliPacBooklistSearchProps}'></div>
	{/if}
{/strip}