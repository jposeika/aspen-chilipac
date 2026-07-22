{strip}
<div class="btn-toolbar">
	<div class="btn-group btn-group-vertical btn-block">
		{* actions *}
		{foreach from=$actions item=curAction}
			<a href="{$curAction.url}" {if !empty($curAction.target)}target="{$curAction.target}"{/if} {if !empty($curAction.id)}id="{$curAction.id}"{/if} {if !empty($curAction.onclick)}onclick="{$curAction.onclick}"{/if} {if !empty($curAction['data-needs-refresh'])}data-needs-refresh="{$curAction['data-needs-refresh']}"{/if} {if !empty($curAction['data-record-id'])}data-record-id="{$curAction['data-record-id']}"{/if} {if !empty($curAction['data-record-source'])}data-record-source="{$curAction['data-record-source']}"{/if} class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap">{if !empty($curAction.target) && $curAction.target == "_blank"}<i class="fas fa-external-link-alt" role="presentation"></i> {/if}{$curAction.title}</a>
		{/foreach}
		{if !empty($chiliPacEnabled) && !empty($chiliPacBibId)}
			<div data-chilifresh-component="booklist-button" data-props='{ldelim}"id":"{$chiliPacBibId|escape:javascript}"{rdelim}'></div>
			<div data-chilifresh-component="bookshelf-button" data-props='{ldelim}"id":"{$chiliPacBibId|escape:javascript}"{rdelim}'></div>
		{/if}
	</div>
</div>
{/strip}