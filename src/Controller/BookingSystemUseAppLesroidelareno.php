<?php

namespace Drupal\lesroidelareno\Controller;

use Drupal\Core\Controller\ControllerBase;
use Stephane888\Debug\Repositories\ConfigDrupal;
use Drupal\prise_rendez_vous\Entity\RdvConfigEntity;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Symfony\Component\HttpFoundation\Request;
use Drupal\lesroidelareno\Entity\CommercePaymentConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\lesroidelareno\lesroidelareno;
use Drupal\booking_system\Controller\BookingSystemUseApp;

/**
 * Ce controlleur permet de fournir les routes pour l'application de
 * resrvation de creneau ( application de base ).
 *
 * @author stephane
 *        
 */
class BookingSystemUseAppLesroidelareno extends BookingSystemUseApp {
  
  /**
   * Permet de charger la configuration par defaut.
   * ( Actuelment pour les tests ).
   */
  public function loadConfigCalandar(Request $Request) {
    $booking_config_type_id = lesroidelareno::getCurrentPrefixDomain();
    // return HttpResponse::response($configs);
    return $this->Views($Request, $booking_config_type_id);
  }
  
  /**
   * Permet de recuperer les données de configurations pour la construction des
   * creneaux.
   *
   * @param string $booking_config_type_id
   * @param string $date
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function loadConfisCreneaux($booking_config_type_id, $date) {
    return parent::loadConfisCreneaux($booking_config_type_id, $date);
  }
  
  /**
   * Enregistrer un creneau.
   *
   * @param string $booking_config_type_id
   */
  public function SaveReservation(Request $Request, string $booking_config_type_id) {
    return parent::SaveReservation($Request, $booking_config_type_id);
  }
  
}