<?php

namespace Drupal\lesroidelareno\Services\layoutgenentitystyles;

use Drupal\layoutgenentitystyles\Services\LayoutgenentitystylesServices;
use Drupal\lesroidelareno\lesroidelareno;

/**
 * Decoration de service : layoutgenentitystyles.add.style.theme
 *
 * @author Stephane
 *        
 */
class OverrideLayoutgenentitystylesServices extends LayoutgenentitystylesServices {
  
  /**
   *
   * @var string
   */
  private $domaine_id;
  
  /**
   *
   * @var LayoutgenentitystylesServices
   */
  protected $serviceInner;
  
  /**
   * Contient la liste des entites donc on va rechercher s'il possede les
   * données pour le champs "layout_builder__layout"
   * Pour le moment on fait uniquement pour l'ent
   *
   * @var array
   */
  protected $entitiesListLayoutBuilderLayout = [
    'cv_entity'
  ];
  /**
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;
  protected $fields = [];
  
  function __construct(LayoutgenentitystylesServices $serviceInner, $SectionStorageManager, $LoadStyleFromMod, $ConfigFactory, $ManageFileCustomStyle, $ManageFileMailStyle) {
    $this->serviceInner = $serviceInner;
    $this->setDefaultDomain();
    parent::__construct($SectionStorageManager, $LoadStyleFromMod, $ConfigFactory, $ManageFileCustomStyle, $ManageFileMailStyle);
  }
  
  /**
   * On recupere la liste des plugins d'affichage d'entite validé en funcion de
   * la configurations.
   */
  public function getListSectionStorages() {
    if (!$this->sectionStorages) {
      /**
       *
       * @var \Drupal\lesroidelareno\Services\layoutgenentitystyles\OverrideParagraphLoader $paragraph_loader
       */
      $paragraph_loader = \Drupal::service('lesroidelareno.layoutgenentitystyles.paragraph_loader');
      $paragraph_loader->setDomaineId($this->domaine_id);
      /**
       * Contient tous les modes d'affichage sans filtre par domaine.
       * ( au lieu de faire parent::, il faut tout reconstruire ).
       *
       * @var array $sectionStorages
       */
      $DefaultSectionStorages = $this->getBaseStorage();
      //
      $field_access = \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD;
      $field_all_access = \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD;
      
      $sectionStorages = [];
      // on filre les contenus definit par le domaine encours.
      foreach ($DefaultSectionStorages as $key => $value) {
        // La clee ($key) est composer de 3 elements.
        [
          $entity_type_id,
          $bundle,
          $viewMode
        ] = explode(".", $key);
        /**
         *
         * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $entity_type
         */
        $entity_type = $this->entityTypeManager()->getStorage($entity_type_id);
        if ($entity_type->hasData()) {
          /**
           *
           * @var \Drupal\Core\Entity\EntityFieldManager $fieldManager
           */
          $fieldManager = \Drupal::service('entity_field.manager');
          $bundle_key = $entity_type->getEntityType()->getKey('bundle');
          if ($bundle_key) {
            $field_definitions = $fieldManager->getFieldDefinitions($entity_type_id, $bundle);
            $has__field_domain_access = (bool) !empty($field_definitions[$field_access]) ?? false;
            $has__field_all_access = (bool) !empty($field_definitions[$field_all_access]) ?? false;
            if ($has__field_domain_access) {
              $ids = $entity_type->getQuery()->condition($field_access, $this->domaine_id)->condition($bundle_key, $bundle)->accessCheck(false)->execute();
              if ($ids)
                $sectionStorages[$key] = $value;
              elseif ($has__field_all_access) {
                $ids = $entity_type->getQuery()->condition($field_all_access, true)->condition($bundle_key, $bundle)->accessCheck(false)->execute();
                if ($ids) {
                  $sectionStorages[$key] = $value;
                }
              }
            } // S'il n'a pas de champs de filtre alors son affichage doit,
              // etre disponible pour tous les domaines.
            else {
              $sectionStorages[$key] = $value;
            }
          }
          else {
            $field_definitions = $fieldManager->getFieldDefinitions($entity_type_id, $entity_type_id);
            $has__field_domain_access = (bool) $field_definitions[$field_access] ?? false;
            $has__field_all_access = (bool) $field_definitions[$field_all_access] ?? false;
            if ($has__field_domain_access) {
              // On verifie si on a au moins une donnée valide.
              $ids = $entity_type->getQuery()->condition($field_access, $this->domaine_id)->accessCheck(false)->execute();
              if ($ids)
                $sectionStorages[$key] = $value;
              elseif ($has__field_all_access) {
                $ids = $entity_type->getQuery()->condition($field_all_access, true)->accessCheck(false)->execute();
                if ($ids) {
                  $sectionStorages[$key] = $value;
                }
              }
            }
            else {
              $sectionStorages[$key] = $value;
            }
          }
        }
      }
      
      $this->sectionStorages = $sectionStorages;
      // on passe par une approche statique pour wb-horizon.
      if ($this->getDomainId() == 'wb_horizon_com' || $this->getDomainId() == 'wb_horizon_kksa') {
        $entitiesAdd = [
          [
            'entity_type_id' => 'blocks_contents',
            'bundle' => 'article_blogs_wbh'
          ],
          [
            'entity_type_id' => 'block_content',
            'bundle' => 'header'
          ],
          [
            'entity_type_id' => 'block_content',
            'bundle' => 'footer'
          ],
          [
            'entity_type_id' => 'user',
            'bundle' => 'user'
          ]
        ];
        foreach ($entitiesAdd as $entitiyAdd) {
          $customsectionStorages = $this->entityTypeManager()->getStorage('entity_view_display')->loadByProperties(
            [
              'targetEntityType' => $entitiyAdd['entity_type_id'],
              'bundle' => $entitiyAdd['bundle']
            ]);
          $this->sectionStorages += $customsectionStorages;
        }
        // if (\Drupal::currentUser()->id() == 1)
        // dd($DefaultSectionStorages, $this->sectionStorages);
      }
    }
    //
    $this->getComponentsOverrides();
    return $this->sectionStorages;
  }
  
  /**
   * On recupere la liste des plugins d'affichage d'entite validé en funcion de
   * la configurations.
   *
   * @return array
   */
  private function getBaseStorage() {
    /**
     * L'entite qui gere les affichages.
     *
     * @var string $entity_type_id
     */
    $entity_type_id = 'entity_view_display';
    $DefaultsSectionStorages = $this->entityTypeManager()->getStorage($entity_type_id)->loadByProperties();
    
    // On filtre les affichages par ceux donc l'utilisateur à valider.
    $config = $this->getConfigs();
    $entity_auto_generate = array_filter($config['entity_auto_generate'], function ($value) {
      return $value ?? false;
    });
    
    if ($entity_auto_generate) {
      $entity_auto_generate = array_keys($entity_auto_generate);
      $this->sectionStorages = array_filter($DefaultsSectionStorages,
        function ($key) use ($entity_auto_generate) {
          foreach ($entity_auto_generate as $valid_entity_type_id) {
            if (str_contains($key, $valid_entity_type_id . '.'))
              return true;
          }
          return false;
        }, ARRAY_FILTER_USE_KEY);
      /**
       * On a un probleme de paragraphe qui persite.
       * Pour gagner en temps.
       */
      if ($this->getDomainId() != 'wb_horizon_com') {
        return $this->sectionStorages;
      }
      // On recupere les paragraphes attaché à un layout.
      // ( Dans cette approche, on considere que tous les layouts sont
      // associés à des paragraphes ).
      /**
       *
       * @var \Drupal\lesroidelareno\Services\layoutgenentitystyles\OverrideParagraphLoader $paragraph_loader
       */
      $paragraph_loader = \Drupal::service('lesroidelareno.layoutgenentitystyles.paragraph_loader');
      $grouped = $paragraph_loader->loadGroupedByParagraphType($entity_auto_generate);
      
      $sectionStorages = [];
      foreach ($grouped as $entity_type_id => $entity_type_ids) {
        foreach ($entity_type_ids as $infor_entity) {
          // On recupere les configurations d'affichage liée au paragraphs.
          $seach_key = 'paragraph.' . $infor_entity['paragraph_type'] . '.';
          $sectionStorages += array_filter($DefaultsSectionStorages, function ($key) use ($seach_key) {
            return str_contains($key, $seach_key) ? true : false;
          }, ARRAY_FILTER_USE_KEY);
        }
      }
    }
    return $sectionStorages;
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\layoutgenentitystyles\Services\LayoutgenentitystylesServices::getDefaultTheme()
   */
  public function getDefaultTheme() {
    if ($this->domaine_id) {
      $defaultThemeName = $this->domaine_id;
    }
    else
      throw new \ErrorException("Le domaine ne peut etre vide");
    return $defaultThemeName;
  }
  
  /**
   * Permet de recuperer tous les styles ajouter via l'interface des Scss.js de
   * layout.
   */
  public function getComponentsOverrides() {
    // on exclue wb-horizon, car il y'aurra bcp de styles provenant de modele.
    if ($this->getDomainId() != 'wb_horizon_com') {
      $cache_ids = [];
      $cache_entities = [];
      $field_access = \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD;
      // On filtre les affichages par ceux donc l'utilisateur à valider.
      $config = $this->getConfigs();
      $entity_auto_generate = array_filter($config['entity_auto_generate'], function ($value) {
        return $value ?? false;
      });
      $entity_auto_generate = array_keys($entity_auto_generate);
      
      foreach ($entity_auto_generate as $entity_type_id) {
        /**
         *
         * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $storage
         */
        $storage = $this->entityTypeManager()->getStorage($entity_type_id);
        if (!$storage && !($storage instanceof \Drupal\Core\Entity\Sql\SqlContentEntityStorage))
          continue;
        $layoutEntitiesViews = [];
        // Verifions si l'entite a des bundles.
        if ($storage->getEntityType()->getBundleEntityType()) {
          $BundleEntityType = $storage->getEntityType()->getBundleEntityType();
          $BundleEntities = $this->entityTypeManager()->getStorage($BundleEntityType)->loadMultiple();
          // Les bundles qui ont un affichage utilisant les layouts.
          foreach ($BundleEntities as $BundleEntity) {
            $this->getEntitiesModeDisplay($layoutEntitiesViews, $entity_type_id, $BundleEntity->id());
          }
        }
        else {
          $this->getEntitiesModeDisplay($layoutEntitiesViews, $entity_type_id, $entity_type_id);
        }
        
        if ($layoutEntitiesViews) {
          foreach ($layoutEntitiesViews as $bundle_id => $layoutEntities) {
            foreach ($layoutEntities as $layout_builder) {
              // On doit se rassurer que chaque entité peut etre surcharger.
              if (!$layout_builder['enabled'])
                continue;
              $this->getIds($cache_ids, $storage, $entity_type_id, $bundle_id, $field_access);
              $ids = $cache_ids[$entity_type_id][$bundle_id];
              
              // $key = $storage->getEntityType()->getKey('bundle');
              // $query = $storage->getQuery();
              // if ($this->EntityHasField($entity_type_id, $field_access))
              // $query->condition($field_access, $this->getDomainId());
              // if ($key)
              // $query->condition($key, $bundle_id);
              // $ids = $query->accessCheck(TRUE)->execute();
              
              if ($ids) {
                // dump($ids, $bundle_id, $entity_type_id);
                if ($layout_builder['allow_custom']) {
                  if (empty($cache_entities[$entity_type_id][$bundle_id]))
                    $cache_entities[$entity_type_id][$bundle_id] = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple($ids);
                  $entities = $cache_entities[$entity_type_id][$bundle_id];
                  if ($entities) {
                    // On ajoute les styles par defaut.
                    $this->getOverrideScss($layout_builder['sections']);
                    // On ajoute les styles par defaut.
                    foreach ($entities as $entity) {
                      $sections = [];
                      $listSetions = $entity->get('layout_builder__layout')->getValue();
                      // $section_storage = $entity->getEntityTypeId() . '.' .
                      // $entity->bundle() . '.' . $entity->id();
                      foreach ($listSetions as $value) {
                        $sections[] = reset($value);
                      }
                      $this->getOverrideScss($sections);
                    }
                  }
                }
                else {
                  // on ajoute les styles par defaut.
                  $this->getOverrideScss($layout_builder['sections']);
                }
              }
            }
          }
        }
      }
      // dd($entity_auto_generate);
    }
  }
  
  private function getIds(array &$cache_ids, $storage, $entity_type_id, $bundle_id, $field_access) {
    if (empty($cache_ids[$entity_type_id][$bundle_id])) {
      $key = $storage->getEntityType()->getKey('bundle');
      $query = $storage->getQuery();
      if ($this->EntityHasField($entity_type_id, $field_access))
        $query->condition($field_access, $this->getDomainId());
      if ($key)
        $query->condition($key, $bundle_id);
      $cache_ids[$entity_type_id][$bundle_id] = $query->accessCheck(TRUE)->execute();
    }
  }
  
  /**
   * Permet de recuperer tous les styles ajouter via l'interface des Scss.js de
   * layout.
   *
   * @deprecated permet juste de comparer avec getComponentsOverrides();
   */
  public function getComponentsOverridesOLD() {
    // on exclue wb-horizon, car il y'aurra bcp de styles provenant de modele.
    if ($this->getDomainId() != 'wb_horizon_com') {
      $field_access = \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD;
      $entities_base = [
        'paragraph',
        'blocks_contents'
      ];
      foreach ($entities_base as $entity_type_id) {
        $storage = $this->entityTypeManager()->getStorage($entity_type_id);
        if (!$storage)
          continue;
        $query = $storage->getQuery();
        $query->condition($field_access, $this->getDomainId());
        $ids = $query->accessCheck(TRUE)->execute();
        if ($ids) {
          $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple($ids);
          foreach ($entities as $entity) {
            // pour les entites de paragraphes surcharger.
            if ($entity->hasField('layout_builder__layout')) {
              $sections = [];
              $listSetions = $entity->get('layout_builder__layout')->getValue();
              // $section_storage = $entity->getEntityTypeId() . '.' .
              // $entity->bundle() . '.' . $entity->id();
              foreach ($listSetions as $value) {
                $sections[] = reset($value);
              }
              $this->getOverrideScss($sections);
              // Pas necessaire, cela va ajouter plus de styles, Or on a deja
              // recuperer les styles utiles via d'autres mecanimes.
              // $this->generateStyleFromSection($sections, $section_storage);
              $entitiesViews = $this->entityTypeManager()->getStorage('entity_view_display')->loadByProperties([
                'targetEntityType' => $entity->getEntityTypeId(),
                'bundle' => $entity->bundle()
              ]);
              foreach ($entitiesViews as $entityView) {
                /**
                 *
                 * @var \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay
                 */
                if ($entityView instanceof \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay) {
                  $layout_builder = $this->getSectionsForEntityView($entityView);
                  if (!empty($layout_builder['enabled']) && $layout_builder['sections']) {
                    $this->getOverrideScss($layout_builder['sections']);
                  }
                }
              }
            }
            else {
              /**
               * On doit aussi recuperer les styles de base definit.
               *
               * @var array $entitiesViews
               */
              $entitiesViews = $this->entityTypeManager()->getStorage('entity_view_display')->loadByProperties([
                'targetEntityType' => $entity->getEntityTypeId(),
                'bundle' => $entity->bundle()
              ]);
              foreach ($entitiesViews as $entityView) {
                /**
                 *
                 * @var \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay
                 */
                if ($entityView instanceof \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay) {
                  $this->generateSTyleFromEntity($entityView, false);
                  $this->generateStyleFromFieldConfigDisplay($entityView, false);
                  $layout_builder = $this->getSectionsForEntityView($entityView);
                  if (!empty($layout_builder['enabled']) && $layout_builder['sections']) {
                    $this->getOverrideScss($layout_builder['sections']);
                  }
                }
              }
              $this->getAllStylesFromOverrideEntity($entity);
            }
          }
        }
      }
    }
  }
  
  /**
   *
   * @deprecated, car le module cv doit etre supprimer.
   */
  protected function addStyleFromEntitiesOverride() {
    $field_access = \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD;
    foreach ($this->entitiesListLayoutBuilderLayout as $entity_type_id) {
      $query = $this->entityTypeManager()->getStorage($entity_type_id)->getQuery();
      $query->condition('layout_builder__layout', '', '<>');
      $query->condition($field_access, $this->domaine_id);
      $results = $query->accessCheck(false)->execute();
      if (!empty($results)) {
        $entities = $this->entityTypeManager()->getStorage($entity_type_id)->loadMultiple($results);
        foreach ($entities as $content) {
          /**
           * *
           *
           * @var \Drupal\layout_builder\Field\LayoutSectionItemList $LayoutField
           */
          $LayoutField = $content->get('layout_builder__layout');
          $sections = $LayoutField->getSections();
          $section_storage = $entity_type_id . '.' . $entity_type_id . '.' . $content->id();
          $this->generateStyleFromSection($sections, $section_storage);
        }
      }
    }
  }
  
  public function getCurrentblock() {
    $defaultThemeName = $this->getDefaultTheme();
    $blocks = [];
    foreach (\Drupal::entityTypeManager()->getStorage('block')->loadByProperties([
      'theme' => $defaultThemeName,
      'status' => 1
    ]) as $key => $block) {
      $visibility = $block->get('visibility');
      if (!empty($visibility['domain']['domains']) && !empty($visibility['domain']['domains'][$defaultThemeName])) {
        $blocks[$key] = $block;
      }
    }
    return $blocks;
  }
  
  private function EntityHasField($entity_type_id, $fieldname) {
    if (empty($this->fields[$entity_type_id][$fieldname])) {
      $entityFieldManager = $this->EntityFieldManager();
      $fields = $entityFieldManager->getBaseFieldDefinitions($entity_type_id);
      if (!empty($fields[$fieldname])) {
        $this->fields[$entity_type_id][$fieldname] = $fields[$fieldname];
        return true;
      }
    }
    else
      return true;
    return false;
  }
  
  /**
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private function EntityFieldManager() {
    if (!$this->entityFieldManager) {
      
      $this->entityFieldManager = \Drupal::service('entity_field.manager');
    }
    return $this->entityFieldManager;
  }
  
  private function setDefaultDomain() {
    /**
     *
     * @var \Drupal\domain\DomainNegotiator $domain
     */
    if (!$this->domaine_id) {
      $this->domaine_id = lesroidelareno::getCurrentDomainId();
    }
  }
  
  public function setDomaineId($hostname) {
    $this->domaine_id = $hostname;
  }
  
  public function getDomainId() {
    if (!empty($this->domaine_id))
      return $this->domaine_id;
    throw new \ErrorException("Le domaine ne peut etre vide");
  }
}