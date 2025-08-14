<?php
declare(strict_types = 1);

namespace Drupal\lesroidelareno\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\domain\DomainNegotiator;
use Drupal\Core\Url;
use Stephane888\Debug\Repositories\ConfigDrupal;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\lesroidelareno\lesroidelareno;

/**
 * Configure lesroidelareno settings for this site.
 */
final class AdminUsersForm extends ConfigFormBase implements ContainerInjectionInterface {
  
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  
  /**
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;
  
  /**
   *
   * @var \Drupal\domain\DomainNegotiator
   */
  protected $DomainNegotiator;
  
  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *        The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $EntityTypeManagerInterface, RequestStack $RequestStack, DomainNegotiator $DomainNegotiator) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $EntityTypeManagerInterface;
    $this->request = $RequestStack->getCurrentRequest();
    $this->DomainNegotiator = $DomainNegotiator;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // $instance = parent::create($container);
    // $instance->entityTypeManager = $container->get('entity_type.manager');
    // $instance->request = $container->get('request_stack');
    // return $instance;
    return new static($container->get('config.factory'), $container->get('entity_type.manager'), $container->get('request_stack'), $container->get('domain.negotiator'));
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'lesroidelareno_admin_users';
  }
  
  /**
   *
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'lesroidelareno.settings'
    ];
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query = $this->request->query->get('domain_config_ui_domain');
    $domain = $this->DomainNegotiator->getActiveDomain();
    if (empty($query)) {
      if ($domain) {
        $url = Url::fromRoute("lesroidelareno.admin_users", [], [
          'query' => [
            'domain_config_ui_domain' => $domain->id(),
            'domain_config_ui_language' => ''
          ],
          'absolute' => TRUE
        ]);
        return new RedirectResponse($url->toString());
      }
    }
    elseif ($domain && $domain->id() !== $query) {
      /**
       *
       * @var \Drupal\domain\Entity\Domain $domain2
       */
      $domain2 = $this->entityTypeManager->getStorage('domain')->load($query);
      // On redirige sur le domaine definie dans la variable, car on ne peut pas
      // afficher ou editer une valeur à partir d'une autre domaine.
      if ($domain2) {
        $url = Url::fromRoute("lesroidelareno.admin_users", [], [
          'query' => [
            'domain_config_ui_domain' => $domain2->id(),
            'domain_config_ui_language' => ''
          ],
          'absolute' => false
        ]);
        $url_string = $domain2->getScheme() . $domain2->getHostname() . $url->toString();
        return new RedirectResponse($url_string);
      }
    }
    $configs = ConfigDrupal::config('lesroidelareno.settings');
    $default_value = [];
    $options = [];
    if (!empty($configs['users']))
      foreach ($configs['users'] as $value) {
        $user = \Drupal\user\Entity\User::load($value['target_id']);
        if ($user) {
          $options[$user->id()] = $user->getAccountName();
          $default_value[] = $user->id();
        }
      }
    $form['users'] = [
      '#type' => 'select2',
      '#title' => $this->t('Utilisateurs'),
      "#description" => $this->t("Selectionner les utilisateurs"),
      "#multiple" => true,
      '#options' => $options,
      '#minimumInputLength' => 3,
      "#default_value" => $default_value,
      '#target_type' => 'user',
      '#autocomplete' => true,
      '#cardinality' => -1,
      '#select2' => [
        'width' => '100%'
      ]
    ];
    return parent::buildForm($form, $form_state);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    // if ($form_state->getValue('example') === 'wrong') {
    // $form_state->setErrorByName(
    // 'message',
    // $this->t('The value is not correct.'),
    // );
    // }
    // @endcode
    parent::validateForm($form, $form_state);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $users = $form_state->getValue('users');
    $this->config('lesroidelareno.settings')->set('users', $form_state->getValue('users'))->save();
    if ($users) {
      foreach ($users as $value) {
        $user = \Drupal\user\Entity\User::load($value['target_id']);
        if ($user) {
          $update = false;
          $userArray = $user->toArray();
          /**
           *
           * @var \Drupal\Core\Field\EntityReferenceFieldItemList $field_domain_admin
           */
          if (!$this->hasValue(lesroidelareno::getCurrentDomainId(), $userArray['field_domain_admin'])) {
            $update = true;
            // Ce champs est utilisé pour determiner si l'utilisateur est
            // administrateur du domaine.
            $userArray['field_domain_admin'][] = [
              'target_id' => lesroidelareno::getCurrentDomainId()
            ];
            $user->set("field_domain_admin", $userArray['field_domain_admin']);
          }
          if (!$this->hasValue(lesroidelareno::getCurrentDomainId(), $userArray['field_domain_access'])) {
            $update = true;
            $userArray['field_domain_access'][] = [
              'target_id' => lesroidelareno::getCurrentDomainId()
            ];
            $user->set("field_domain_access", $userArray['field_domain_access']);
          }
          if ($update) {
            $this->messenger()->addMessage("L'utilisateur " . $user->getAccountName() . " a été ajouté au domaine " . lesroidelareno::getCurrentDomainId() . " avec succes");
            $user->addRole(lesroidelareno::getRoleManagerWebsite());
            $user->save();
          }
        }
      }
    }
    parent::submitForm($form, $form_state);
  }
  
  private function hasValue(mixed $search, array $datas): bool {
    return in_array($search, array_column($datas, 'target_id'), true);
  }
}
