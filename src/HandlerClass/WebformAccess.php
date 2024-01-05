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
    
    switch ($operation) {
      // Tout le monde peut voir les contenus publiées.
      case 'view':
        if ($isAdministrator)
          return AccessResult::allowed();
        // On empeche l'acces au données appartenant à un autre domaine.
        elseif (!$isAdministrator && $entity->hasField($field_domain_access) && $entity->{$field_domain_access}->target_id !== lesroidelareno::getCurrentDomainId()) {
          throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
        else
          return AccessResult::allowed();
        break;
      // On met à jour si l'utilisateur est autheur ou s'il est administrateur.
      case 'update':
      case 'delete':
        if ($isAdministrator)
          return AccessResult::allowed();
        // On empeche l'acces au données appartenant à un autre domaine.
        elseif (!$isAdministrator && $entity->hasField($field_domain_access) && $entity->{$field_domain_access}->target_id !== lesroidelareno::getCurrentDomainId()) {
          throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
        elseif ($isOwnerSite && $entity->getOwnerId() == lesroidelareno::getCurrentUserId()) {
          return AccessResult::allowed();
        }
        break;
    }
    // on bloque au cas contraire.
    return AccessResult::forbidden("Wb-Horizon, Vous n'avez pas les droits pour effectuer cette action");
  }
  
}