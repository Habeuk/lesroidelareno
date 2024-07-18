<?php

namespace Drupal\lesroidelareno;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\lesroidelareno\Entity\CommercePaymentConfigInterface;

/**
 * Defines the storage handler class for Commerce payment config entities.
 *
 * This extends the base storage class, adding required special handling for
 * Commerce payment config entities.
 *
 * @ingroup lesroidelareno
 */
interface CommercePaymentConfigStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Commerce payment config revision IDs for a specific Commerce payment config.
   *
   * @param \Drupal\lesroidelareno\Entity\CommercePaymentConfigInterface $entity
   *   The Commerce payment config entity.
   *
   * @return int[]
   *   Commerce payment config revision IDs (in ascending order).
   */
  public function revisionIds(CommercePaymentConfigInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Commerce payment config author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Commerce payment config revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

}
