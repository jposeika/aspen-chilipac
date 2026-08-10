{* Rendered by services/Booklist/DownloadPDF.php and converted to a PDF by dompdf.
   dompdf has no flexbox or grid support, so the two column layout is a table.
   A fixed position header repeats on every page; the page margin reserves room
   for it, which is why $headerHeight is calculated server side. *}
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<style>
		@page {
			margin: {$pageMarginTop}mm 12mm 12mm 12mm;
		}

		body {
			font-family: "DejaVu Sans", sans-serif;
			font-size: 9pt;
			color: #222;
			margin: 0;
		}

		table {
			border-collapse: collapse;
		}

		#pdfHeader {
			position: fixed;
			top: -{$headerOffset}mm;
			left: 0;
			right: 0;
			height: {$headerHeight}mm;
			border-bottom: 1px solid #999;
		}

		#pdfHeader h1 {
			font-size: 13pt;
			margin: 0;
		}

		#pdfHeader .headerDescription {
			margin: 2mm 0 0 0;
			font-size: 8.5pt;
			color: #555;
			line-height: 1.35;
		}

		#pdfHeader .headerAuthor {
			margin: 1.5mm 0 0 0;
			font-size: 8.5pt;
			color: #555;
			text-align: right;
		}

		.itemCell {
			width: 50%;
			padding: 0 4mm 6mm 0;
			vertical-align: top;
		}

		.itemCoverCell {
			width: 22mm;
			padding: 0 3mm 0 0;
			vertical-align: top;
		}

		.itemCover {
			width: 22mm;
		}

		.itemTextCell {
			vertical-align: top;
		}

		.itemTitle {
			font-weight: bold;
			font-size: 9.5pt;
			line-height: 1.25;
		}

		.itemAuthor {
			margin-top: 1mm;
			color: #555;
			line-height: 1.25;
		}

		.noItems {
			color: #555;
		}
	</style>
</head>
<body>
	<div id="pdfHeader">
		<h1>{$booklistName|escape}</h1>
		{if !empty($booklistDescription)}
			<div class="headerDescription">{$booklistDescription|escape}</div>
		{/if}
		{if !empty($booklistAuthor)}
			<div class="headerAuthor">
				{translate text="Created by" isPublicFacing=true} <strong>{$booklistAuthor|escape}</strong>
			</div>
		{/if}
	</div>

	{if !empty($booklistItems)}
		<table width="100%">
			{foreach from=$booklistItems item=item name=items key=index}
				{if $index % 2 == 0}<tr>{/if}
				<td class="itemCell">
					<table>
						<tr>
							<td class="itemCoverCell">
								{if !empty($item.cover)}
									<img class="itemCover" src="{$item.cover}" alt="">
								{/if}
							</td>
							<td class="itemTextCell">
								<div class="itemTitle">{$item.title|escape}</div>
								{if !empty($item.author)}
									<div class="itemAuthor">{$item.author|escape}</div>
								{/if}
							</td>
						</tr>
					</table>
				</td>
				{if $index % 2 == 1 || $smarty.foreach.items.last}
					{if $index % 2 == 0}<td class="itemCell"></td>{/if}
					</tr>
				{/if}
			{/foreach}
		</table>
	{else}
		<p class="noItems">{translate text="This booklist does not have any items yet." isPublicFacing=true}</p>
	{/if}
</body>
</html>
