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

/**
 * Provides the Stripe payment gateway.
 * L'objectif principal de ce module est de permettre de surchager les
 * paramettres de connections de stripe en functions de la valeurs definie par
 * l'utilisateur.
 *
 * @CommercePaymentGateway(
 *   id = "stripeacompteoverride",
 *   label = "StripeHabeuk Acompte by lesroidelareno",
 *   display_label = "StripeHabeuk Acompte by lesroidelareno",
 *   forms = {
 *     "add-payment-method" = "Drupal\lesroidelareno\PluginForm\Stripe\PaymentMethodAddFormOverride",
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
   *
   * @var \Drupal\lesroidelareno\Entity\CommercePaymentConfig
   */
  protected $commerce_payment_config;
  
  /**
   * Re-initializes the SDK after the plugin is unserialized.
   */
  public function __wakeup() {
    $this->updateConfigs();
    parent::__wakeup();
  }
  
  /**
   * On charge la valeur des access en function du domaine.
   */
  private function updateConfigs() {
    $DirectAccessRoutes = [
      'entity.commerce_payment_gateway.collection',
      'entity.commerce_payment_gateway.edit_form'
    ];
    if (!in_array(\Drupal::routeMatch()->getRouteName(), $DirectAccessRoutes)) {
      if (!$this->commerce_payment_config) {
        /**
         *
         * @var DomainNegotiatorInterface $negotiator
         */
        $negotiator = \Drupal::service('domain.negotiator');
        $datas = \Drupal::entityTypeManager()->getStorage("commerce_payment_config")->loadByProperties([
          'domain_id' => $negotiator->getActiveId()
        ]);
        if ($datas)
          $this->commerce_payment_config = reset($datas);
      }
      //
      if ($this->commerce_payment_config) {
        $this->configuration['publishable_key'] = $this->commerce_payment_config->getPublishableKey();
        $this->configuration['secret_key'] = $this->commerce_payment_config->getSecretKey();
        $this->configuration['mode'] = $this->commerce_payment_config->getMode();
        $this->configuration['percent_value'] = $this->commerce_payment_config->getPercentValue();
        $this->configuration['min_value_paid'] = $this->commerce_payment_config->getMinValuePaid();
      }
      else {
        $this->configuration['publishable_key'] = '';
        $this->configuration['secret_key'] = '';
        $this->messenger()->addError("Paramettres de vente non configurer");
      }
    }
  }
  
}