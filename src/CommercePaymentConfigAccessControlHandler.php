<?php

namespace Drupal\lesroidelareno;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Commerce payment config entity.
 *
 * @see \Drupal\lesroidelareno\Entity\CommercePaymentConfig.
 */
class CommercePaymentConfigAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\lesroidelareno\Entity\CommercePaymentConfigInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished commerce payment config entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published commerce payment config entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit commerce payment config entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete commerce payment config entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add commerce payment config entities');
  }


}
