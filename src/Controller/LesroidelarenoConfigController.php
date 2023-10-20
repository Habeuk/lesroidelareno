<?php

namespace Drupal\lesroidelareno\Controller;

use Drupal\Core\Controller\ControllerBase;
use Stephane888\Debug\Repositories\ConfigDrupal;
use Stephane888\DrupalUtility\HttpResponse;
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
  
  /**
   *
   * @param DomainNegotiatorInterface $domainNegotiator
   */
  public function __construct(DomainNegotiatorInterface $domainNegotiator) {
    $this->domainNegotiator = $domainNegotiator;
  }
  
  /**
   * Accorde les roles necessaire permettant de poursuivre la creation du site.
   */
  public function giveRoles() {
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    if ($user) {
      if (!$user->hasRole('gerant_de_site_web')) {
        $user->addRole('gerant_de_site_web');
        $user->save();
      }
      return HttpResponse::response([
        $user->id()
      ], 200, 'User must connecte');
    }
    return HttpResponse::response('user must connecte', 400, 'user must connecte');
  }
  
  /**
   * permet de gerer les menus.
   */
  public function manageMenuItems(Request $Request) {
    if (!lesroidelareno::userIsAdministratorSite()) {
      return $this->forbittenMessage();
    }
    $key = "third_party_settings.lesroidelareno.domain_id";
    /**
     * On sauvegarde le resultat, car cela est utiliser pour fabrqriquer le lien
     * "ajouter un menu" et de plus cela accelere traitement à ce stade.
     *
     * @var array $ids
     */
    $ids = lesroidelareno::retriveDataByKey($key);
    if (!$ids) {
      $query = \Drupal::entityTypeManager()->getStorage("menu")->getQuery();
      $query->condition($key, $this->domainNegotiator->getActiveId());
      $ids = $query->execute();
      if (!$ids) {
        // Si on ne parviens pas à recuperer l'id, on essaie via la label.
        $query = \Drupal::entityTypeManager()->getStorage("menu")->getQuery();
        $query->condition('label', $this->domainNegotiator->getActiveId(), 'CONTAINS');
        $ids = $query->execute();
      }
      lesroidelareno::setDataCache($key, $ids);
    }
    if ($ids) {
      $id = reset($ids);
      $menu = \Drupal\system\Entity\Menu::load($id);
      // on definit le type d'operation car la valeur default n'est pas
      // specifier.
      $menuForm = $this->entityFormBuilder()->getForm($menu, "edit");
      if (!lesroidelareno::isAdministrator()) {
        $menuForm['id']['#access'] = false;
        $menuForm['actions']['clear']['#access'] = false;
        $menuForm['actions']['delete']['#access'] = false;
        $menuForm['label']['#access'] = false;
        $menuForm['description']['#access'] = false;
        $menuForm['domain_id']['#access'] = false;
      }
      // on passe le domaine dans le paramettre.
      // dump(\Drupal::routeMatch()->getRouteObject()->getDefaults());
      return $menuForm;
    }
    $this->messenger()->addError("impossible de determiner votre menu, veillez contacter l'administrateur");
    return [];
  }
  
  /**
   *
   * @param string $menu
   * @return array|boolean|array
   */
  public function addMenuItem($menu) {
    if (!lesroidelareno::userIsAdministratorSite()) {
      return $this->forbittenMessage();
    }
    $menu = \Drupal\menu_link_content\Entity\MenuLinkContent::create([
      'bundle' => $menu
    ]);
    $menuForm = $this->entityFormBuilder()->getForm($menu);
    if (!lesroidelareno::isAdministrator()) {
      $menuForm['id']['#access'] = false;
      $menuForm['actions']['clear']['#access'] = false;
      $menuForm['actions']['delete']['#access'] = false;
      $menuForm['label']['#access'] = false;
      $menuForm['description']['#access'] = false;
      $menuForm['domain_id']['#access'] = false;
    }
    // on passe le domaine dans le paramettre.
    // dump(\Drupal::routeMatch()->getRouteObject()->getDefaults());
    return $menuForm;
  }
  
  /**
   * permet de lister les paiements et de les configurees par le prorietaire du
   * site.
   */
  public function PayementGateways(Request $request, $payment_plugin_id) {
    if (!lesroidelareno::userIsAdministratorSite() && lesroidelareno::FindUserAuthorDomain()) {
      return $this->forbittenMessage();
    }
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
        $this->setRequired($form['publishable_key']);
        $this->setRequired($form['secret_key']);
      }
      else {
        // dump($form['percent_value']);
        $this->setRequired($form['percent_value']);
        $this->setRequired($form['min_value_paid']);
        $this->setRequired($form['publishable_key']);
        $this->setRequired($form['secret_key']);
      }
      return $form;
    }
    return [];
  }
  
  protected function setRequired(&$field) {
    $field['#required'] = true;
    $field['widget']['#required'] = true;
    if (!empty($field['widget'][0])) {
      $field['widget'][0]['#required'] = true;
      if (!empty($field['widget'][0]['value'])) {
        $field['widget'][0]['value']['#required'] = true;
      }
    }
  }
  
  /**
   * Permet de configurer les prises de RDV.
   *
   * @return array
   */
  public function UpdateDefaultConfigsCreneauRdv() {
    if (!lesroidelareno::userIsAdministratorSite()) {
      return $this->forbittenMessage();
    }
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
  
  /**
   * Le but de cette fonction est de notifier l'administrateur l'acces à des
   * informations senssible.
   *
   * @param string $message
   * @param array $context
   * @return array
   */
  protected function forbittenMessage($message = "Access non authoriser", $context = []) {
    $this->getLogger("lesroidelareno")->critical($message, $context);
    $this->messenger()->addError($message);
    return [];
  }
  
}