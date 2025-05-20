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
   *
   * @var \Drupal\layout_custom_style\StyleScssPluginManager
   */
  protected $StyleScssPlugin;
  
  function __construct(LayoutgenentitystylesServices $serviceInner, $SectionStorageManager, $LoadStyleFromMod, $ConfigFactory, $ManageFileCustomStyle, $ManageFileMailStyle) {
    $this->serviceInner = $serviceInner;
    $this->domaine_id = $this->setDefaultDomain();
    parent::__construct($SectionStorageManager, $LoadStyleFromMod, $ConfigFactory, $ManageFileCustomStyle, $ManageFileMailStyle);
  }
  
  /**
   * On recupere la liste des plugins d'affichage d'entite validé en funcion de
   * la configurations.
   */
  public function getListSectionStorages() {
    if (!$this->sectionStorages) {
      /**
       * Contient tous les modes d'affichage sans filtre par domaine.
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
            $has__field_domain_access = (bool) $field_definitions[$field_access] ?? false;
            $has__field_all_access = (bool) $field_definitions[$field_all_access] ?? false;
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
    }
    return $this->sectionStorages;
  }
  
  /**
   * Permet de generer tous les styles et de les ajouter dans la configuration
   * du theme actif.
   */
  function generateAllFilesStyles() {
    $this->serviceInner->generateAllFilesStyles();
    $this->getComponentsOverrides();
    $this->addStyleFromEntitiesOverride();
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\layoutgenentitystyles\Services\LayoutgenentitystylesServices::getCurrentTheme()
   */
  public function getCurrentTheme() {
    if (!$this->domaine_id) {
      $defaultThemeName = \Drupal::config('system.theme')->get('default');
    }
    else
      $defaultThemeName = $this->domaine_id;
    return $defaultThemeName;
  }
  
  /**
   * Specifique à wb-horizon.
   */
  protected function getComponentsOverrides() {
    $fied_access = \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD;
    $query = $this->entityTypeManager()->getStorage('paragraph')->getQuery();
    $query->condition($fied_access, $this->domaine_id);
    $ids = $query->accessCheck(TRUE)->execute();
    // dump($ids);
    if ($ids) {
      $entities = $this->entityTypeManager->getStorage('paragraph')->loadMultiple($ids);
      foreach ($entities as $entity) {
        if (method_exists($entity, 'hasField')) {
          if ($entity->hasField('layout_builder__layout')) {
            
            $sections = [];
            $listSetions = $entity->get('layout_builder__layout')->getValue();
            $section_storage = $entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $entity->id();
            foreach ($listSetions as $value) {
              $sections[] = reset($value);
            }
            $this->getOverrideScss($sections);
            $this->generateStyleFromSection($sections, $section_storage);
          }
        }
      }
    }
  }
  
  /**
   * --
   */
  protected function addStyleFromEntitiesOverride() {
    $conf = $this->getConfigFOR_generate_style_theme();
    if ($conf['tab1']['use_domain']) {
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
  }
  
  /**
   * Specifique à wb-horizon.
   * Permet de recuperer les styles surcharger et de les ajouter en BD afin que
   * le fichier custom.scss puisse etre generer avec du bon contenu.
   */
  protected function getOverrideScss($sections) {
    if (\Drupal::moduleHandler()->moduleExists('lesroidelareno')) {
      // On charge
      foreach ($sections as $section) {
        /**
         *
         * @var \Drupal\layout_builder\Section $section
         */
        $storage = $section->getLayoutSettings();
        $this->loadPluginScss()->addConfigs($storage);
      }
    }
  }
  
  /**
   * Specifique à wb-horizon.
   *
   * @return \Drupal\layout_custom_style\StyleScssPluginManager
   */
  protected function loadPluginScss() {
    if (!$this->StyleScssPlugin) {
      $this->StyleScssPlugin = \Drupal::service('plugin.manager.style_scss');
    }
    return $this->StyleScssPlugin;
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
}