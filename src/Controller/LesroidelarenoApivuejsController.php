<?php
declare(strict_types = 1);

namespace Drupal\lesroidelareno\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\apivuejs\Services\DuplicateEntityReference;
use Drupal\apivuejs\Services\GenerateForm;
use Drupal\apivuejs\Controller\ApivuejsController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\lesroidelareno\Services\APCuLockBackend;
use Stephane888\DrupalUtility\HttpResponse;

/**
 * Returns responses for lesroidelareno routes.
 */
final class LesroidelarenoApivuejsController extends ApivuejsController {
  /**
   *
   * @var APCuLockBackend
   */
  private $apculock;
  
  public function __construct(DuplicateEntityReference $DuplicateEntityReference, GenerateForm $GenerateForm, APCuLockBackend $apculock) {
    parent::__construct($DuplicateEntityReference, $GenerateForm);
    $this->apculock = $apculock;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('apivuejs.duplicate_reference'), $container->get('apivuejs.getform'), $container->get('lesroisdelareno.apculock'));
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\apivuejs\Controller\ApivuejsController::saveEntity()
   */
  public function saveEntity(Request $Request, $entity_type_id): \Symfony\Component\HttpFoundation\JsonResponse {
    $key = 'apivuejs_save_entity';
    if (!$this->apculock->waitBeforeStart($key)) {
      return HttpResponse::response([
        'error' => 'Server busy. Try later.'
      ], 423);
    }
    $reponses = parent::saveEntity($Request, $entity_type_id);
    $this->apculock->release($key);
    return $reponses;
  }
}