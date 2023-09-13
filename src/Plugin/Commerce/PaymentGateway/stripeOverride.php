<?php

namespace Drupal\lesroidelareno\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_stripe\Plugin\Commerce\PaymentGateway\Stripe;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Form\FormStateInterface;
use Stripe\Stripe as StripeLibrary;
use Drupal\lesroidelareno\lesroidelareno;

/**
 * Provides the Stripe payment gateway.
 * L'objectif principal de ce module est de permettre de surchager les
 * paramettres de connections de stripe en functions de la valeurs definie par
 * l'utilisateur.
 *
 * @CommercePaymentGateway(
 *   id = "lesroidelareno_stripe_override",
 *   label = "Stripe(default) by lesroidelareno",
 *   display_label = "Payer la totalité",
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
class stripeOverride extends Stripe {
  
  /**
   *
   * @var \Drupal\lesroidelareno\Entity\CommercePaymentConfig
   */
  protected $commerce_payment_config;
  
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
        
        $datas = \Drupal::entityTypeManager()->getStorage("commerce_payment_config")->loadByProperties([
          'domain_id' => lesroidelareno::getCurrentDomainId(),
          'payment_plugin_id' => 'paiement_acompte'
        ]);
        if ($datas)
          $this->commerce_payment_config = reset($datas);
      }
      //
      if ($this->commerce_payment_config) {
        $this->configuration['publishable_key'] = $this->commerce_payment_config->getPublishableKey();
        $this->configuration['secret_key'] = $this->commerce_payment_config->getSecretKey();
        $this->configuration['mode'] = $this->commerce_payment_config->getMode();
      }
      else {
        $this->configuration['publishable_key'] = '';
        $this->configuration['secret_key'] = '';
        $this->messenger()->addError("Paramettres de vente non configurer");
      }
    }
  }
  
  /**
   * Re-initializes the SDK after the plugin is unserialized.
   */
  public function __wakeup() {
    $this->updateConfigs();
    parent::__wakeup();
    $this->init();
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase::buildConfigurationForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['display_label']['#access'] = TRUE;
    return $form;
  }
  
  // /**
  // * Initializes the SDK.
  // */
  // protected function init() {
  // parent::init();
  // $dd = [
  // 'ApiKey' => StripeLibrary::getApiKey(),
  // 'configuration' => $this->configuration
  // ];
  // \Stephane888\Debug\debugLog::kintDebugDrupal($dd, 'stripeOverride--init--',
  // true);
  // }
  
  // /**
  // *
  // * {@inheritdoc}
  // * @see
  // \Drupal\commerce_stripe\Plugin\Commerce\PaymentGateway\Stripe::getPublishableKey()
  // */
  // public function getPublishableKey() {
  // $this->updateConfigs();
  // return parent::getPublishableKey();
  // }
}









