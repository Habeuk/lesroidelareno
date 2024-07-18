<?php
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\wbumenudomain\WbumenudomainMenuItemDecorating;
use Drupal\views\ViewExecutable;

/**
 * implement hook_views_query_alter
 *
 * @param ViewExecutable $view
 * @param Drupal\views\Plugin\views\query\QueryPluginBase $query
 */
function lesroidelareno_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {
  if ($view->id() == 'produits_simialires') {
    _lesroidelareno_viewquery($query);
  }
}

function _lesroidelareno_viewquery(QueryPluginBase &$query) {
  $configuration = array(
    'type' => 'LEFT',
    'table' => 'commerce_product__4aa4f981fa',
    'field' => 'entity_id',
    'left_table' => 'commerce_product_field_data',
    'left_field' => 'product_id',
    'operator' => '='
  );
  $join = \Drupal\views\Views::pluginManager('join')->createInstance('standard', $configuration);
  $rel = $query->addRelationship('kksa888', $join, 'node_field_data');
  // $query->addTable('commerce_product__4aa4f981fa', $rel, $join, 'kksa888');
  $activeDomain = WbumenudomainMenuItemDecorating::getCurrentActiveDomaineByUrl();
  if ('lesroisdelareno_fr' == $activeDomain || 'v2lesroisdelareno_kksa' == $activeDomain)
    $query->addWhere('grp1', 'kksa888.field_en_promotion_sur_les_roide_value', 1, '=');
  $query->addWhere('grp1', 'commerce_product_field_data.field_domain_access', WbumenudomainMenuItemDecorating::getCurrentActiveDomaineByUrl(), '=');
  $query->setWhereGroup('OR', 'grp1');
}
