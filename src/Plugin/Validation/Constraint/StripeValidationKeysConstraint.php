<?php

namespace Drupal\lesroidelareno\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the stripe access.
 *
 * @see https://www.drupal.org/docs/drupal-apis/entity-api/entity-validation-api/defining-constraints-validations-on-entities-andor-fields
 *
 * @Constraint(
 *   id = "StripeValidationKeys",
 *   label = @Translation("Stripe Validation Keys", context = "Validation"),
 *   type = "entity:commerce_payment_config"
 * )
 */
class StripeValidationKeysConstraint extends Constraint {
  public $error_mode = "Les paramettres ne correspondent au mode production";
  public $ApiError = 'ApiError : %message';
  
}