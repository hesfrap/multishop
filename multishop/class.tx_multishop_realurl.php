<?php
class tx_multishop_realurl {

	/**
	 * Generates additional RealURL configuration and merges it with provided configuration
	 *
	 * @paramarray$paramsDefault configuration
	 * @paramtx_realurl_autoconfgen$pObjParent object
	 * @returnarrayUpdated configuration
	 */

	function addConfig($params, &$pObj) {

			//add custom config
		$config = array (
			'preVars' => array(
				array (
					'GETvar' => 'type',
					'valueMap' => array (
						'multishop' => 2002,
						'multishop_admin' => 2003,
					),
					'noMatch' => 'bypass'
				),		
			),
			'postVarSets' => array (
				array(
					'GETvar' => 'type',
					'valueMap' => array(
					),
				),									
				'_DEFAULT' => array (
					/******* MULTISHOP BEGIN **/
					// checkout-section
					'checkout-section' => array (
						array(
							'GETvar' => 'tx_multishop_pi1[previous_checkout_section]',
						),
					),
					// section
					'section' => array (
						array(
							'GETvar' => 'tx_multishop_pi1[page_section]',
						),						
						array(
							'GETvar' => 'categories_id[0]',
							'lookUpTable' => array (
								'table' => 'tx_multishop_categories_description',
								'id_field' => 'categories_id',
								'alias_field' => 'categories_name',
								'useUniqueCache' => 1,
								'useUniqueCache_conf' => array (
									'strtolower' => 1,
									'spaceCharacter' => '-',
								),
							),
						),
						array(
							'GETvar' => 'categories_id[1]',
							'lookUpTable' => array (
								'table' => 'tx_multishop_categories_description',
								'id_field' => 'categories_id',
								'alias_field' => 'categories_name',
								'useUniqueCache' => 1,
								'useUniqueCache_conf' => array (
									'strtolower' => 1,
									'spaceCharacter' => '-',
								),
							),
						),
						array(
							'GETvar' => 'categories_id[2]',
							'lookUpTable' => array (
								'table' => 'tx_multishop_categories_description',
								'id_field' => 'categories_id',
								'alias_field' => 'categories_name',
								'useUniqueCache' => 1,
								'useUniqueCache_conf' => array (
									'strtolower' => 1,
									'spaceCharacter' => '-',
								),
							),
						),
						array(
							'GETvar' => 'categories_id[3]',
							'lookUpTable' => array (
								'table' => 'tx_multishop_categories_description',
								'id_field' => 'categories_id',
								'alias_field' => 'categories_name',
								'useUniqueCache' => 1,
								'useUniqueCache_conf' => array (
									'strtolower' => 1,
									'spaceCharacter' => '-',
								),
							),
						),
						array(
							'GETvar' => 'categories_id[4]',
							'lookUpTable' => array (
								'table' => 'tx_multishop_categories_description',
								'id_field' => 'categories_id',
								'alias_field' => 'categories_name',
								'useUniqueCache' => 1,
								'useUniqueCache_conf' => array (
									'strtolower' => 1,
									'spaceCharacter' => '-',
								),
							),
						),
						array(
							'GETvar' => 'categories_id[5]',
							'lookUpTable' => array (
								'table' => 'tx_multishop_categories_description',
								'id_field' => 'categories_id',
								'alias_field' => 'categories_name',
								'useUniqueCache' => 1,
								'useUniqueCache_conf' => array (
									'strtolower' => 1,
									'spaceCharacter' => '-',
								),
							),
						),						
						array(
							'GETvar' => 'products_id',
							'lookUpTable' => array (
								'table' => 'tx_multishop_products_description',
								'id_field' => 'products_id',
								'alias_field' => 'products_name',
								'useUniqueCache' => 1,
								'useUniqueCache_conf' => array (
									'strtolower' => 1,
									'spaceCharacter' => '-',
								),
							),
						),
					
					),
					/******* MULTISHOP END **/
				),
			),
			'fileName' => array (
				'index' => array(
					/******* MULTISHOP BEGIN **/
					'multishop.html' => array(
						'keyValues' => array(
							'type' => 2002,
						),
					),
					'multishop_admin.html' => array(
						'keyValues' => array(
							'type' => 2003,
						),
					),
					/******* MULTISHOP END **/
				),
			),
		);
		return array_merge_recursive($params['config'], $config);
	}
}
?>