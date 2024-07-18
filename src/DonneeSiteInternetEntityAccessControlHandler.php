<?php

namespace Drupal\lesroidelareno;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Donnee site internet des utilisateurs entity.
 *
 * @see \Drupal\lesroidelareno\Entity\DonneeSiteInternetEntity.
 */
class DonneeSiteInternetEntityAccessControlHandler extends EntityAccessControlHandler {
  
  /**
   *
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\lesroidelareno\Entity\DonneeSiteInternetEntityInterface $entity */
    switch ($operation) {
      
      case 'view':
        
        if (!$entity->isPublished()) {
          if ($account->id() == $entity->getOwnerId()) {
            return AccessResult::allowed();
          }
          return AccessResult::allowedIfHasPermission($account, 'view unpublished donnee site internet des utilisateurs entities');
        }
        else {
          // if entity is published, show
          return AccessResult::allowed();
        }
        
        return AccessResult::allowedIfHasPermission($account, 'view published donnee site internet des utilisateurs entities');
      
      case 'update':
        
        return AccessResult::allowedIfHasPermission($account, 'edit donnee site internet des utilisateurs entities');
      
      case 'delete':
        
        return AccessResult::allowedIfHasPermission($account, 'delete donnee site internet des utilisateurs entities');
    }
    
    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }
  
  /**
   *
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add donnee site internet des utilisateurs entities');
  }
  
}
