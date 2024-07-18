<?php

namespace Drupal\lesroidelareno;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Commerce payment config entities.
 *
 * @ingroup lesroidelareno
 */
class CommercePaymentConfigListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Commerce payment config ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\lesroidelareno\Entity\CommercePaymentConfig $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.commerce_payment_config.edit_form',
      ['commerce_payment_config' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
