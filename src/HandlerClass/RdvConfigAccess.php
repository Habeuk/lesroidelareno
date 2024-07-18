<?php

namespace Drupal\lesroidelareno\HandlerClass;

use Drupal\webform\WebformEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\lesroidelareno\lesroidelareno;
use Drupal\Core\Entity\EntityAccessControlHandler;

/**
 * Le webform est à revoir par example les conditions de delete et aussi les
 * autres $operation.
 *
 * @author stephane
 *        
 */
class RdvConfigAccess extends EntityAccessControlHandler {
  
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
    $cache_contexts = [
      'user',
      'url.site'
    ];
    return AccessResult::allowed()->addCacheableDependency($entity)->addCacheContexts($cache_contexts);
  }
  
}