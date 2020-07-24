<?php

namespace Drupal\ewp_institutions;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Organization entity.
 *
 * @see \Drupal\ewp_institutions\Entity\OrgEntity.
 */
class OrgEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ewp_institutions\Entity\OrgEntityInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished organization entities');
        }

        return AccessResult::allowedIfHasPermission($account, 'view published organization entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit organization entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete organization entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add organization entities');
  }

}
