<?php

namespace Drupal\lesroidelareno\Services\layoutgenentitystyles;

use Drupal\layoutgenentitystyles\Services\ParagraphLoader;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\lesroidelareno\lesroidelareno;

/**
 *
 * @author stephane
 *        
 */
class OverrideParagraphLoader extends ParagraphLoader {
  /**
   *
   * @var string
   */
  private $field_access = \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD;
  /**
   *
   * @var array
   */
  protected $checkFieldDomainAccess = [];
  /**
   *
   * @var string
   */
  protected $fielNameDomainAccess = \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD;
  
  /**
   *
   * @var ParagraphLoader
   */
  protected $serviceInner;
  
  /**
   *
   * @var string
   */
  private $domaine_id;
  
  public function __construct(ParagraphLoader $serviceInner, $entityTypeManager, $entityFieldManager) {
    $this->serviceInner = $serviceInner;
    parent::__construct($entityTypeManager, $entityFieldManager);
  }
  
  /**
   * [NB: il faudra comprendre pourquoi cette fonction s'execute 3 foix et avec
   * des valeurs de $entities differents ]
   * L'environement de wb-horizon est particulier, on doit s'appuyer uniquement
   * sur l'entité "site_internet_entity" afin de recuperer tous les styles.
   *
   * {@inheritdoc}
   * @see \Drupal\layoutgenentitystyles\Services\ParagraphLoader::findParagraphReferenceFields()
   */
  public function findParagraphReferenceFields(array $entities) {
    $paragraph_fields = [];
    if ($this->getDomainId() == 'wb_horizon_com') {
      // $this->findParagraphReferenceFieldsWBh($paragraph_fields);
      $paragraph_fields = $this->serviceInner->findParagraphReferenceFields([
        'block_content',
        'blocks_contents',
        'site_internet_entity'
      ]);
    }
    else {
      $paragraph_fields = $this->serviceInner->findParagraphReferenceFields($entities);
    }
    return $paragraph_fields;
  }
  
  /**
   *
   * @param array $paragraph_fields
   */
  private function findParagraphReferenceFieldsWBh(array &$paragraph_fields, $entity_type_id = "site_internet_entity", $bundle = null) {
    if ($bundle)
      $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    else
      $fields = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    /**
     *
     * @var \Drupal\Core\Entity\EntityStorageInterface $EntityStorage
     */
    $EntityStorage = $this->entityTypeManager->getStorage($entity_type_id);
    $table = $EntityStorage->getEntityType()->getBaseTable();
    $id = $EntityStorage->getEntityType()->getKey('id');
    $references_entities = [];
    foreach ($fields as $field_name => $field_info) {
      /**
       *
       * @var \Drupal\Core\Field\BaseFieldDefinition $field_info
       */
      if (($field_info->getType() == 'entity_reference' || $field_info->getType() == 'entity_reference_revisions') && $field_info instanceof \Drupal\Core\Field\BaseFieldDefinition) {
        // On recupere tous les entités en relations.
        if (!$field_info->offsetGet('read-only') && !str_contains($field_name, "user") && !str_contains($field_name, "domain") && !str_contains($field_name, "translation") && !str_contains(
          $field_name, "entities_duplicate")) {
          $ids = $this->getentitiesReferences($references_entities, $entity_type_id, $field_name, $id);
          if ($ids) {
            $target_type = $field_info->getSetting("target_type");
            $subStorages = $this->entityTypeManager->getStorage($target_type);
            foreach ($ids as $value) {
              /**
               *
               * @var \Drupal\paragraphs\Entity\Paragraph $subEntity
               */
              $subEntity = $subStorages->load($value[$field_name . '_target_id']);
              if ($subEntity) {
                $paragraph_fields[$subEntity->getEntityTypeId()] = [];
                $this->findParagraphReferenceFieldsWBh($paragraph_fields, $subEntity->getEntityTypeId(), $subEntity->bundle());
              }
            }
          }
        }
      }
      /**
       *
       * @var \Drupal\Core\Field\BaseFieldDefinition $field_info
       */
      if ($field_info->getSetting('target_type') === 'paragraph') {
        $paragraph_fields[$entity_type_id][$field_name] = $field_name;
      }
    }
  }
  
  private function getentitiesReferences(&$references_entities, $entity_type_id, $field, $id) {
    $connexion = \Drupal::database();
    
    //
    $tableField = $entity_type_id . '__' . $field;
    /**
     *
     * @var \Drupal\mysql\Driver\Database\mysql\Select $query
     */
    $query = $connexion->select($tableField, $tableField)->fields($tableField);
    //
    $tableJoin = $entity_type_id . '__' . $this->field_access;
    $condition = $tableJoin . '.entity_id = ' . $tableField . '.entity_id  and ' . $tableJoin . "." . $this->field_access . "_target_id  = '" . $this->getDomainId() . "'";
    $query->addJoin('INNER', $tableJoin, $tableJoin, $condition);
    $query->addField($tableJoin, 'field_domain_access_target_id');
    //
    // dump($entity_type_id . ' -- ' . $field);
    // dd($query->execute()->fetchAll(\PDO::FETCH_ASSOC));
    return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\layoutgenentitystyles\Services\ParagraphLoader::updateQuery()
   */
  public function updateQuery($table, $entity_type_id, $field, $id, EntityStorageInterface $EntityStorage) {
    
    /**
     *
     * @var \Drupal\mysql\Driver\Database\mysql\Select $query
     */
    $query = $this->serviceInner->updateQuery($table, $entity_type_id, $field, $id, $EntityStorage);
    if ($this->hasFieldDomainAccess($entity_type_id)) {
      $tableJoin = $entity_type_id . '__' . $this->field_access;
      $condition = $tableJoin . '.entity_id = ' . $table . '.' . $id . " and " . $tableJoin . "." . $this->field_access . "_target_id  = '" . $this->getDomainId() . "'";
      $query->addJoin('INNER', $tableJoin, $tableJoin, $condition);
      $query->addField($tableJoin, 'field_domain_access_target_id');
    }
    return $query;
  }
  
  public function setDomaineId($hostname) {
    $this->domaine_id = $hostname;
  }
  
  public function getDomainId() {
    if (!empty($this->domaine_id))
      return $this->domaine_id;
    throw new \ErrorException("Le domaine ne peut etre vide");
  }
  
  /**
   *
   * @param string $entity_type_id
   */
  protected function hasFieldDomainAccess($entity_type_id) {
    if (empty($this->checkFieldDomainAccess[$entity_type_id])) {
      $fields = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
      $hasVield = false;
      if (!empty($fields[$this->field_access]))
        $hasVield = true;
      $this->checkFieldDomainAccess[$entity_type_id][$this->field_access] = $hasVield;
    }
    return $this->checkFieldDomainAccess[$entity_type_id][$this->field_access] ?? false;
  }
}