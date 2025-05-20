<?php

namespace Drupal\lesroidelareno\Services\layoutgenentitystyles;

use Drupal\layoutgenentitystyles\Services\ParagraphLoader;
use Drupal\Core\Entity\EntityStorageInterface;

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
  protected $fielNameDomainAccess = \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD;
  
  /**
   *
   * @var ParagraphLoader
   */
  protected $serviceInner;
  
  public function __construct(ParagraphLoader $serviceInner, $entityTypeManager, $entityFieldManager) {
    $this->serviceInner = $serviceInner;
    parent::__construct($entityTypeManager, $entityFieldManager);
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\layoutgenentitystyles\Services\ParagraphLoader::updateQuery()
   */
  public function updateQuery($table, $entity_type_id, $field, $id, EntityStorageInterface $EntityStorage) {
    // $EntityStorage->getEntityType();
    /**
     *
     * @var \Drupal\mysql\Driver\Database\mysql\Select $query
     */
    $query = $this->serviceInner->updateQuery($table, $entity_type_id, $field, $id, $EntityStorage);
    //
    $tableJoin = $entity_type_id . '__' . $field;
    $condition = $tableJoin . '.entity_id = ' . $table . '.' . $id;
    $query->addJoin('INNER', $tableJoin, $tableJoin, $condition);
    //
    return $query;
  }
}