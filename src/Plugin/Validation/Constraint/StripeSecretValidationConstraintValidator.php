<?php

namespace Drupal\lesroidelareno\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Stripe\Stripe as StripeLibrary;
use Stripe\Balance;
use Stripe\Exception\ApiErrorException;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @see https://www.drupal.org/docs/drupal-apis/entity-api/entity-validation-api/defining-constraints-validations-on-entities-andor-fields
 */
class StripeSecretValidationConstraintValidator extends ConstraintValidator {
  
  /**
   *
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    foreach ($value as $item) {
      $secret_key = $item->value;
      try {
        StripeLibrary::setApiKey($secret_key);
        // Make sure we use the right mode for the secret keys.
        Balance::retrieve()->offsetGet('livemode');
      }
      catch (ApiErrorException $e) {
        $this->context->addViolation($constraint->error_message, [
          '%value' => $item->value,
          '%message' => $e->getMessage()
        ]);
      }
    }
  }
  
}