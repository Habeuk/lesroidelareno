<?php

namespace Drupal\lesroidelareno;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Donnee site internet des utilisateurs entities.
 *
 * @ingroup lesroidelareno
 */
class DonneeSiteInternetEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Donnee site internet des utilisateurs ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\lesroidelareno\Entity\DonneeSiteInternetEntity $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.donnee_internet_entity.edit_form',
      ['donnee_internet_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
