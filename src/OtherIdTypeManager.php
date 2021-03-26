<?php

namespace Drupal\ewp_institutions;

/**
 * Provides list of Other ID types.
 */
class OtherIdTypeManager {

  /**
   * An array of type key => type name pairs.
   */
  protected $otherIdTypes;

  /**
   * Curated list of EWP Other ID types.
   *
   * @return array
   *   An array of type key => type name pairs.
   */
  public static function getList() {
    $other_id_types = [
      'previous-schac' => t('Previous SCHAC'),
      'pic' => t('PIC identifier'),
      'erasmus' => t('Erasmus institutional code'),
      'erasmus-charter' => t('Erasmus Charter number'),
      'custom' => t('custom identifier'),
    ];

    return $other_id_types;
  }

  /**
   * Get an array of type key => type name pairs, as options.
   *
   * @return array
   *   An array of type key => type name pairs.
   *
   * @see \Drupal\ewp_institutions\OtherIdTypeManager::getList()
   */
  public function getOptions() {
    // Populate the type list if it is not already populated.
    if (!isset($this->otherIdTypes)) {
      $this->otherIdTypes = static::getList();
    }

    $options = $this->otherIdTypes;
    
    return $options;
  }

}
