<?php

namespace Drupal\lesroidelareno\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\lesroidelareno\Entity\CommercePaymentConfig;
use Stripe\Stripe as StripeLibrary;
use Stripe\Balance;
use Stripe\Exception\ApiErrorException;

/**
 * Validates the Stripe access constraint.
 *
 * @see https://www.drupal.org/docs/drupal-apis/entity-api/entity-validation-api/defining-constraints-validations-on-entities-andor-fields
 */
class StripeValidationKeysConstraintValidator extends ConstraintValidator {
  
  /**
   *
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    try {
      /** @var CommercePaymentConfig $entity */
      if (!empty($entity->get('secret_key')->value)) {
        
        $expected_livemode = $entity->getMode() == 'live' ? TRUE : FALSE;
        $secret_key = $entity->getSecretKey();
        StripeLibrary::setApiKey($secret_key);
        // Make sure we use the right mode for the secret keys.
        Balance::retrieve()->offsetGet('livemode');
        if (Balance::retrieve()->offsetGet('livemode') != $expected_livemode) {
          $this->context->addViolation($constraint->error_mode);
        }
      }
    }
    catch (ApiErrorException $e) {
      $this->context->addViolation($constraint->ApiError, [
        '%message' => $e->getMessage()
      ]);
    }
  }
  
}