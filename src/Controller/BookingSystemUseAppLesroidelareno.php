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
  protected static $default_id = 'wb_horizon_com';
  
  /**
   * Permet de charger la configuration par defaut.
   */
  public function loadConfigCalandar(Request $Request) {
    $entity_type_id = "booking_config_type";
    $booking_config_type_id = lesroidelareno::getCurrentPrefixDomain();
    $entityConfig = $this->entityTypeManager()->getStorage($entity_type_id)->load($booking_config_type_id);
    if (!$booking_config_type_id || !$entityConfig) {
      /**
       * Pour la configuration par defaut.
       */
      $booking_config_type_id = self::$default_id;
    }
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
   * Permet de generer et de configurer RDV par domaine.
   */
  public function ConfigureDefault() {
    $entity_type_id = "booking_config_type";
    $id = lesroidelareno::getCurrentPrefixDomain();
    if (!$id) {
      /**
       * Pour la configuration par defaut.
       */
      $id = self::$default_id;
    }
    $entityConfig = $this->entityTypeManager()->getStorage($entity_type_id)->load($id);
    if (!$entityConfig) {
      $entityConfig = $this->entityTypeManager()->getStorage($entity_type_id)->create([
        'id' => $id,
        'label' => 'Configuration des creneaux',
        'days' => \Drupal\booking_system\DaysSettingsInterface::DAYS
      ]);
      $entityConfig->save();
    }
    
    // dd($entityConfig->toArray());
    // $entityConfig->save();
    
    $form = $this->entityFormBuilder()->getForm($entityConfig, "edit", [
      'redirect_route' => 'lesroidelareno.booking_system.config_resume',
      'booking_config_type_id' => $entityConfig->id()
    ]);
    return $form;
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