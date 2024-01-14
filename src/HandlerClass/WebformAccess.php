<?php

namespace Drupal\lesroidelareno\HandlerClass;

use Drupal\webform\WebformEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\lesroidelareno\lesroidelareno;

/**
 * Le webform est à revoir par example les conditions de delete et aussi les
 * autres $operation.
 *
 * @author stephane
 *        
 */
class WebformAccess extends WebformEntityAccessControlHandler {
  
  /**
   * on herite pas accessDefault car c'est publick pour
   * WebformEntityAccessControlHandler;
   *
   * {@inheritdoc}
   * @see \Drupal\blockscontent\BlocksContentsAccessControlHandler::checkAccess()
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $isOwnerSite = lesroidelareno::isOwnerSite();
    $isAdministrator = lesroidelareno::isAdministrator();
    $field_domain_access = \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD;
    $target_id = $entity->getThirdPartySetting('webform_domain_access', $field_domain_access);
    $cache_contexts = [
      'user',
      'url.site'
    ];
    switch ($operation) {
      // Tout le monde peut voir les contenus publiées.
      case 'view':
        if ($isAdministrator)
          return AccessResult::allowed()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
        // On empeche l'acces au données appartenant à un autre domaine.
        elseif (!$entity->isNew() && $target_id !== lesroidelareno::getCurrentDomainId()) {
          return AccessResult::forbidden("Wb-Horizon, Vous n'avez pas les droits pour effectuer cette action")->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
        }
        else
          return AccessResult::allowed()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
        break;
      // On met à jour si l'utilisateur est autheur ou s'il est administrateur.
      case 'update':
      case 'delete':
        if ($isAdministrator)
          return AccessResult::allowed()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
        // On empeche l'acces au données appartenant à un autre domaine.
        elseif (!$entity->isNew() && $target_id !== lesroidelareno::getCurrentDomainId()) {
          return AccessResult::forbidden("Wb-Horizon, Vous n'avez pas les droits pour effectuer cette action")->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
        }
        elseif ($isOwnerSite && $entity->getOwnerId() == lesroidelareno::getCurrentUserId()) {
          return AccessResult::allowed()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
        }
        break;
    }
    // on bloque au cas contraire.
    return AccessResult::forbidden("Wb-Horizon, Vous n'avez pas les droits pour effectuer cette action")->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
  }
  
}