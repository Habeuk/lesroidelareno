<?php

namespace Drupal\lesroidelareno\Services\Payements;

use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 *
 * @author stephane
 *        
 */
class PayementGateWay {
  
  /**
   * Name of the entity's weight field or FALSE if no field is provided.
   *
   * @var string|bool
   */
  protected $weightKey = TRUE;
  
  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;
  
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityTypeManager;
  
  /**
   *
   * @var EntityFormBuilder
   */
  protected $EntityFormBuilder;
  
  function __construct(DomainNegotiatorInterface $negotiator, EntityFieldManager $entity_type_manager, EntityFormBuilder $EntityFormBuilder) {
    $this->negotiator = $negotiator;
    $this->entityTypeManager = $entity_type_manager;
    $this->EntityFormBuilder = $EntityFormBuilder;
  }
  
  /**
   * Recupere la liste des paressereles valides pour le client.
   */
  public function lists() {
    $gateWays = PaymentGateway::loadMultiple();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $entity */
    $payment_gateway_plugin = $entity->getPlugin();
    $type = $payment_gateway_plugin->getLabel();
    $modes = $payment_gateway_plugin->getSupportedModes();
    $mode = $modes[$payment_gateway_plugin->getMode()] ?? $this->t('N/A');
    $status = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    $row['label'] = $entity->label();
    $row['id']['#markup'] = $entity->id();
    // $this->weightKey determines whether the table will be rendered as a form.
    if (!empty($this->weightKey)) {
      $row['plugin']['#markup'] = $type;
      $row['mode']['#markup'] = $mode;
      $row['status']['#markup'] = $status;
    }
    else {
      $row['plugin'] = $type;
      $row['mode'] = $mode;
      $row['status'] = $status;
    }
    
    return $row + parent::buildRow($entity);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  protected function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['id'] = $this->t('ID');
    $header['plugin'] = $this->t('Payment gateway');
    $header['mode'] = $this->t('Mode');
    $header['status'] = $this->t('Status');
    return $header;
  }
  
}