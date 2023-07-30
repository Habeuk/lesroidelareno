<?php

namespace Drupal\lesroidelareno\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\lesroidelareno\lesroidelareno;

/**
 * Defines a local action plugin with a dynamic title.
 */
class AddItemMenu extends MenuLinkAdd {
  
  public function getRouteName() {
    // return $this->pluginDefinition['route_name'];
    return "lesroidelareno.manage_menu.add";
  }
  
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    return $options;
  }
  
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $parameters = parent::getRouteParameters($route_match);
    $key = "third_party_settings.lesroidelareno.domain_id";
    $ids = lesroidelareno::retriveDataByKey($key);
    $parameters['menu'] = $ids ? reset($ids) : '';
    if (empty($ids)) {
      dump($ids);
      dd(lesroidelareno::getCacheAPCu());
    }
    return $parameters;
  }
  
  // public function getTitle(){
  // /
  // }
}