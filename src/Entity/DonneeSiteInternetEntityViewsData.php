<?php

namespace Drupal\lesroidelareno\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Donnee site internet des utilisateurs entities.
 */
class DonneeSiteInternetEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
