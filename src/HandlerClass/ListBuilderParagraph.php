<?php
declare(strict_types = 1);

namespace Drupal\lesroidelareno\HandlerClass;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;

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
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $requestStack;
  /**
   * --
   */
  protected $current_path;
  
  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  
  /**
   *
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => $this->t('ID'),
      'label' => $this->t('Label'),
      'type' => 'Type',
      'utilisation' => 'Utilisation',
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
      'utilisation' => $this->countUtilisation($entity),
      'field_domain_access' => $domains
    ];
    return $row + parent::buildRow($entity);
  }
  
  /**
   * Compte le nombre de fois ou le paragraph est utilisé en function de
   * certains type de contenu.
   */
  protected function countUtilisation(\Drupal\paragraphs\Entity\Paragraph $entity) {
    $connection = $this->getDatabase();
    $paragraph_id = $entity->id();
    /**
     * Liste des entites qui peuvent contenir des paragraphes.
     *
     * @var array $entitiesKeepParagraphs
     */
    $entitiesKeepParagraphs = [
      'blocks_contents',
      'node',
      'taxonomy_term',
      'user',
      'commerce_product',
      'commerce_product_variation',
      'hbk_collection',
      'site_internet_entity',
      'site_type_datas',
      'block_content'
    ];
    $links = [];
    foreach ($entitiesKeepParagraphs as $entity_id) {
      $fields = $this->getListManuelFieldReferenceParagraph($entity_id);
      foreach ($fields as $field_name => $field) {
        if ($field['is_mulple']) {
          $field_parent_reference = 'entity_id';
          $query = $connection->select($entity_id . '__' . $field['field_name'], 'f');
          $query->fields('f', [
            $field_parent_reference
          ]);
          $query->condition('f.' . $field_name . '_target_id', $paragraph_id);
          $result = $query->execute()->fetchAssoc();
        }
        else {
          $field_parent_reference = $field['field_name'];
          $query = $connection->select($entity_id . '_field_data', 'f');
          $query->fields('f', [
            $field_parent_reference
          ]);
          $query->condition('f.' . $field_name, $paragraph_id);
          $result = $query->execute()->fetchAssoc();
        }
        if ($result) {
          $referenceEntity = \Drupal::entityTypeManager()->getStorage($entity_id)->load($result[$field_parent_reference]);
          if ($referenceEntity)
            $links[] = [
              'title' => Markup::create($entity_id . ' | ' . $referenceEntity->id() . ' | ' . $referenceEntity->label()),
              'url' => $referenceEntity->toUrl()
            ];
        }
      }
    }
    if ($links) {
      return [
        'data' => [
          '#theme' => 'links',
          '#links' => $links
        ]
      ];
    }
    return '';
  }
  
  /**
   * Recupere les champs reference au paragraphes qui ont été creer
   * manuellement.
   */
  protected function getListManuelFieldReferenceParagraph($entyty_id) {
    $ids = [];
    /**
     *
     * @var \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
     */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    foreach ($entity_field_manager->getBaseFieldDefinitions($entyty_id) as $field_name => $field) {
      /**
       *
       * @var \Drupal\Core\Field\BaseFieldDefinition $field
       */
      if (($field->getType() == 'entity_reference' || $field->getType() == 'entity_reference_revisions') && $field->getSetting('target_type') == 'paragraph') {
        $ids[$field_name] = [
          'is_mulple' => $field->isMultiple(),
          'field_name' => $field->getName()
        ];
      }
    }
    return $ids;
  }
  
  /**
   * Recupere les champs reference au paragraphes qui ont été creer via
   * l'interface utilisateur.
   */
  protected function getListCreateFieldReferenceParagraph() {
    /**
     *
     * @var \Drupal\Core\Entity\Query\QueryInterface $query
     */
    $query = \Drupal::entityTypeManager()->getStorage('field_storage_config')->getQuery();
    $query->condition('settings.target_type', 'paragraph');
    return $query->execute();
  }
  
  /**
   * Recupere la connexion a la base de donnee.
   */
  protected function getDatabase() {
    if (!$this->database) {
      $this->database = \Drupal::database();
    }
    return $this->database;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = parent::render();
    $query = $this->getEntityListQuery();
    $query->pager(0, NULL);
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
    $query = [
      'query' => [
        'destination' => $this->getCurrentUri()
      ]
    ];
    $operations['voir'] = [
      'title' => 'voir',
      'weight' => -10,
      'url' => $voir_url
    ];
    $delete_url = Url::fromRoute('lesroidelareno.manage_paragraphs.predelete', [
      'paragraph' => $entity->id()
    ], $query);
    $operations['delete'] = [
      'title' => 'delete (Attention)',
      'weight' => 30,
      'url' => $delete_url
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
  
  /**
   * Gets the request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request The request object.
   */
  protected function getRequest() {
    if (!$this->requestStack) {
      $this->requestStack = \Drupal::service('request_stack');
    }
    return $this->requestStack->getCurrentRequest();
  }
  
  protected function getCurrentUri() {
    if (!$this->current_path)
      $this->current_path = $this->getRequest()->getRequestUri();
    return $this->current_path;
  }
}