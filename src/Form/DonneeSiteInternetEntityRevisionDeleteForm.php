<?php

namespace Drupal\lesroidelareno\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Donnee site internet des utilisateurs revision.
 *
 * @ingroup lesroidelareno
 */
class DonneeSiteInternetEntityRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The Donnee site internet des utilisateurs revision.
   *
   * @var \Drupal\lesroidelareno\Entity\DonneeSiteInternetEntityInterface
   */
  protected $revision;

  /**
   * The Donnee site internet des utilisateurs storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $donneeSiteInternetEntityStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->donneeSiteInternetEntityStorage = $container->get('entity_type.manager')->getStorage('donnee_internet_entity');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'donnee_internet_entity_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.donnee_internet_entity.version_history', ['donnee_internet_entity' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $donnee_internet_entity_revision = NULL) {
    $this->revision = $this->DonneeSiteInternetEntityStorage->loadRevision($donnee_internet_entity_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->DonneeSiteInternetEntityStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Donnee site internet des utilisateurs: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Revision from %revision-date of Donnee site internet des utilisateurs %title has been deleted.', ['%revision-date' => \Drupal::service('date.formatter')->format($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.donnee_internet_entity.canonical',
       ['donnee_internet_entity' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {donnee_internet_entity_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.donnee_internet_entity.version_history',
         ['donnee_internet_entity' => $this->revision->id()]
      );
    }
  }

}
