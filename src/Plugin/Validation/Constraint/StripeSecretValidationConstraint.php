<?php

namespace Drupal\lesroidelareno\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Stripe\Stripe as StripeLibrary;
use Stripe\Balance;
use Stripe\Exception\ApiErrorException;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @see https://www.drupal.org/docs/drupal-apis/entity-api/entity-validation-api/defining-constraints-validations-on-entities-andor-fields
 *
 * @Constraint(
 *   id = "StripeSecretValidation",
 *   label = @Translation("Stripe secret validation", context = "Validation"),
 *   type = "string"
 * )
 */
class StripeSecretValidationConstraint extends Constraint {
  // The message that will be shown if the value is not unique.
  public $error_message = 'key %value is not valide; %message';
  
}