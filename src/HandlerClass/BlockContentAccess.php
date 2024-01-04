<?php

namespace Drupal\lesroidelareno\HandlerClass;

use Drupal\block_content\BlockContentAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\lesroidelareno\lesroidelareno;
use Drupal\Core\Access\AccessResult;

class BlockContentAccess extends BlockContentAccessControlHandler {
  
  // use AccessDefault;
  /**
   * Le but principale ici est d'eleminer toutes les entites de type
   * blockcontent.
   * On pourrait etre informer par message et avoir une liste.
   *
   * {@inheritdoc}
   * @see \Drupal\blockscontent\BlocksContentsAccessControlHandler::checkAccess()
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $isOwnerSite = lesroidelareno::isOwnerSite();
    $IsAdministratorSite = lesroidelareno::userIsAdministratorSite();
    $isAdministrator = lesroidelareno::isAdministrator();
    switch ($operation) {
      case 'view':
        if ($entity->isPublished()) {
          return AccessResult::allowed();
        }
        elseif ($isAdministrator)
          return AccessResult::allowed();
        break;
      case 'update':
      case 'delete':
        if ($isAdministrator)
          return AccessResult::allowed();
        elseif ($isOwnerSite) {
          return AccessResult::allowed();
        }
        elseif ($IsAdministratorSite) {
          return AccessResult::allowed();
        }
    }
    return parent::checkAccess($entity, $operation, $account);
  }
  
}