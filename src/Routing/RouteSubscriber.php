<?php

namespace Drupal\lesroidelareno\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {
  
  /**
   *
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change la methode de traitement de la route : system.entity_autocomplete
    // afin d'ajouter le fitre par domaine.
    if ($route = $collection->get('system.entity_autocomplete')) {
      $route->setDefault('_controller', '\Drupal\lesroidelareno\Services\EntityReferenceAutocomplete::handleAutocompleteCustom');
    }
    
    foreach ($collection as $name => $route) {
      /**
       *
       * @var \Symfony\Component\Routing\Route $route
       */
      $defaults = $route->getDefaults();
      if (!empty($defaults['_is_jsonapi']) && !empty($defaults['resource_type'])) {
        $methods = $route->getMethods();
        // on ne permet aucune MAJ via JSONAPI.
        if (in_array('DELETE', $methods) || in_array('POST', $methods) || in_array('PATCH', $methods)) {
          // We never want to delete data, only unpublish.
          $collection->remove($name);
        }
        // Pour toutes les demandes get les redirigées sur cette route.
        // le traitement se fait via un hook ou un event.
      }
      // Ajouter la possibilite au proproitaire de pouvoir modifier la
      // configuration du layout. (il doit aussi avoir access au contenu en
      // question).
      elseif ($name == 'layout_builder.overrides.paragraph.view' || $name == 'entity.paragraph.canonical') {
      /**
       * Le requirementes est ajouté mais l'utilisateur n'a pas access à la
       * ressource, du à un autre role definie en amont.
       * (_layout_builder_access ). (On est passe pas le UI pour configurer
       * chaque bloc ).
       */
        // $requirements = [
        // '_role' => "gerant_de_site_web+administrator"
        // ];
        // $route->addRequirements($requirements);
      }
      // else {
      // // dump($defaults);
      // }
      // elseif (!empty($defaults['_entity_form']) && $defaults['_entity_form']
      // == 'paragraph.layout_builder') {
      // $requirements = [
      // '_role' => "gerant_de_site_web+administrator"
      // ];
      // $route->addRequirements($requirements);
      // $collection->addRequirements($requirements);
      // }
    }
  }
  
}