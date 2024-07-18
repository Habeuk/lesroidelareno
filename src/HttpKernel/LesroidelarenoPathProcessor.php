<?php

namespace Drupal\lesroidelareno\HttpKernel;

use Drupal\domain\DomainNegotiatorInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityInterface;
use Drupal\domain_access\DomainAccessManagerInterface;
use Drupal\commerce_product\Entity\Product;

/**
 * Processes the outbound path using path alias lookups.
 */
class LesroidelarenoPathProcessor implements OutboundPathProcessorInterface {
  
  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;
  
  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;
  
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  
  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;
  
  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  
  /**
   * An array of content entity types.
   *
   * @var array
   */
  protected $entityTypes;
  
  /**
   * An array of routes exclusion settings, keyed by route.
   *
   * @var array
   */
  protected $excludedRoutes;
  
  /**
   * The active domain request.
   *
   * @var \Drupal\domain\DomainInterface
   */
  protected $activeDomain;
  
  /**
   * The domain storage.
   *
   * @var \Drupal\domain\DomainStorageInterface|null
   */
  protected $domainStorage;
  
  /**
   * Constructs a DomainSourcePathProcessor object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *        The domain negotiator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *        The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *        The entity type manager.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *        The path alias manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *        The config factory.
   */
  public function __construct(DomainNegotiatorInterface $negotiator, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, AliasManagerInterface $alias_manager, ConfigFactoryInterface $config_factory) {
    $this->negotiator = $negotiator;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $alias_manager;
    $this->configFactory = $config_factory;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    //
    if (!empty($options['entity_type']) && $options['entity_type'] == 'commerce_product') {
      
      // Get the current language.
      $langcode = NULL;
      if (!empty($options['language'])) {
        $langcode = $options['language']->getId();
      }
      
      // Get the URL object for this request.
      $alias = $this->aliasManager->getPathByAlias($path, $langcode);
      $url = Url::fromUserInput($alias, $options);
      
      // Get the route name to pass through to the alter hooks.
      if ($url->isRouted()) {
        $options['route_name'] = $url->getRouteName();
      }
      
      if (!empty($options['entity'])) {
        $entity = $options['entity'];
      }
      else {
        $parameters = $url->getRouteParameters();
        if (!empty($parameters)) {
          $entity = $this->getEntity($parameters);
        }
      }
      
      if (isset($options['domain_target_id'])) {
        $target_id = $options['domain_target_id'];
        $source = $this->domainStorage()->load($target_id);
      }
      else {
        $source = $this->Lesroidelareno_domain_source_get($entity);
      }
      
      // on change le domaine de base.
      if (!empty($source)) {
        // dump($source);
        /**
         *
         * @var \Drupal\domain\Entity\Domain $source
         */
        // Note that url rewrites add a leading /, which getPath() also adds.
        $options['base_url'] = trim($source->getPath(), '/');
        $options['absolute'] = TRUE;
      }
    }
    return $path;
  }
  
  protected function Lesroidelareno_domain_source_get(Product $entity) {
    $source = NULL;
    if (!isset($entity->{DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD})) {
      return $source;
    }
    // $entity->toUrl()
    $value = $entity->get(DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD)->getValue();
    if (!empty($value[0])) {
      $target_id = $value[0]['target_id'];
      if ($domain = $this->domainStorage()->load($target_id))
        $source = $domain;
    }
    return $source;
  }
  
  /**
   * Gets an array of content entity types, keyed by type.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[] An array of content
   *         entity types, keyed by type.
   */
  public function getEntityTypes() {
    if (!isset($this->entityTypes)) {
      foreach ($this->entityTypeManager->getDefinitions() as $type => $definition) {
        if ($definition->getGroup() == 'content') {
          $this->entityTypes[$type] = $type;
        }
      }
    }
    return $this->entityTypes;
  }
  
  /**
   * Retrieves the domain storage handler.
   *
   * @return \Drupal\domain\DomainStorageInterface The domain storage handler.
   */
  protected function domainStorage() {
    if (!$this->domainStorage) {
      $this->domainStorage = $this->entityTypeManager->getStorage('domain');
    }
    return $this->domainStorage;
  }
  
}