<?php

namespace Drupal\lesroidelareno;

/**
 * Ce fichier permet de mettre sur pied un enssemble d'outil permettant de
 * corriger de maniere rapide les erreurs liées au module paragraph.
 *
 * @author stephane
 *
 */
class ParagraphCorrection {

  /**
   * Recupere les entitées qui utilise un type de paragraph qui a été
   * supprimé.
   */
  static function getEntityUnInstall($paragraph_type_id, $id) {
    $entities = [
      'block_content',
      'node',
      'commerce_product',
      'cv_entity',
      'model_cv',
      'site_internet_entity',
      'site_type_datas'
    ];
    $results = [];
    /**
     * --
     */
    foreach ($entities as $base_table) {
      $table = $base_table . '__layout_paragraphs';
      $idColumn = 'id';
      switch ($base_table) {
        case 'commerce_product':
          $idColumn = 'product_id';
          break;

        case 'node':
          $idColumn = 'nid';
          break;
      }
      /**
       *
       * @var \Drupal\Core\Database\Connection $dateBase
       */
      $dateBase = \Drupal::database();
      if ($dateBase->schema()->tableExists($table)) {
        $query = $dateBase->select($table, 'n');
        $query->addJoin('INNER', $base_table . '_field_data', 'bt', 'n.entity_id=bt.' . $idColumn);
        $query->fields('n', [
          'bundle',
          'layout_paragraphs_target_id',
          'langcode',
          'entity_id'
        ]);
        $query->fields('bt');
        //
        $query->condition('bundle', $paragraph_type_id);
        $results[] = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
      }
    }
    return $results;
  }

}