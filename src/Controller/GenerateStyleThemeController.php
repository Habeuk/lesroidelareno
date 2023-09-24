<?php

namespace Drupal\lesroidelareno\Controller;

use Drupal\Core\Controller\ControllerBase;
use Stephane888\Debug\ExceptionExtractMessage;
use Drupal\generate_style_theme\Services\ManageFileCustomStyle;

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
   * Permet de recuperer les styles definit dans une entity et de les renvoyés
   * vers le themes.
   * Cette fonctionnalitées est limités pour le moment à wb-horizon.
   *
   * @param integer $id
   * @param string $theme_name
   * @param string $entity_type_id
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function setDefaultStyle($id, $theme_name, $domaine_id, $entity_type_id = 'site_type_datas') {
    /**
     * C'est le contenu model.
     * Dans ce contenu model, seul quelques sont necessaire.
     * [ layout_paragraphs ]
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
          \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD => $domaine_id
        ];
        $this->ManageFileCustomStyle->saveStyle('entity.site_type_datas', 'lesroidelareno', $style_scss, $style_js, $customValue);
        // \Stephane888\Debug\debugLog::kintDebugDrupal($entityModel->get('style_scss')->value,
        // 'setDefaultStyle', true);
        return $this->reponse('Add custom style from model to site model : OK.');
      }
      catch (\Exception $e) {
        $errors = ExceptionExtractMessage::errorAll($e);
        $this->getLogger('lesroidelareno')->critical($e->getMessage() . '<br>' . implode("<br>", $errors));
        return $this->reponse($errors, 400, $e->getMessage());
      }
    }
    else {
      $this->getLogger('lesroidelareno')->critical(" Le contenu model n'existe plus : " . $id);
      return $this->reponse([], 400, "Le contenu model n'existe plus : " . $id);
    }
  }
  
}