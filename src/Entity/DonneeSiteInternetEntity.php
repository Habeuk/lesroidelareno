<?php

namespace Drupal\lesroidelareno\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;
use Jawira\CaseConverter\Convert;
use Stephane888\Debug\Repositories\ConfigDrupal;

/**
 * Defines the Donnee site internet des utilisateurs entity.
 *
 * @ingroup lesroidelareno
 *
 * @ContentEntityType(
 *   id = "donnee_internet_entity",
 *   label = @Translation("Donnee site internet des utilisateurs"),
 *   handlers = {
 *     "storage" = "Drupal\lesroidelareno\DonneeSiteInternetEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\lesroidelareno\DonneeSiteInternetEntityListBuilder",
 *     "views_data" = "Drupal\lesroidelareno\Entity\DonneeSiteInternetEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\lesroidelareno\Form\DonneeSiteInternetEntityForm",
 *       "add" = "Drupal\lesroidelareno\Form\DonneeSiteInternetEntityForm",
 *       "edit" = "Drupal\lesroidelareno\Form\DonneeSiteInternetEntityForm",
 *       "delete" = "Drupal\lesroidelareno\Form\DonneeSiteInternetEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\lesroidelareno\DonneeSiteInternetEntityHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\lesroidelareno\DonneeSiteInternetEntityAccessControlHandler",
 *   },
 *   base_table = "donnee_internet_entity",
 *   revision_table = "donnee_internet_entity_revision",
 *   revision_data_table = "donnee_internet_entity_field_revision",
 *   translatable = FALSE,
 *   admin_permission = "administer donnee site internet des utilisateurs entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/donnee-site/user/donnee_internet_entity/{donnee_internet_entity}",
 *     "add-form" = "/donnee-site/user/donnee_internet_entity/add",
 *     "edit-form" = "/donnee-site/user/donnee_internet_entity/{donnee_internet_entity}/edit",
 *     "delete-form" = "/donnee-site/user/donnee_internet_entity/{donnee_internet_entity}/delete",
 *     "version-history" = "/donnee-site/user/donnee_internet_entity/{donnee_internet_entity}/revisions",
 *     "revision" = "/donnee-site/user/donnee_internet_entity/{donnee_internet_entity}/revisions/{donnee_internet_entity_revision}/view",
 *     "revision_revert" = "/donnee-site/user/donnee_internet_entity/{donnee_internet_entity}/revisions/{donnee_internet_entity_revision}/revert",
 *     "revision_delete" = "/donnee-site/user/donnee_internet_entity/{donnee_internet_entity}/revisions/{donnee_internet_entity_revision}/delete",
 *     "collection" = "/donnee-site/user/donnee_internet_entity",
 *   },
 *   field_ui_base_route = "donnee_internet_entity.settings"
 * )
 */
class DonneeSiteInternetEntity extends EditorialContentEntityBase implements DonneeSiteInternetEntityInterface {
  
  use EntityChangedTrait;
  use EntityPublishedTrait;
  
  /**
   *
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id()
    ];
  }
  
  /**
   *
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    
    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    
    return $uri_route_parameters;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // On redefinit l'id de l'utilisateur juste avant la premiere sauvegarde.
    if ($this->isNew())
      $this->setOwnerId(\Drupal::currentUser()->id());
    // If no revision author has been set explicitly,
    // make the donnee_internet_entity owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }
  
  function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    //
    $this->saveAnotherDatas();
  }
  
  /**
   * Enregistre d'autres données.
   */
  protected function saveAnotherDatas() {
    $string_nbre = strlen($this->getName());
    // On cree l'entite qui permettra de creer le domaine sur OVH.
    if ($string_nbre >= 3) {
      $textConvert = new Convert($this->enleverCaracteresSpeciaux($this->getName()));
      $sub_domain = $textConvert->toKebab();
      $sub_domain = preg_replace('/[^a-z0-9\-]/', "", $sub_domain);
      // on limite le domaine à 20 carataire.
      /**
       * La longeur maximal est de 64 carateres pour un sous-domaine.
       * wb-horizon => 15 carateres.
       * on peut prendre un sous domaine 25 carataires ( 15+25=40 ).
       */
      $sub_domain = substr($sub_domain, 0, 25);
      // Verifie si le nom de domaine existe deja.
      $query = $this->entityTypeManager()->getStorage('domain_ovh_entity')->getQuery();
      $query->condition('sub_domain', $sub_domain . "%", 'LIKE');
      $entities = $query->execute();
      if (!empty($entities)) {
        // cette regle ne permet pas de garantir l'unicité des valeurs du
        // champs.
        // $sub_domain .= count($entities) + 1;
        
        /**
         * Example : un cas de figure ou on a en bd :
         * - test2
         * - test3
         * Si l'utilisateur saisie test, le resultat va etre "test3".( bug ).
         */
        //
        $query = $this->entityTypeManager()->getStorage('domain_ovh_entity')->getQuery();
        $query->sort('id', 'DESC');
        $query->range(0, 1);
        $ids = $query->execute();
        $id = reset($ids) + 1;
        $sub_domain .= $id;
      }
      // On le cree si et seulement s'il n'est pas deja crée.
      if (empty($this->getDomainOvhEntity())) {
        $conf = ConfigDrupal::config('ovh_api_rest.settings');
        if (empty($conf['zone_name'])) {
          /**
           *
           * @var \Psr\Log\LoggerInterface $logger
           */
          $logger = \Drupal::logger('generate_style_theme');
          $logger->warning(" Le module ovh n'est pas correctement configurer ");
          throw new \LogicException(" Le module ovh n'est pas correctement configurer ");
        }
        //
        
        //
        $DomainOvh = \Drupal\ovh_api_rest\Entity\DomainOvhEntity::create();
        $DomainOvh->set('name', ' Generate domain : ' . $this->getName());
        // $DomainOvh->set('zone_name', $conf['zone_name']);
        // $DomainOvh->set('field_type', $conf['field_type']);
        
        $DomainOvh->setSubDomain($sub_domain);
        // $DomainOvh->set('target', $conf['target']);
        // $DomainOvh->set('path', $conf['path']);
        $domaineHost = $DomainOvh->getsubDomain() . '.' . $DomainOvh->getZoneName();
        // On cree le domain.
        $domain = \Drupal\vuejs_entity\VuejsEntity::createDomainFromData($domaineHost);
        if ($domain) {
          $domain->id();
          $DomainOvh->set('domain_id_drupal', $domain->id());
          $DomainOvh->save();
          //
          if ($DomainOvh->id()) {
            $this->setDomainOvhEntity($DomainOvh->id());
            $this->save();
          }
          else
            throw new \Exception(" Impossible de creer ou de recuperer le DomainOvhEntity. ");
        }
        else
          throw new \Exception(" Impossible de creer ou de recuperer le domain. ");
      }
    }
    else {
      throw new \Exception(" Nombre de caractaire inssuffisant ");
    }
  }
  
  private function enleverCaracteresSpeciaux($text) {
    $utf8 = [
      '/[áàâãªä]/u' => 'a',
      '/[ÁÀÂÃÄ]/u' => 'A',
      '/[ÍÌÎÏ]/u' => 'I',
      '/[íìîï]/u' => 'i',
      '/[éèêë]/u' => 'e',
      '/[ÉÈÊË]/u' => 'E',
      '/[óòôõºö]/u' => 'o',
      '/[ÓÒÔÕÖ]/u' => 'O',
      '/[úùûü]/u' => 'u',
      '/[ÚÙÛÜ]/u' => 'U',
      '/ç/' => 'c',
      '/Ç/' => 'C',
      '/ñ/' => 'n',
      '/Ñ/' => 'N'
    ];
    return preg_replace(array_keys($utf8), array_values($utf8), $text);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }
  
  /**
   *
   * @param integer $target_id
   */
  public function setTypeHomePage($target_id) {
    $this->set('type_home_page', $target_id);
  }
  
  /**
   *
   * @param integer $target_id
   */
  public function setDomainOvhEntity($target_id) {
    $this->set('domain_ovh_entity', $target_id);
  }
  
  public function getDomainOvhEntity() {
    return $this->get('domain_ovh_entity')->target_id;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    
    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')->setLabel(t('Authored by'))->setDescription(t('The user ID of author of the Donnee site internet des utilisateurs entity.'))->setRevisionable(TRUE)->setSetting('target_type', 'user')->setSetting('handler', 'default')->setDisplayOptions('view', [
      'label' => 'hidden',
      'type' => 'author',
      'weight' => 0
    ])->setDisplayOptions('form', [
      'type' => 'entity_reference_autocomplete',
      'weight' => 5,
      'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => '60',
        'autocomplete_type' => 'tags',
        'placeholder' => ''
      ]
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);
    
    $fields['domain_ovh_entity'] = BaseFieldDefinition::create('entity_reference')->setLabel(t('Domaine OVH'))->setSetting('target_type', 'domain_ovh_entity')->setSetting('handler', 'default')->setDisplayOptions('form', [
      'type' => 'entity_reference_autocomplete',
      'weight' => 5
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);
    
    // 1
    $fields['name'] = BaseFieldDefinition::create('string')->setLabel(t(" What is the name of your business "))->setDescription(t(' You can change it at any time '))->setRevisionable(TRUE)->setSettings([
      'max_length' => 50,
      'text_processing' => 0
    ])->setDefaultValue('')->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
      'weight' => -4
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -4
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setRequired(TRUE)->addConstraint('UniqueField', [])->setTranslatable(TRUE);
    
    // 2 On doit preciser le taxo plus tard.( ce dernier vient de module
    // creation de site virtuel ).
    $fields['type_site'] = BaseFieldDefinition::create('entity_reference')->setLabel(t(" What type of site do you want to create? "))->setDisplayOptions('form', [
      'type' => 'options_select',
      'weight' => 5,
      'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => '60',
        'placeholder' => ''
      ]
    ])->setDisplayConfigurable('view', TRUE)->setDisplayConfigurable('form', true)->setDescription(t(" Select a field related or close to your activity "))->setSetting('handler_settings', [
      'target_bundles' => [
        'typesite' => 'typesite'
      ],
      'sort' => [
        'field' => 'name',
        'direction' => 'asc'
      ],
      'auto_create' => false,
      'auto_create_bundle' => ''
    ])->setSetting('target_type', 'taxonomy_term')->setSetting('handler', 'default')->setRevisionable(TRUE);
    
    // // 3
    $fields['type_color_theme'] = BaseFieldDefinition::create('boolean')->setLabel(t(" How would you like to define the colors? "))->setDisplayOptions('form', [
      'type' => 'options_buttons',
      'weight' => -3,
      'settings' => []
    ])->setDisplayOptions('view', [])->setDisplayConfigurable('view', TRUE)->setDisplayConfigurable('form', true)->setSetting('on_label', t("Select colors"))->setSetting('off_label', t('Select a color theme'));
    
    // 3.1
    $fields['color_primary'] = BaseFieldDefinition::create('color_theme_field_type')->setLabel(t(' Primary color '))->setRequired(TRUE)->setDefaultValue([
      'color' => '#CE3B3B',
      'name' => ''
    ])->setDisplayConfigurable('form', true)->setDisplayConfigurable('view', TRUE);
    //
    $fields['color_secondary'] = BaseFieldDefinition::create('color_theme_field_type')->setLabel(t(' Secondary color '))->setRequired(TRUE)->setDefaultValue([
      'color' => '#DD731D',
      'name' => ''
    ])->setDisplayConfigurable('form', true)->setDisplayConfigurable('view', TRUE);
    //
    $fields['color_linkhover'] = BaseFieldDefinition::create('color_theme_field_type')->setLabel(t(' Link color '))->setRequired(TRUE)->setDefaultValue([
      'color' => '#F88C12',
      'name' => ''
    ])->setDisplayConfigurable('form', true)->setDisplayConfigurable('view', TRUE);
    //
    $fields['background'] = BaseFieldDefinition::create('color_theme_field_type')->setLabel(t(" Background color "))->setRequired(TRUE)->setDefaultValue([
      'color' => '#0F103E',
      'name' => ''
    ])->setDisplayConfigurable('form', true)->setDisplayConfigurable('view', TRUE);
    // 3.2
    $fields['site_theme_color'] = BaseFieldDefinition::create('list_string')->setLabel(t(" Choose a color theme "))->setRequired(TRUE)->setSetting('allowed_values_function', [
      '\Drupal\lesroidelareno\LesroidelarenoFormDonneeSite',
      'getListThemeColor'
    ])->setDisplayOptions('view', [
      'label' => 'above'
    ])->setDisplayOptions('form', [
      'type' => 'options_buttons',
      'settings' => [],
      'weight' => -3
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setTranslatable(true);
    
    // 4 choix du model de la page d'acceuil.
    $fields['type_home_page'] = BaseFieldDefinition::create('entity_reference')->setLabel(t(" Choose your homepage design "))->setRevisionable(TRUE)->setSetting('target_type', 'site_type_datas')->setSetting('handler', 'default')->setDisplayOptions('view', [
      'label' => 'hidden',
      'type' => 'author',
      'weight' => 0
    ])->setDisplayOptions('form', [
      'type' => 'selectfilter_theme',
      'weight' => 5,
      'settings' => []
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setDescription(t(" The theme colors will be updated during the creation of your model, you could always modify them. "));
    
    // 5 Choix des pages que lon souhaite avoir.
    $fields['pages'] = BaseFieldDefinition::create('list_string')->setLabel(t(" Please select the pages "))->setRequired(TRUE)->setSetting('allowed_values_function', [
      '\Drupal\lesroidelareno\LesroidelarenoFormDonneeSite',
      'getListPages'
    ])->setDisplayOptions('view', [
      'label' => 'above'
    ])->setDisplayOptions('form', [
      'type' => 'options_buttons',
      'settings' => [],
      'weight' => -3
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setCardinality(-1);
    
    // 6 Avez vous du contenus.
    $fields['status']->setDescription(t(" A boolean indicating whether the Donnee site internet des utilisateurs is published. "))->setDisplayOptions('form', [
      'type' => 'boolean_checkbox',
      'weight' => -3
    ]);
    
    // 7
    $fields['has_contents'] = BaseFieldDefinition::create('boolean')->setLabel(t(" Do you have content for your different pages? "))->setRequired(true)->setDisplayOptions('form', [
      'type' => 'options_buttons',
      'weight' => -3
    ])->setDisplayOptions('view', [])->setDisplayConfigurable('view', TRUE)->setDisplayConfigurable('form', true)->setSetting('on_label', "Oui")->setSetting('off_label', 'Non')->setDescription(t("
     Si vous n'avez pas de contenu, nous pouvons vous accompagnez dans sa redaction. "));
    
    // 7.1 => l'utilisateur a du contenu.
    $fields['image_logo'] = BaseFieldDefinition::create('image')->setLabel(t("Please insert company logo"))->setRequired(false)->setDisplayConfigurable('form', [
      'type' => 'image'
    ])->setDisplayConfigurable('view', TRUE)->setSetting("min_resolution", "250x250")->setSetting('alt_field', false)->setSetting('alt_field_required', false);
    //
    $fields['description'] = BaseFieldDefinition::create('text_long')->setLabel(" Description ")->setSettings([
      'text_processing' => 0
      // 'html_format' => "text_code"
    ])->setRequired(TRUE)->setDisplayConfigurable('form', true)->setDisplayConfigurable('view', TRUE)->setDisplayOptions('form', [
      'type' => 'text_textarea',
      'weight' => 0
    ])->setDisplayOptions('view', [
      'label' => 'hidden',
      'type' => 'text_default',
      'weight' => 0
    ])->setTranslatable(TRUE);
    
    //
    $fields['contenus_transferer'] = BaseFieldDefinition::create('file')->setLabel(t("Add pictures"))->setDisplayConfigurable('form', true)->setDisplayConfigurable('view', TRUE)->setRequired(false)->setSettings([
      'target_type' => 'file',
      'display_field' => false,
      'display_default' => false,
      'uri_scheme' => 'public', // private
      'file_extensions' => 'doc docx pdf zip jpg png git tar gz rtf',
      'file_directory' => "[date:custom:Y]-[date:custom:m]",
      'max_filesize' => "",
      'description_field' => "",
      'handler' => 'default',
      'handler_settings' => []
    ])->setDescription(t(' You can zip your content to transfer it faster '))->setSetting('default_image', [
      'target_id' => 1406,
      'uuid' => '21da205e-97b5-4817-b746-4da3d6a53813',
      'width' => 100,
      'height' => 100,
      'alt' => '',
      'title' => ''
    ])->setCardinality(-1);
    
    //
    $fields['contenus_transferer_txt'] = BaseFieldDefinition::create('file')->setLabel(t('Add texts'))->setDisplayConfigurable('form', true)->setDisplayConfigurable('view', TRUE)->setRequired(false)->setSettings([
      'target_type' => 'file',
      'display_field' => false,
      'display_default' => false,
      'uri_scheme' => 'public', // private
      'file_extensions' => 'doc docx pdf zip jpg png git tar gz rtf',
      'file_directory' => "[date:custom:Y]-[date:custom:m]",
      'max_filesize' => "",
      'description_field' => "",
      'handler' => 'default',
      'handler_settings' => []
    ])->setDescription(t(' You can zip your content to transfer it faster '))->setSetting('default_image', [
      'target_id' => 1406,
      'uuid' => '21da205e-97b5-4817-b746-4da3d6a53813',
      'width' => 100,
      'height' => 100,
      'alt' => '',
      'title' => ''
    ])->setCardinality(-1);
    
    // 8
    $fields['demande_traitement'] = BaseFieldDefinition::create('boolean')->setLabel(t('Are your data complete?'))->setDisplayOptions('form', [
      'type' => 'options_buttons',
      'weight' => -3
    ])->setDisplayOptions('view', [])->setDisplayConfigurable('view', TRUE)->setDisplayConfigurable('form', true)->setSetting('on_label', "Oui")->setSetting('off_label', 'Non
    ')->setDescription(t("NB: we will start building your site if you have selected 'yes'. "));
    
    // 9
    $fields['traitement_encours'] = BaseFieldDefinition::create('list_string')->setLabel(t("Construction in progress ..."))->setDisplayOptions('form', [
      'type' => 'options_buttons',
      'weight' => -3
    ])->setSetting('allowed_values_function', [
      '\Drupal\lesroidelareno\LesroidelarenoFormDonneeSite',
      'StatusTraitement'
    ]);
    
    $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Created'))->setDescription(t(' The time that the entity was created. '));
    
    $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'))->setDescription(t(' The time that the entity was last edited. '));
    
    return $fields;
  }
  
}