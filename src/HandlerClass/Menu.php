<?php

namespace Drupal\lesroidelareno\HandlerClass;

use Drupal\system\MenuAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\lesroidelareno\lesroidelareno;

class Menu extends MenuAccessControlHandler {
  
  /**
   *
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $isOwnerSite = lesroidelareno::isOwnerSite();
    $isAdministrator = lesroidelareno::isAdministrator();
    $target_id = $entity->getThirdPartySetting('wb_horizon_public', 'domain_id');
    $cache_contexts = [
      'url.site'
    ];
    $access = parent::checkAccess($entity, $operation, $account);
    if ($operation === 'view label') {
      if ($isAdministrator)
        return $access;
      // On empeche l'acces au données appartenant à un autre domaine.
      elseif (!$entity->isNew() && $target_id !== lesroidelareno::getCurrentDomainId()) {
        return AccessResult::forbidden()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
      }
      else
        return $access;
    }
    elseif ($operation === 'view') {
      if ($isAdministrator)
        return $access;
      // On empeche l'acces au données appartenant à un autre domaine.
      elseif (!$entity->isNew() && $target_id !== lesroidelareno::getCurrentDomainId()) {
        return AccessResult::forbidden()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
      }
      elseif ($isOwnerSite)
        return AccessResult::allowed()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
      else
        $access;
    }
    // Locked menus could not be deleted.
    elseif ($operation === 'delete') {
      if ($entity->isLocked()) {
        return AccessResult::forbidden('The Menu config entity is locked.')->addCacheableDependency($entity);
      }
      else {
        if ($isAdministrator)
          return AccessResult::allowed()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
        // On empeche l'acces au données appartenant à un autre domaine.
        elseif (!$entity->isNew() && $target_id !== lesroidelareno::getCurrentDomainId()) {
          return AccessResult::forbidden()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
        }
        elseif ($isOwnerSite) {
          return AccessResult::allowed()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
        }
      }
    }
    elseif ($operation === 'update') {
      if ($isAdministrator)
        return AccessResult::allowed()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
      // On empeche l'acces aux données appartenant à un autre domaine.
      elseif (!$entity->isNew() && $target_id !== lesroidelareno::getCurrentDomainId()) {
        return AccessResult::forbidden()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
      }
      elseif ($isOwnerSite) {
        return AccessResult::allowed()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
      }
    }
    // on bloque au cas contraire.
    return AccessResult::forbidden("Wb-Horizon, Vous n'avez pas les droits pour effectuer cette action");
  }
  
}