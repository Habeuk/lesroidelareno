<?php
declare(strict_types = 1);

namespace Drupal\lesroidelareno\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\lesroidelareno\lesroidelareno;

/**
 * Returns responses for lesroidelareno routes.
 */
final class ManageParagraphs extends ControllerBase {
  
  public function DeleteParagraph($paragraph) {
    if (lesroidelareno::isAdministrator()) {
      $entity = $this->entityTypeManager()->getStorage('paragraph')->load($paragraph);
      if ($entity) {
        $this->messenger()->addStatus("La paragraph " . $entity->id() . " a été supprimer ");
      }
      else
        $this->messenger()->addWarning("La paragraph " . $entity->id() . " n'existe plus ");
    }
    return [];
  }
  
  /**
   * Builds the response.
   */
  public function __invoke(Request $request): array {
    /**
     *
     * @var \Drupal\lesroidelareno\HandlerClass\ListBuilderParagraph $ListBuilderParagraph
     */
    $ListBuilderParagraph = $this->entityTypeManager()->getListBuilder("paragraph");
    $contain_domaine = $request->query->get("contain");
    $type_paragraph = $request->query->get("type_paragraph");
    $limit = $request->query->get("limit");
    /**
     *
     * @var \Drupal\Core\Entity\Query\QueryInterface $customQuery
     */
    $customQuery = $ListBuilderParagraph->getQuery();
    if ($contain_domaine)
      $customQuery->condition('field_domain_access', trim($contain_domaine));
    
    if ($type_paragraph)
      $customQuery->condition('type', trim($type_paragraph));
    if ($limit) {
      $limit = (int) $limit;
      if ($limit > 0)
        $ListBuilderParagraph->setLimit($limit);
    }
    
    return [
      "filter" => $this->formBuilder()->getForm("Drupal\lesroidelareno\Form\FilterForm"),
      "collection" => $ListBuilderParagraph->render()
    ];
  }
}
