<?php

/**
 * @file
 * Contains donnee_internet_entity.page.inc.
 *
 * Page callback for Donnee site internet des utilisateurs entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Donnee site internet des utilisateurs templates.
 *
 * Default template: donnee_internet_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_donnee_internet_entity(array &$variables) {
  // Fetch DonneeSiteInternetEntity Entity Object.
  $donnee_internet_entity = $variables['elements']['#donnee_internet_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
