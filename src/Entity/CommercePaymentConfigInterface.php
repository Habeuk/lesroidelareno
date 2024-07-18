<?php

namespace Drupal\lesroidelareno\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Commerce payment config entities.
 *
 * @ingroup lesroidelareno
 */
interface CommercePaymentConfigInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Commerce payment config name.
   *
   * @return string
   *   Name of the Commerce payment config.
   */
  public function getName();

  /**
   * Sets the Commerce payment config name.
   *
   * @param string $name
   *   The Commerce payment config name.
   *
   * @return \Drupal\lesroidelareno\Entity\CommercePaymentConfigInterface
   *   The called Commerce payment config entity.
   */
  public function setName($name);

  /**
   * Gets the Commerce payment config creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Commerce payment config.
   */
  public function getCreatedTime();

  /**
   * Sets the Commerce payment config creation timestamp.
   *
   * @param int $timestamp
   *   The Commerce payment config creation timestamp.
   *
   * @return \Drupal\lesroidelareno\Entity\CommercePaymentConfigInterface
   *   The called Commerce payment config entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Commerce payment config revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Commerce payment config revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\lesroidelareno\Entity\CommercePaymentConfigInterface
   *   The called Commerce payment config entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Commerce payment config revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Commerce payment config revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\lesroidelareno\Entity\CommercePaymentConfigInterface
   *   The called Commerce payment config entity.
   */
  public function setRevisionUserId($uid);

}
