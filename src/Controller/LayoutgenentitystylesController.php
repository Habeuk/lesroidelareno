<?php
declare(strict_types = 1);

namespace Drupal\lesroidelareno\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\layout_builder\SectionStorage\SectionStorageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\layout_builder\Plugin\SectionStorage\DefaultsSectionStorage;
use Drupal\lesroidelareno\Services\layoutgenentitystyles\OverrideLayoutgenentitystylesServices;
use Stephane888\DrupalUtility\HttpResponse;

/**
 * Returns responses for lesroidelareno routes.
 */
class LayoutgenentitystylesController extends ControllerBase {
  
  /**
   * The section storage manager.
   *
   * @var SectionStorageManager
   */
  protected $sectionStorageManager;
  
  /**
   * The section storage.
   *
   * @var DefaultsSectionStorage
   */
  protected $sectionStorage;
  
  /**
   *
   * @var OverrideLayoutgenentitystylesServices
   */
  protected $LayoutgenentitystylesServices;
  
  function __construct(OverrideLayoutgenentitystylesServices $LayoutgenentitystylesServices) {
    $this->LayoutgenentitystylesServices = $LayoutgenentitystylesServices;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('layoutgenentitystyles.add.style.theme'));
  }
  
  /**
   *
   * @return string[]
   */
  public function apiManuelGenerate($hostname) {
    $this->LayoutgenentitystylesServices->setDomaineId($hostname);
    $this->LayoutgenentitystylesServices->setShowMessage(false);
    $this->LayoutgenentitystylesServices->generateAllFilesStyles();
    // dd($this->LayoutgenentitystylesServices->getLibraries());
    return HttpResponse::response($this->LayoutgenentitystylesServices->getLibraries());
  }
}