<?php

namespace Drupal\lesroidelareno\HandlerClass;

use Drupal\block\BlockAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\lesroidelareno\lesroidelareno;

class Block extends BlockAccessControlHandler {
  
  /**
   *
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $cache_contexts = [
      'user',
      'url.site'
    ];
    $isAdministrator = lesroidelareno::isAdministrator();
    $target_id = $entity->get('theme');
    $access = parent::checkAccess($entity, $operation, $account);
    if ($isAdministrator) {
      return $access;
    }
    if ($access->isForbidden())
      return $access;
    
    // Pour le filtre, on le simplifie au ninimun
    if ($operation == 'view') {
      if (lesroidelareno::getCurrentDomainId() != $target_id) {
        return AccessResult::forbidden()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
      }
      return AccessResult::allowed()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
    }
    
    // quel consequence d'utiliser le cache ?
    // return AccessResult::forbidden()->addCacheableDependency($entity);
    return AccessResult::forbidden("Wb-Horizon, Vous n'avez pas les droits pour effectuer cette action")->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
  }
  
}