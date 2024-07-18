<?php

namespace Drupal\lesroidelareno\Controller;

use Drupal\Core\Controller\ControllerBase;
use Stephane888\Debug\ExceptionExtractMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\generate_style_theme\Services\ManageFileCustomStyle;
use Stephane888\DrupalUtility\HttpResponse;

/**
 * Class DonneeSiteInternetEntityController.
 *
 * Returns responses for Donnee site internet des utilisateurs routes.
 */
class GenerateStyleThemeController extends ControllerBase {
  
  /**
   *
   * @var ManageFileCustomStyle
   */
  protected $ManageFileCustomStyle;
  
  public function __construct(ManageFileCustomStyle $ManageFileCustomStyle) {
    $this->ManageFileCustomStyle = $ManageFileCustomStyle;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('generate_style_theme.manage_file_custom_style'));
  }
  
  /**
   * Permet de recuperer les styles definit dans une entity et de les renvoyés
   * vers le themes.
   * Cette fonctionnalitées est limités pour le moment à wb-horizon.
   *
   * @param integer $id
   * @param string $theme_name
   * @param string $entity_type_id
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function setDefaultStyle($id, $theme_name, $entity_type_id = 'site_type_datas') {
    /**
     *
     * @var \Drupal\creation_site_virtuel\Entity\SiteTypeDatas $entityModel
     */
    $entityModel = $this->entityTypeManager()->getStorage($entity_type_id)->load($id);
    if ($entityModel) {
      try {
        $this->ManageFileCustomStyle->theme_name = $theme_name;
        $style_scss = $entityModel->get('style_scss')->value;
        $style_js = $entityModel->get('style_js')->value;
        $customValue = [
          \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD => $theme_name,
          \Drupal\domain_source\DomainSourceElementManagerInterface::DOMAIN_SOURCE_FIELD => $theme_name
        ];
        if (!empty($style_scss) || !empty($style_js)) {
          /**
           * On utilise les clées de "Form::GenerateStyleThemeStyles" afin de
           * permettre l'edition du styles au niveau du sous domaine.
           *
           * @var string $key
           */
          $key = 'generate_style_theme.styles';
          $this->ManageFileCustomStyle->saveStyle($key, 'generate_style_theme', $style_scss, $style_js, $customValue);
        }
        
        return HttpResponse::response('Add custom style from model to site model : OK.');
      }
      catch (\Exception $e) {
        $errors = ExceptionExtractMessage::errorAll($e);
        $this->getLogger('lesroidelareno')->critical($e->getMessage() . '<br>' . implode("<br>", $errors));
        return HttpResponse::response($errors, 400, $e->getMessage());
      }
    }
    else {
      $this->getLogger('lesroidelareno')->critical(" Le contenu model n'existe plus : " . $id);
      return HttpResponse::response([], 400, "Le contenu model n'existe plus : " . $id);
    }
  }
  
}