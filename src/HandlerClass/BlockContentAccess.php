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
    $field_domain_access = \Drupal\domain_access\DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD;
    switch ($operation) {
      case 'view':
        if ($isAdministrator)
          return AccessResult::allowed();
        // On empeche l'acces au données appartenant à un autre domaine.
        elseif (!$isAdministrator && !$entity->isNew() && $entity->hasField($field_domain_access) && $entity->{$field_domain_access}->target_id !== lesroidelareno::getCurrentDomainId()) {
          $db = [
            "Entity domaine" => $entity->{$field_domain_access}->target_id,
            'Current domaine' => lesroidelareno::getCurrentDomainId(),
            'uid' => lesroidelareno::getCurrentUserId(),
            'bundle' => $entity->bundle()
          ];
          $message = "Entity " . $entity->id() . " : " . $entity->getEntityTypeId() . " : " . $entity->label() . ", non accessible sur le domaine : " . lesroidelareno::getCurrentDomainId();
          \Drupal::logger('lesroidelareno')->info($message, $db);
          return AccessResult::forbidden("Wb-Horizon, Vous n'avez pas les droits pour effectuer cette action");
        }
        elseif ($entity->isPublished()) {
          return AccessResult::allowed();
        }
        break;
      case 'update':
      case 'delete':
        if ($isAdministrator)
          return AccessResult::allowed();
        elseif (!$entity->isNew() && $entity->hasField($field_domain_access) && $entity->{$field_domain_access}->target_id !== lesroidelareno::getCurrentDomainId()) {
          $db = [
            "Entity domaine" => $entity->{$field_domain_access}->target_id,
            'Current domaine' => lesroidelareno::getCurrentDomainId(),
            'uid' => lesroidelareno::getCurrentUserId(),
            'bundle' => $entity->bundle()
          ];
          $message = "Entity " . $entity->id() . " : " . $entity->getEntityTypeId() . " : " . $entity->label() . ", non accessible sur le domaine : " . lesroidelareno::getCurrentDomainId();
          \Drupal::logger('lesroidelareno')->info($message, $db);
          return AccessResult::forbidden("Wb-Horizon, Vous n'avez pas les droits pour effectuer cette action");
        }
        elseif ($isOwnerSite) {
          return AccessResult::allowed();
        }
        elseif ($IsAdministratorSite) {
          return AccessResult::allowed();
        }
        break;
    }
    return AccessResult::forbidden("Wb-Horizon, Vous n'avez pas les droits pour effectuer cette action");
    // return parent::checkAccess($entity, $operation, $account);
  }
  
}