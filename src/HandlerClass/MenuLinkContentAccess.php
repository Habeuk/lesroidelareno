<?php

namespace Drupal\lesroidelareno\HandlerClass;

use Drupal\menu_link_content\MenuLinkContentAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\lesroidelareno\lesroidelareno;

class MenuLinkContentAccess extends MenuLinkContentAccessControlHandler {
  use AccessDefault;
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\blockscontent\BlocksContentsAccessControlHandler::checkAccess()
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    parent::checkAccess($entity, $operation, $account);
    $cache_contexts = [
      'url.site'
    ];
    $isOwnerSite = lesroidelareno::isOwnerSite();
    $isAdministrator = lesroidelareno::isAdministrator();
    switch ($operation) {
      // Tout le monde peut voir les contenus publiées.
      case 'view':
        if ($entity->isPublished()) {
          return AccessResult::allowed();
        }
        elseif ($isAdministrator)
          return AccessResult::allowed();
      // On met à jour si l'utilisateur est autheur ou s'il est administrateur.
      /**
       * on verifie si le champs wbh_user_id n'est pas null, si ces le cas la
       * verification est rapide.
       * Si le champs n'est pas disponible, on fait la verification à partir à
       * partir du menu.thirparty...domain_id. i.e, on verifie que le menu
       * appartient à l'auteur.
       */
      case 'update':
      case 'delete':
        if ($isAdministrator)
          return AccessResult::allowed();
        elseif ($isOwnerSite) {
          if ($entity->get('wbh_user_id')->target_id) {
            if ($entity->get('wbh_user_id')->target_id == lesroidelareno::getCurrentUserId())
              return AccessResult::allowed();
          }
          // verification à partir du menu
          elseif ($domain_id = lesroidelareno::FindUserAuthorDomain()) {
            /**
             * *
             *
             * @var \Drupal\menu_link_content\Entity\MenuLinkContent $entity
             */
            $menu = \Drupal\system\Entity\Menu::load($entity->bundle());
            if ($menu->getThirdPartySetting('lesroidelareno', 'domain_id') === $domain_id) {
              return AccessResult::allowed();
            }
            // verification à partir du label.
            else {
              $query = \Drupal::entityTypeManager()->getStorage('menu')->getQuery();
              $query->condition('label', $domain_id, 'CONTAINS');
              $ids = $query->execute();
              if ($ids)
                return AccessResult::allowed();
            }
          }
        }
    }
    // on bloque au cas contraire.
    return AccessResult::forbidden("Wb-Horizon, Vous n'avez pas les droits pour effectuer cette action")->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
  }
  
}