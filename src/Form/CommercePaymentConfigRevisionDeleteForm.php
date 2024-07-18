<?php

namespace Drupal\lesroidelareno\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Commerce payment config revision.
 *
 * @ingroup lesroidelareno
 */
class CommercePaymentConfigRevisionDeleteForm extends ConfirmFormBase {


  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The Commerce payment config revision.
   *
   * @var \Drupal\lesroidelareno\Entity\CommercePaymentConfigInterface
   */
  protected $revision;

  /**
   * The Commerce payment config storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $commercePaymentConfigStorage;

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
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->commercePaymentConfigStorage = $container->get('entity_type.manager')->getStorage('commerce_payment_config');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_payment_config_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.commerce_payment_config.version_history', ['commerce_payment_config' => $this->revision->id()]);
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
  public function buildForm(array $form, FormStateInterface $form_state, $commerce_payment_config_revision = NULL) {
    $this->revision = $this->CommercePaymentConfigStorage->loadRevision($commerce_payment_config_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->CommercePaymentConfigStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Commerce payment config: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Revision from %revision-date of Commerce payment config %title has been deleted.', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.commerce_payment_config.canonical',
       ['commerce_payment_config' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {commerce_payment_config_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.commerce_payment_config.version_history',
         ['commerce_payment_config' => $this->revision->id()]
      );
    }
  }

}
