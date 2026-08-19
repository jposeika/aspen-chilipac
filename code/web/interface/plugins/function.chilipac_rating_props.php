<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {chilipac_rating_props} function plugin
 *
 * Name:     chilipac_rating_props<br>
 * Purpose:  build the props for a ChiliFresh rating component from a grouped work permanent id.
 *
 * ChiliPAC identifies titles by ILS bib id, so the permanent id is resolved through
 * ChilipacApi (which memoizes lookups for the request). Templates that already have a
 * chiliPacBibMap assigned, such as search results, are served from that map instead.
 *
 * When a record driver is passed its ISBNs are included so ChiliFresh can total ratings
 * across every edition in the grouped work rather than the primary record alone.
 *
 * @param array $params Requires 'id'; optional 'recordDriver' and 'assign'.
 * @param UInterface $smarty
 * @return null|string The encoded props, or an empty string when the work has no bib id.
 */
function smarty_function_chilipac_rating_props($params, &$smarty) {
	$props = '';
	if (!empty($params['id'])) {
		$permanentId = $params['id'];

		$bibId = '';
		$bibMap = $smarty->getVariable('chiliPacBibMap');
		if (!empty($bibMap[$permanentId])) {
			$bibId = $bibMap[$permanentId];
		} else {
			require_once ROOT_DIR . '/sys/Enrichment/ChilipacApi.php';
			$lookedUp = ChilipacApi::getBibMapForGroupedWorks([$permanentId]);
			if (!empty($lookedUp[$permanentId])) {
				$bibId = $lookedUp[$permanentId];
			}
		}

		if (!empty($bibId)) {
			$propsData = ['id' => (string)$bibId];
			if (!empty($params['recordDriver']) && method_exists($params['recordDriver'], 'getISBNs')) {
				$isbns = $params['recordDriver']->getISBNs();
				if (!empty($isbns) && is_array($isbns)) {
					$propsData['isbns'] = array_values(array_unique($isbns));
				}
			}
			$props = json_encode($propsData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
		}
	}

	if (!isset($params['assign'])) {
		return $props;
	} else {
		$smarty->assign($params['assign'], $props);
	}
	return null;
}
