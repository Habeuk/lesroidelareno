<?php

namespace Drupal\lesroidelareno\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\lesroidelareno\Entity\DonneeSiteInternetEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DonneeSiteInternetEntityController.
 *
 *  Returns responses for Donnee site internet des utilisateurs routes.
 */
class DonneeSiteInternetEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Donnee site internet des utilisateurs revision.
   *
   * @param int $donnee_internet_entity_revision
   *   The Donnee site internet des utilisateurs revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($donnee_internet_entity_revision) {
    $donnee_internet_entity = $this->entityTypeManager()->getStorage('donnee_internet_entity')
      ->loadRevision($donnee_internet_entity_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('donnee_internet_entity');

    return $view_builder->view($donnee_internet_entity);
  }

  /**
   * Page title callback for a Donnee site internet des utilisateurs revision.
   *
   * @param int $donnee_internet_entity_revision
   *   The Donnee site internet des utilisateurs revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($donnee_internet_entity_revision) {
    $donnee_internet_entity = $this->entityTypeManager()->getStorage('donnee_internet_entity')
      ->loadRevision($donnee_internet_entity_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $donnee_internet_entity->label(),
      '%date' => $this->dateFormatter->format($donnee_internet_entity->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Donnee site internet des utilisateurs.
   *
   * @param \Drupal\lesroidelareno\Entity\DonneeSiteInternetEntityInterface $donnee_internet_entity
   *   A Donnee site internet des utilisateurs object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(DonneeSiteInternetEntityInterface $donnee_internet_entity) {
    $account = $this->currentUser();
    $donnee_internet_entity_storage = $this->entityTypeManager()->getStorage('donnee_internet_entity');

    $build['#title'] = $this->t('Revisions for %title', ['%title' => $donnee_internet_entity->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all donnee site internet des utilisateurs revisions") || $account->hasPermission('administer donnee site internet des utilisateurs entities')));
    $delete_permission = (($account->hasPermission("delete all donnee site internet des utilisateurs revisions") || $account->hasPermission('administer donnee site internet des utilisateurs entities')));

    $rows = [];

    $vids = $donnee_internet_entity_storage->revisionIds($donnee_internet_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\lesroidelareno\DonneeSiteInternetEntityInterface $revision */
      $revision = $donnee_internet_entity_storage->loadRevision($vid);
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $donnee_internet_entity->getRevisionId()) {
          $link = $this->l($date, new Url('entity.donnee_internet_entity.revision', [
            'donnee_internet_entity' => $donnee_internet_entity->id(),
            'donnee_internet_entity_revision' => $vid,
          ]));
        }
        else {
          $link = $donnee_internet_entity->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.donnee_internet_entity.revision_revert', [
                'donnee_internet_entity' => $donnee_internet_entity->id(),
                'donnee_internet_entity_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.donnee_internet_entity.revision_delete', [
                'donnee_internet_entity' => $donnee_internet_entity->id(),
                'donnee_internet_entity_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
    }

    $build['donnee_internet_entity_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
