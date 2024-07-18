<?php

namespace Drupal\lesroidelareno\Controller;

use Drupal\booking_system\Controller\BookingSystemConfigResumeController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for booking_system routes.
 */
class BSAutoEcoleConfigResumeController extends BookingSystemConfigResumeController {
  
  /**
   * Permet d'afficher le resume d'une configuration.
   * - Afficher les dates et heures desactivés.
   * - Afficher egalement les equipes.
   */
  public function ConfigResume(Request $Request, $booking_config_type_id) {
    $build = parent::ConfigResume($Request, $booking_config_type_id);
    // On modifie la route pour : booking_configs
    if (!empty($build['booking_configs'][0]['#url'])) {
      // $build['booking_configs'][0]['#url'] =
      // Url::fromRoute("entity.booking_config_type.edit_form", [
      // 'booking_config_type' => $booking_config_type_id
      // ], [
      // 'query' => [
      // 'destination' => $Request->getPathInfo()
      // ]
      // ]);
    }
    return $build;
  }
  
}