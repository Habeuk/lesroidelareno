<?php
declare(strict_types = 1);

namespace Drupal\lesroidelareno\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\lesroidelareno\lesroidelareno;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for lesroidelareno routes.
 */
final class ManageParagraphs extends ControllerBase {
  
  public function PreParagraph(Request $request, $paragraph) {
    if (lesroidelareno::isAdministrator()) {
      $entity = $this->entityTypeManager()->getStorage('paragraph')->load($paragraph);
      if ($entity) {
        $this->messenger()->addStatus("validé La supression du paragraph " . $entity->id() . " ? ");
        $query = [
          'query' => [
            'destination' => $request->get('destination')
          ]
        ];
        $delete_url = \Drupal\Core\Url::fromRoute('lesroidelareno.manage_paragraphs.confirmdelete', [
          'paragraph' => $entity->id()
        ], $query);
        $build['my_custom_link'] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'h3',
            '#value' => $entity->label()
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'type : ' . $entity->bundle()
          ],
          [
            '#type' => 'link',
            '#title' => 'Supprimer, cette action est irreversible',
            '#url' => $delete_url,
            '#attributes' => [
              'class' => [
                'button',
                'button--danger'
              ]
            ]
          ]
        ];
        return $build;
      }
      else
        $this->messenger()->addWarning("La paragraph " . $entity->id() . " n'existe plus ");
    }
    return [];
  }
  
  public function DeleteParagraph(Request $request, $paragraph) {
    if (lesroidelareno::isAdministrator()) {
      $entity = $this->entityTypeManager()->getStorage('paragraph')->load($paragraph);
      if ($entity) {
        $this->messenger()->addStatus("Le paragraph " . $entity->id() . " a été supprimer ");
        $entity->delete();
        $status = 302;
        return new RedirectResponse($request->get('destination'), $status);
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
    if ($limit) {
      $limit = (int) $limit;
      if ($limit > 0)
        $ListBuilderParagraph->setLimit($limit);
    }
    /**
     *
     * @var \Drupal\Core\Entity\Query\QueryInterface $customQuery
     */
    $customQuery = $ListBuilderParagraph->getQuery();
    if ($contain_domaine)
      $customQuery->condition('field_domain_access', trim($contain_domaine));
    
    if ($type_paragraph)
      $customQuery->condition('type', trim($type_paragraph));
    
    return [
      "filter" => $this->formBuilder()->getForm("Drupal\lesroidelareno\Form\FilterForm"),
      "collection" => $ListBuilderParagraph->render()
    ];
  }
}
