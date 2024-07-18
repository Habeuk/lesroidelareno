<?php

namespace Drupal\lesroidelareno\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_payment\Event\PaymentEvents;
use Drupal\commerce_payment\Event\FilterPaymentGatewaysEvent;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\lesroidelareno\Entity\CommercePaymentConfig;

class CommerceConditionEventsSubscriber implements EventSubscriberInterface {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  
  /**
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;
  
  /**
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;
  //
  protected $domainId;
  
  function __construct(EntityTypeManagerInterface $entityTypeManager, DomainNegotiatorInterface $domainNegotiator, Messenger $messenger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->domainNegotiator = $domainNegotiator;
    $this->messenger = $messenger;
  }
  
  /**
   *
   * {@inheritdoc}
   *
   * @return array The event names to listen for, and the methods that should be
   *         executed.
   */
  public static function getSubscribedEvents() {
    return [
      PaymentEvents::FILTER_PAYMENT_GATEWAYS => 'PaymentGatewaysFilters'
    ];
  }
  
  public function PaymentGatewaysFilters(FilterPaymentGatewaysEvent $event) {
    $validPaymentGateways = [];
    $PaymentGateways = $event->getPaymentGateways();
    foreach ($PaymentGateways as $payment_method_id => $payment_method) {
      if ($this->isActivePaymentMethod($payment_method_id)) {
        $validPaymentGateways[$payment_method_id] = $payment_method;
      }
    }
    if (!$validPaymentGateways)
      $this->messenger->addError('Vous devez configurer au moins une methode de paiement');
    //
    $event->setPaymentGateways($validPaymentGateways);
  }
  
  protected function isActivePaymentMethod($payment_method_id) {
    $datas = $this->entityTypeManager->getStorage('commerce_payment_config')->loadByProperties([
      'domain_id' => $this->domainId(),
      'payment_plugin_id' => $payment_method_id
    ]);
    if ($datas) {
      /**
       *
       * @var CommercePaymentConfig $PaymentGateway
       */
      $PaymentGateway = reset($datas);
      return $PaymentGateway->PaymentMethodIsActive();
    }
    return false;
  }
  
  protected function domainId() {
    if (!$this->domainId) {
      $this->domainId = $this->domainNegotiator->getActiveId();
    }
    return $this->domainId;
  }
  
}