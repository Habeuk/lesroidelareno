<?php

namespace Drupal\lesroidelareno;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface DonneeSiteInternetEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Donnee site internet des utilisateurs revision IDs for a specific Donnee site internet des utilisateurs.
   *
   * @param \Drupal\lesroidelareno\Entity\DonneeSiteInternetEntityInterface $entity
   *   The Donnee site internet des utilisateurs entity.
   *
   * @return int[]
   *   Donnee site internet des utilisateurs revision IDs (in ascending order).
   */
  public function revisionIds(DonneeSiteInternetEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Donnee site internet des utilisateurs author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Donnee site internet des utilisateurs revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

}
