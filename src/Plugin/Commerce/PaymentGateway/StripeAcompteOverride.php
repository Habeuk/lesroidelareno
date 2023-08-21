<?php

namespace Drupal\lesroidelareno\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_stripe\Plugin\Commerce\PaymentGateway\Stripe;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\commerce_price\MinorUnitsConverterInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Stripe\Stripe as StripeLibrary;
use Drupal\stripebyhabeuk\Plugin\Commerce\PaymentGateway\StripeAcompte;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;
use Drupal\lesroidelareno\lesroidelareno;

/**
 * Provides the Stripe payment gateway.
 * L'objectif principal de ce module est de permettre de surchager les
 * paramettres de connections de stripe en functions de la valeurs definie par
 * l'utilisateur.
 *
 * @CommercePaymentGateway(
 *   id = "stripeacompteoverride",
 *   label = "StripeHabeuk Acompte by lesroidelareno",
 *   display_label = "Payer l'acompte",
 *   forms = {
 *     "add-payment-method" = "Drupal\lesroidelareno\PluginForm\Stripe\PaymentMethodAddAcompteOverride",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa", "unionpay"
 *   },
 *   js_library = "commerce_stripe/form",
 *   requires_billing_information = FALSE,
 * )
 */
class StripeAcompteOverride extends StripeAcompte {
  /**
   * Permet de terminer si la configuration est deja à jour.
   *
   * @var string
   */
  private $configIsUpdate = FALSE;
  
  /**
   *
   * @var \Drupal\lesroidelareno\Entity\CommercePaymentConfig
   */
  protected $commerce_payment_config;
  
  /**
   * Re-initializes the SDK after the plugin is unserialized.
   */
  public function __wakeup() {
    parent::__wakeup();
    // new approche.
    $this->updateConfigs();
  }
  
  /**
   * On charge la valeur des access en function du domaine.
   */
  private function updateConfigs() {
    $DirectAccessRoutes = [
      'entity.commerce_payment_gateway.collection',
      'entity.commerce_payment_gateway.edit_form'
    ];
    if (!$this->configIsUpdate && !in_array(\Drupal::routeMatch()->getRouteName(), $DirectAccessRoutes)) {
      if (!$this->commerce_payment_config) {
        $datas = \Drupal::entityTypeManager()->getStorage("commerce_payment_config")->loadByProperties([
          'domain_id' => lesroidelareno::getCurrentDomainId()
        ]);
        if ($datas)
          $this->commerce_payment_config = reset($datas);
      }
      //
      if ($this->commerce_payment_config) {
        $this->configuration['publishable_key'] = $this->commerce_payment_config->getPublishableKey();
        $this->configuration['secret_key'] = $this->commerce_payment_config->getSecretKey();
        $this->configuration['mode'] = $this->commerce_payment_config->getMode();
        $this->configuration['percent_value'] = (int) $this->commerce_payment_config->getPercentValue();
        $this->configuration['min_value_paid'] = (int) $this->commerce_payment_config->getMinValuePaid();
        \Stephane888\Debug\debugLog::kintDebugDrupal($this->configuration, 'updateConfigs', true);
        $this->configIsUpdate = true;
      }
      else {
        $this->configuration['publishable_key'] = '';
        $this->configuration['secret_key'] = '';
        $this->messenger()->addError("Paramettres de vente non configurer");
      }
    }
  }
  
  // public function setConfiguration(array $configuration) {
  // $this->updateConfigs();
  // parent::setConfiguration($configuration);
  // }
  public function getPercentValue() {
    $this->updateConfigs();
    return parent::getPercentValue();
  }
  
  public function getMinValuePaid() {
    $this->updateConfigs();
    return parent::getMinValuePaid();
  }
  
  public function getPublishableKey() {
    $this->updateConfigs();
    return parent::getPublishableKey();
  }
  
  public function getSecretKey() {
    $this->updateConfigs();
    return parent::getSecretKey();
  }
  
}