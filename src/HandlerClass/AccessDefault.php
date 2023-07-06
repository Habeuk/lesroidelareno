<?php

namespace Drupal\lesroidelareno\HandlerClass;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\lesroidelareno\lesroidelareno;

/**
 * Ce filtre s'aplique uniquement aux proprietaires de site web.
 * Pour les utilisateurs qui possedent un sous compte, on doit se baser,
 * sur le compte du proprietaire.
 * NB : cela n'accorde pas un access à la creation, pour autoriser l'access à la
 * creation il faut passer par l'alteration de la route, i.e on pourra ajouter
 * les roles necessaire.
 * ( mais contraitemnt on est passé par les droits en administrations ).
 *
 * @see https://www.drupal.org/docs/drupal-apis/routing-system/altering-existing-routes-and-adding-new-routes-based-on-dynamic-ones
 * @author stephane
 *        
 */
trait AccessDefault {
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\blockscontent\BlocksContentsAccessControlHandler::checkAccess()
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $isOwnerSite = lesroidelareno::isOwnerSite();
    $isAdministrator = lesroidelareno::isAdministrator();
    // dump($entity->getEntityTypeId() . ' :: ' . $operation);
    // if ($operation == 'update' || $operation == 'delete') {
    // $debug = [
    // 'entity_uid' => $entity->getOwnerId(),
    // 'current_user_uid' => lesroidelareno::getCurrentUserId(),
    // 'entity' => $entity->toArray()
    // ];
    // \Stephane888\Debug\debugLog::$max_depth = 5;
    // \Stephane888\Debug\debugLog::kintDebugDrupal($debug,
    // $entity->getEntityTypeId() . '---checkAccess---' . $operation . '--',
    // true);
    // }
    switch ($operation) {
      // Tout le monde peut voir les contenus publiées.
      case 'view':
        if ($entity->isPublished()) {
          return AccessResult::allowed();
        }
        elseif ($isAdministrator)
          return AccessResult::allowed();
      // On met à jour si l'utilisateur est autheur ou s'il est administrateur.
      case 'update':
      case 'delete':
        if ($isAdministrator)
          return AccessResult::allowed();
        elseif ($isOwnerSite && $entity->getOwnerId() == lesroidelareno::getCurrentUserId()) {
          return AccessResult::allowed();
        }
    }
    // on bloque au cas contraire.
    return AccessResult::forbidden("Wb-Horizon, Vous n'avez pas les droits pour effectuer cette action");
  }
  
}