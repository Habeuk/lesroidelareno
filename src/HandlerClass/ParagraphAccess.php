<?php

namespace Drupal\lesroidelareno\HandlerClass;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\lesroidelareno\lesroidelareno;
use Drupal\paragraphs\ParagraphAccessControlHandler;

class ParagraphAccess extends ParagraphAccessControlHandler {
  use AccessDefault;
  
  /**
   * Les paragraphes suivent une autre logique.
   * Ici, on verifie si l'utilisateur est proprietaire de site, ensuite, on
   * verifie s'il a les droits d'editer le parent.( car la logique getOwner...
   * est deprecie dans la
   * version paragraphs:1.15.0
   * ).
   *
   * Au final, on a choisit d'ajouter un champs specifique au paragraphe afin de
   * determiner qui est le proproietaire lors de la creation. ( cette une
   * approche forcer en attendant de ressoudre le probleme de logique ).
   *
   *
   * {@inheritdoc}
   * @see \Drupal\blockscontent\BlocksContentsAccessControlHandler::checkAccess()
   */
  protected function checkAccess(EntityInterface $paragraph, $operation, AccountInterface $account) {
    $isOwnerSite = lesroidelareno::isOwnerSite();
    $isAdministrator = lesroidelareno::isAdministrator();
    
    switch ($operation) {
      // Tout le monde peut voir les contenus publiées.
      case 'view':
        if ($paragraph->isPublished()) {
          return AccessResult::allowed();
        }
        elseif ($isAdministrator)
          return AccessResult::allowed();
      // On met à jour si l'utilisateur est autheur ou s'il est administrateur.
      case 'update':
      case 'delete':
        if ($isAdministrator)
          return AccessResult::allowed();
        elseif ($isOwnerSite) {
          
          // si on parvient à identifier le parent.
          if ($paragraph->getParentEntity() != NULL) {
            return parent::checkAccess($paragraph, $operation, $account);
          }
          elseif ($paragraph->get('wbh_user_id')->target_id == lesroidelareno::getCurrentUserId()) {
            // dump(\Drupal::routeMatch()->getRouteObject());
            // dump(\Drupal::routeMatch()->getRouteName());
            // /**
            // *
            // * @var \Drupal\Core\Routing\AccessAwareRouter $router
            // */
            // $router = \Drupal::service('router');
            // dump($router->getRouteCollection()->get('layout_builder.overrides.paragraph.view'));
            
            // $role = \Drupal\user\Entity\Role::load('gerant_de_site_web');
            // dump($role->getPermissions());
            return AccessResult::allowed();
          }
        }
    }
    // on bloque au cas contraire.
    return AccessResult::forbidden("Wb-Horizon, Vous n'avez pas les droits pour effectuer cette action");
  }
  
}