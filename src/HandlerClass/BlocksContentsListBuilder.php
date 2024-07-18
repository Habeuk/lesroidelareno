<?php

namespace Drupal\lesroidelareno\HandlerClass;

use Drupal\Core\Entity\EntityInterface;
use Drupal\blockscontent\BlocksContentsListBuilder as BlocksContentsListBuilderDefault;
use Drupal\Core\Link;

class BlocksContentsListBuilder extends BlocksContentsListBuilderDefault {
  protected $field_access = \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD;
  protected $field_source = \Drupal\domain_source\DomainSourceElementManagerInterface::DOMAIN_SOURCE_FIELD;
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = parent::buildHeader();
    $header[$this->field_access] = 'field_access';
    $header[$this->field_source] = 'field_source';
    return $header;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\blockscontent\Entity\BlocksContents $entity */
    $row = parent::buildRow($entity);
    $row[$this->field_access] = $entity->get($this->field_access)->target_id;
    $row[$this->field_source] = $entity->get($this->field_source)->target_id;
    return $row;
  }
  
}