<?php

namespace Drupal\lesroidelareno\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\lesroidelareno\Entity\CommercePaymentConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CommercePaymentConfigController.
 *
 *  Returns responses for Commerce payment config routes.
 */
class CommercePaymentConfigController extends ControllerBase implements ContainerInjectionInterface {

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
   * Displays a Commerce payment config revision.
   *
   * @param int $commerce_payment_config_revision
   *   The Commerce payment config revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($commerce_payment_config_revision) {
    $commerce_payment_config = $this->entityTypeManager()->getStorage('commerce_payment_config')
      ->loadRevision($commerce_payment_config_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('commerce_payment_config');

    return $view_builder->view($commerce_payment_config);
  }

  /**
   * Page title callback for a Commerce payment config revision.
   *
   * @param int $commerce_payment_config_revision
   *   The Commerce payment config revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($commerce_payment_config_revision) {
    $commerce_payment_config = $this->entityTypeManager()->getStorage('commerce_payment_config')
      ->loadRevision($commerce_payment_config_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $commerce_payment_config->label(),
      '%date' => $this->dateFormatter->format($commerce_payment_config->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Commerce payment config.
   *
   * @param \Drupal\lesroidelareno\Entity\CommercePaymentConfigInterface $commerce_payment_config
   *   A Commerce payment config object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(CommercePaymentConfigInterface $commerce_payment_config) {
    $account = $this->currentUser();
    $commerce_payment_config_storage = $this->entityTypeManager()->getStorage('commerce_payment_config');

    $build['#title'] = $this->t('Revisions for %title', ['%title' => $commerce_payment_config->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all commerce payment config revisions") || $account->hasPermission('administer commerce payment config entities')));
    $delete_permission = (($account->hasPermission("delete all commerce payment config revisions") || $account->hasPermission('administer commerce payment config entities')));

    $rows = [];

    $vids = $commerce_payment_config_storage->revisionIds($commerce_payment_config);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\lesroidelareno\Entity\CommercePaymentConfigInterface $revision */
      $revision = $commerce_payment_config_storage->loadRevision($vid);
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $commerce_payment_config->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.commerce_payment_config.revision', [
            'commerce_payment_config' => $commerce_payment_config->id(),
            'commerce_payment_config_revision' => $vid,
          ]))->toString();
        }
        else {
          $link = $commerce_payment_config->toLink($date)->toString();
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
              'url' => Url::fromRoute('entity.commerce_payment_config.revision_revert', [
                'commerce_payment_config' => $commerce_payment_config->id(),
                'commerce_payment_config_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.commerce_payment_config.revision_delete', [
                'commerce_payment_config' => $commerce_payment_config->id(),
                'commerce_payment_config_revision' => $vid,
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

    $build['commerce_payment_config_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
