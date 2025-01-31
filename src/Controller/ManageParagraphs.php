<?php

declare(strict_types=1);

namespace Drupal\lesroidelareno\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for lesroidelareno routes.
 */
final class ManageParagraphs extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(): array {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
