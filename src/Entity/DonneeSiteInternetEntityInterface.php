<?php

namespace Drupal\lesroidelareno\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Donnee site internet des utilisateurs entities.
 *
 * @ingroup lesroidelareno
 */
interface DonneeSiteInternetEntityInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Donnee site internet des utilisateurs name.
   *
   * @return string
   *   Name of the Donnee site internet des utilisateurs.
   */
  public function getName();

  /**
   * Sets the Donnee site internet des utilisateurs name.
   *
   * @param string $name
   *   The Donnee site internet des utilisateurs name.
   *
   * @return \Drupal\lesroidelareno\Entity\DonneeSiteInternetEntityInterface
   *   The called Donnee site internet des utilisateurs entity.
   */
  public function setName($name);

  /**
   * Gets the Donnee site internet des utilisateurs creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Donnee site internet des utilisateurs.
   */
  public function getCreatedTime();

  /**
   * Sets the Donnee site internet des utilisateurs creation timestamp.
   *
   * @param int $timestamp
   *   The Donnee site internet des utilisateurs creation timestamp.
   *
   * @return \Drupal\lesroidelareno\Entity\DonneeSiteInternetEntityInterface
   *   The called Donnee site internet des utilisateurs entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Donnee site internet des utilisateurs revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Donnee site internet des utilisateurs revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\lesroidelareno\Entity\DonneeSiteInternetEntityInterface
   *   The called Donnee site internet des utilisateurs entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Donnee site internet des utilisateurs revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Donnee site internet des utilisateurs revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\lesroidelareno\Entity\DonneeSiteInternetEntityInterface
   *   The called Donnee site internet des utilisateurs entity.
   */
  public function setRevisionUserId($uid);

}
