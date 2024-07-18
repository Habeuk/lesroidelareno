<?php

namespace Drupal\lesroidelareno;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class CommercePaymentConfigStorage extends SqlContentEntityStorage implements CommercePaymentConfigStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(CommercePaymentConfigInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {commerce_payment_config_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {commerce_payment_config_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

}
