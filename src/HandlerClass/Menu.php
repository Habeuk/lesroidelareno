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
    
    if ($operation === 'view label') {
      if ($isAdministrator)
        return AccessResult::allowed();
      // On empeche l'acces au données appartenant à un autre domaine.
      elseif (!$entity->isNew() && $target_id !== lesroidelareno::getCurrentDomainId()) {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
      }
      else
        return AccessResult::allowed();
    }
    elseif ($operation === 'view') {
      if ($isAdministrator)
        return AccessResult::allowed();
      // On empeche l'acces au données appartenant à un autre domaine.
      elseif (!$entity->isNew() && $target_id !== lesroidelareno::getCurrentDomainId()) {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
      }
      elseif ($isOwnerSite)
        return AccessResult::allowed();
    }
    // Locked menus could not be deleted.
    elseif ($operation === 'delete') {
      if ($entity->isLocked()) {
        return AccessResult::forbidden('The Menu config entity is locked.')->addCacheableDependency($entity);
      }
      else {
        if ($isAdministrator)
          return AccessResult::allowed();
        // On empeche l'acces au données appartenant à un autre domaine.
        elseif (!$entity->isNew() && $target_id !== lesroidelareno::getCurrentDomainId()) {
          throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
        elseif ($isOwnerSite) {
          return AccessResult::allowed();
        }
      }
    }
    elseif ($operation === 'update') {
      if ($isAdministrator)
        return AccessResult::allowed();
      // On empeche l'acces au données appartenant à un autre domaine.
      elseif (!$entity->isNew() && $target_id !== lesroidelareno::getCurrentDomainId()) {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
      }
      elseif ($isOwnerSite) {
        return AccessResult::allowed();
      }
    }
    // on bloque au cas contraire.
    return AccessResult::forbidden("Wb-Horizon, Vous n'avez pas les droits pour effectuer cette action");
  }
  
}