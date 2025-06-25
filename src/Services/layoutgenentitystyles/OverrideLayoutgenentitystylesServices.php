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
      $paragraph_loader = \Drupal::service('layoutgenentitystyles.paragraph_loader');
      $paragraph_loader->setDomaineId($this->domaine_id);
      /**
       * Contient tous les modes d'affichage sans filtre par domaine.
       * ( au lieu de faire parent::, il faut tout reconstruire ).
       *
       * @var array $sectionStorages
       */
      $DefaultSectionStorages = $this->serviceInner->getListSectionStorages();
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
      if ($this->getDomainId() == 'wb_horizon_com0') {
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
      }
    }
    //
    $this->getComponentsOverrides();
    return $this->sectionStorages;
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
              $key = $storage->getEntityType()->getKey('bundle');
              $query = $storage->getQuery();
              $query->condition($field_access, $this->getDomainId());
              if ($key)
                $query->condition($key, $bundle_id);
              $ids = $query->accessCheck(TRUE)->execute();
              if ($ids)
                if ($layout_builder['allow_custom']) {
                  $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple($ids);
                  if ($entities) {
                    // on ajoute les styles par defaut.
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