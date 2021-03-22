<?php

namespace Drupal\ewp_institutions_get\Controller;

use Drupal\Core\Entity\Controller\EntityController;

/**
 * Provides additional title callbacks for Institution entities.
 *
 * It provides:
 * - An add from external source title callback.
 * - An import title callback.
 * - An update title callback.
 */
class InstitutionEntityController extends EntityController {

  /**
   * Provides a generic add from external source title callback.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The title for the entity import page.
   */
  public function addExternalTitle($entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    return $this->t('Add @entity-type from external source', ['@entity-type' => $entity_type->getSingularLabel()]);
  }

  /**
   * Provides a generic import title callback.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The title for the entity import page.
   */
  public function importTitle($entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    return $this->t('Import @entity-type', ['@entity-type' => $entity_type->getSingularLabel()]);
  }

}
