<?php
declare(strict_types = 1);

namespace Drupal\lesroidelareno\HandlerClass;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Url;

/**
 * Provides a custom list builder for MyEntity entities.
 */
class ListBuilderParagraph extends EntityListBuilder {
  /**
   * The number of entities to list per page, or FALSE to list all entities.
   *
   * For example, set this to FALSE if the list uses client-side filters that
   * require all entities to be listed (like the views overview).
   *
   * @var int|false
   */
  protected $limit = 10;
  
  /**
   *
   * @var QueryInterface
   */
  protected $customQuery;
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => $this->t('ID'),
      'label' => $this->t('Label'),
      'type' => 'Type',
      'field_domain_access' => 'Domain'
    ];
    return $header + parent::buildHeader();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /**
     *
     * @var \Drupal\paragraphs\Entity\Paragraph $entity
     */
    $domains = '';
    foreach ($entity->get('field_domain_access')->getValue() as $value) {
      if (!empty($value))
        $domains .= $value['target_id'] . ' | ';
    }
    $row = [
      'id' => $entity->id(),
      'label' => $entity->label(),
      'type' => $entity->bundle(),
      'field_domain_access' => $domains
    ];
    return $row + parent::buildRow($entity);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = parent::render();
    $query = $this->getEntityListQuery();
    $total = $query->count()->execute();
    $build['summary']['#markup'] = $this->t('Total paragraphs: @total', [
      '@total' => $total
    ]);
    return $build;
  }
  
  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *        The entity the operations are for.
   *        
   * @return array The array structure is identical to the return value of
   *         self::getOperations().
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = [];
    $voir_url = Url::fromRoute('entity.paragraph.canonical', [
      'paragraph' => $entity->id()
    ]);
    $operations['voir'] = [
      'title' => 'voir',
      'weight' => 10,
      'url' => $voir_url
    ];
    return $operations + parent::getDefaultOperations($entity);
  }
  
  /**
   *
   * @param QueryInterface $query
   * @return QueryInterface
   */
  public function getQuery(): QueryInterface {
    $this->customQuery = parent::getEntityListQuery();
    return $this->customQuery;
  }
  
  /**
   *
   * @param QueryInterface $query
   */
  public function setQuery(QueryInterface $query) {
    $this->customQuery = $query;
  }
  
  public function setLimit(int $value) {
    $this->limit = $value;
  }
  
  /**
   * Returns a query object for loading entity IDs from the storage.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface A query object used to
   *         load entity IDs.
   */
  protected function getEntityListQuery(): QueryInterface {
    if (!$this->customQuery) {
      $this->customQuery = parent::getEntityListQuery();
    }
    return $this->customQuery;
  }
}