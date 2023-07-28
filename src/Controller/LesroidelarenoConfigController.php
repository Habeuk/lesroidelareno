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

/**
 * Class DonneeSiteInternetEntityController.
 *
 * Returns responses for Donnee site internet des utilisateurs routes.
 */
class LesroidelarenoConfigController extends ControllerBase {
  /**
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;
  
  public static function create(ContainerInterface $container) {
    return new static($container->get('domain.negotiator'));
  }
  
  public function __construct(DomainNegotiatorInterface $domainNegotiator) {
    $this->domainNegotiator = $domainNegotiator;
  }
  
  /**
   * --
   */
  public function PayementGateways(Request $request, $payment_plugin_id) {
    /**
     * Contient les payments qui peuvent etre utiliser par les clients.
     *
     * @var array $validPayments
     */
    $validPayments = [
      'stripe_cart_by_domain',
      'commander',
      'paiement_acompte'
    ];
    // permet de lister tous les plugins
    if ($payment_plugin_id == 'list-all') {
      $links = [];
      foreach ($validPayments as $value) {
        $PaymentGateway = PaymentGateway::load($value);
        if ($PaymentGateway) {
          $links[] = [
            'title' => $PaymentGateway->label(),
            'url' => Url::fromRoute("lesroidelareno.payement_gateways", [
              'payment_plugin_id' => $PaymentGateway->id()
            ], [
              'query' => [
                'destination' => $request->getPathInfo()
              ]
            ])
          ];
        }
      }
      return [
        '#theme' => 'links',
        '#links' => $links
      ];
    }
    else {
      $datas = $this->entityTypeManager()->getStorage('commerce_payment_config')->loadByProperties([
        'domain_id' => $this->domainNegotiator->getActiveId(),
        'payment_plugin_id' => $payment_plugin_id
      ]);
      if (!$datas) {
        $CommercePaymentConfig = CommercePaymentConfig::create([
          'domain_id' => $this->domainNegotiator->getActiveId(),
          'payment_plugin_id' => $payment_plugin_id
        ]);
        $CommercePaymentConfig->save();
      }
      else {
        $CommercePaymentConfig = reset($datas);
      }
      $form = $this->entityFormBuilder()->getForm($CommercePaymentConfig);
      // $form['payment_plugin_id']['widget'][0]['value']['#attributes']['readonly']
      // = 'readonly';
      if (!lesroidelareno::isAdministrator()) {
        $form['domain_id']['#access'] = false;
        $form['payment_plugin_id']['#access'] = false;
      }
      // On masque les champs non desirer.
      if ($CommercePaymentConfig->get('payment_plugin_id')->value == 'commander') {
        $form['publishable_key']['#access'] = false;
        $form['secret_key']['#access'] = false;
        $form['mode']['#access'] = false;
        $form['percent_value']['#access'] = false;
        $form['min_value_paid']['#access'] = false;
      }
      elseif ($CommercePaymentConfig->get('payment_plugin_id')->value == 'stripe_cart_by_domain') {
        $form['percent_value']['#access'] = false;
        $form['min_value_paid']['#access'] = false;
      }
      return $form;
    }
    return [];
  }
  
  /**
   * Permet de configurer les prises de RDV.
   *
   * @return array
   */
  public function UpdateDefaultConfigsCreneauRdv() {
    $content = ConfigDrupal::config('prise_rendez_vous.default_configs');
    $entity = RdvConfigEntity::load($content['id']);
    if (!$entity) {
      $this->messenger()->addStatus('new RDV config is create', true);
      $entity = RdvConfigEntity::create();
      $entity->set('id', $content['id']);
      $entity->set('label', $content['label']);
      $entity->set('jours', \Drupal\prise_rendez_vous\PriseRendezVousInterface::jours);
      $entity->save();
    }
    // On cree le formulaire pour la configuration de base des prises de
    // rendez-vous.
    $form = $this->entityFormBuilder()->getForm($entity);
    
    return $form;
  }
  
  // function ConfigPage() {
  // return [
  // '#type' => 'html_tag',
  // '#tag' => 'div',
  // '#value' => 'Page config'
  // ];
  // }
  
  // function ConfigPage2() {
  // return [
  // '#type' => 'html_tag',
  // '#tag' => 'div',
  // '#value' => 'Page config 2'
  // ];
  // }

/**
 *
 * {@inheritdoc}
 */
}