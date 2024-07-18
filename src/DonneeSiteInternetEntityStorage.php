<?php

namespace Drupal\lesroidelareno;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\lesroidelareno\Entity\DonneeSiteInternetEntityInterface;

/**
 * Defines the storage handler class for Donnee site internet des utilisateurs entities.
 *
 * This extends the base storage class, adding required special handling for
 * Donnee site internet des utilisateurs entities.
 *
 * @ingroup lesroidelareno
 */
class DonneeSiteInternetEntityStorage extends SqlContentEntityStorage implements DonneeSiteInternetEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(DonneeSiteInternetEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {donnee_internet_entity_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {donnee_internet_entity_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

}
