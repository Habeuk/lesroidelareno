<?php

namespace Drupal\lesroidelareno\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Commerce payment config entity.
 *
 * @ingroup lesroidelareno
 *
 * @ContentEntityType(
 *   id = "commerce_payment_config",
 *   label = @Translation("Commerce payment config"),
 *   handlers = {
 *     "storage" = "Drupal\lesroidelareno\CommercePaymentConfigStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\lesroidelareno\CommercePaymentConfigListBuilder",
 *     "views_data" = "Drupal\lesroidelareno\Entity\CommercePaymentConfigViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\lesroidelareno\Form\CommercePaymentConfigForm",
 *       "add" = "Drupal\lesroidelareno\Form\CommercePaymentConfigForm",
 *       "edit" = "Drupal\lesroidelareno\Form\CommercePaymentConfigForm",
 *       "delete" = "Drupal\lesroidelareno\Form\CommercePaymentConfigDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\lesroidelareno\CommercePaymentConfigHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\lesroidelareno\CommercePaymentConfigAccessControlHandler",
 *   },
 *   base_table = "commerce_payment_config",
 *   revision_table = "commerce_payment_config_revision",
 *   revision_data_table = "commerce_payment_config_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = FALSE,
 *   admin_permission = "administer commerce payment config entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "domain_id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/commerce_payment_config/{commerce_payment_config}",
 *     "add-form" = "/admin/structure/commerce_payment_config/add",
 *     "edit-form" = "/admin/structure/commerce_payment_config/{commerce_payment_config}/edit",
 *     "delete-form" = "/admin/structure/commerce_payment_config/{commerce_payment_config}/delete",
 *     "version-history" = "/admin/structure/commerce_payment_config/{commerce_payment_config}/revisions",
 *     "revision" = "/admin/structure/commerce_payment_config/{commerce_payment_config}/revisions/{commerce_payment_config_revision}/view",
 *     "revision_revert" = "/admin/structure/commerce_payment_config/{commerce_payment_config}/revisions/{commerce_payment_config_revision}/revert",
 *     "revision_delete" = "/admin/structure/commerce_payment_config/{commerce_payment_config}/revisions/{commerce_payment_config_revision}/delete",
 *     "collection" = "/admin/structure/commerce_payment_config",
 *   },
 *   field_ui_base_route = "commerce_payment_config.settings",
 *   constraints = {
 *     "StripeValidationKeys" = {}
 *   }
 * )
 */
class CommercePaymentConfig extends EditorialContentEntityBase implements CommercePaymentConfigInterface {
  
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
    
    // If no revision author has been set explicitly,
    // make the commerce_payment_config owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('domain_id')->target_id;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function setName($domain_id) {
    $this->set('domain_id', $domain_id);
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
   * Permet de determiner si le mode.
   *
   * @return boolean
   */
  public function PaymentMethodIsActive() {
    return $this->get('active')->value ? true : false;
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
  
  public function getPublishableKey() {
    return $this->get('publishable_key')->value;
  }
  
  public function getSecretKey() {
    return $this->get('secret_key')->value;
  }
  
  public function getMode() {
    return $this->get('mode')->value;
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
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    
    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);
    
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')->setLabel(t('Authored by'))->setDescription(t('The user ID of author of the Commerce payment config entity.'))->setRevisionable(TRUE)->setSetting('target_type', 'user')->setSetting('handler', 'default')->setDisplayOptions('view', [
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
    //
    $fields['domain_id'] = BaseFieldDefinition::create('entity_reference')->setLabel(t(' Domaine ID '))->setSetting('target_type', 'domain')->setSetting('handler', 'default')->setDisplayOptions('form', [
      'type' => 'entity_reference_autocomplete',
      'weight' => 5
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setRequired(TRUE);
    
    $fields['payment_plugin_id'] = BaseFieldDefinition::create('string')->setLabel(t('Payment plugin id '))->setSettings([
      'max_length' => 100,
      'text_processing' => 0
    ])->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
      'weight' => -4
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -4
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setRequired(true);
    //
    $fields['publishable_key'] = BaseFieldDefinition::create('string')->setLabel(t('Publishable Key'))->setSettings([
      'max_length' => 100,
      'text_processing' => 0
    ])->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
      'weight' => -4
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -4
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);
    //
    $fields['secret_key'] = BaseFieldDefinition::create('string')->setLabel(t('Secret Key'))->setSettings([
      'max_length' => 100,
      'text_processing' => 0
    ])->setDefaultValue('')->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'string',
      'weight' => -4
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -4
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE)->setConstraints([
      'StripeSecretValidation' => []
    ]);
    //
    $fields['mode'] = BaseFieldDefinition::create('list_string')->setLabel(" mode ")->setDisplayOptions('form', [
      'type' => 'options_buttons',
      'weight' => 5,
      'settings' => array(
        'match_operator' => 'CONTAINS',
        'size' => '10',
        'autocomplete_type' => 'tags',
        'placeholder' => ''
      )
    ])->setDisplayConfigurable('view', TRUE)->setDisplayConfigurable('form', true)->setSettings([
      'allowed_values' => [
        'test' => "Test",
        'live' => "Production"
      ]
    ])->setDefaultValue('test');
    //
    $fields['percent_value'] = BaseFieldDefinition::create('string')->setLabel(t('Percent value'))->setSettings([
      'max_length' => 100,
      'text_processing' => 0
    ])->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'number',
      'weight' => -4,
      'min' => 0
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -4
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);
    //
    $fields['min_value_paid'] = BaseFieldDefinition::create('string')->setLabel(t('Min value paid'))->setSettings([
      'max_length' => 100,
      'text_processing' => 0
    ])->setDisplayOptions('view', [
      'label' => 'above',
      'type' => 'number',
      'weight' => -4,
      'min' => 0
    ])->setDisplayOptions('form', [
      'type' => 'string_textfield',
      'weight' => -4
    ])->setDisplayConfigurable('form', TRUE)->setDisplayConfigurable('view', TRUE);
    //
    $fields['active'] = BaseFieldDefinition::create('boolean')->setLabel(" Activer ")->setDisplayOptions('form', [
      'type' => 'boolean_checkbox',
      'weight' => 3
    ])->setDisplayOptions('view', [])->setDisplayConfigurable('view', TRUE)->setDisplayConfigurable('form', true)->setDefaultValue(true);
    
    $fields['status']->setDescription(t('A boolean indicating whether the Commerce payment config is published.'))->setDisplayOptions('form', [
      'type' => 'boolean_checkbox',
      'weight' => -3
    ]);
    
    $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Created'))->setDescription(t('The time that the entity was created.'));
    
    $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'))->setDescription(t('The time that the entity was last edited.'));
    
    return $fields;
  }
  
}
