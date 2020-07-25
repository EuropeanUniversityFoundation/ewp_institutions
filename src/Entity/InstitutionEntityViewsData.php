<?php

namespace Drupal\ewp_institutions\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Institution entities.
 */
class InstitutionEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
