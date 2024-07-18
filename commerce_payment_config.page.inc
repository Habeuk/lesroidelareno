<?php

/**
 * @file
 * Contains commerce_payment_config.page.inc.
 *
 * Page callback for Commerce payment config entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Commerce payment config templates.
 *
 * Default template: commerce_payment_config.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_commerce_payment_config(array &$variables) {
  // Fetch CommercePaymentConfig Entity Object.
  $commerce_payment_config = $variables['elements']['#commerce_payment_config'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
