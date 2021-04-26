<?php

namespace Drupal\ewp_institutions;

/**
 * Provides lists of Other ID types.
 */
class OtherIdTypeManager {

  /**
   * An array of type key => type name pairs where type must be unique.
   */
  protected $otherIdUniqueTypes;

  /**
   * An array of type key => type name pairs where type can be not unique.
   */
  protected $otherIdNonUniqueTypes;

  /**
   * An array of type key => type name pairs.
   */
  protected $otherIdTypes;

  /**
   * Curated list of unique EWP Other ID types.
   *
   * @return array
   *   An array of type key => type name pairs where type must be unique.
   */
  public static function getUniqueTypes() {
    $unique_types = [
      'erasmus' => t('Erasmus institutional code'),
      'erasmus-charter' => t('Erasmus Charter number'),
      'pic' => t('PIC identifier'),
    ];

    return $unique_types;
  }

  /**
   * Get an array of type key => type name pairs, as options.
   *
   * @return array
   *   An array of type key => type name pairs.
   *
   * @see \Drupal\ewp_institutions\OtherIdTypeManager::getUniqueTypes()
   */
  public function getUniqueTypeList() {
    // Populate the type list if it is not already populated.
    if (!isset($this->otherIdUniqueTypes)) {
      $this->otherIdUniqueTypes = static::getUniqueTypes();
    }

    $unique = $this->otherIdUniqueTypes;

    return $unique;
  }

  /**
   * Curated list of non unique EWP Other ID types.
   *
   * @return array
   *   An array of type key => type name pairs where type can be not unique.
   */
  public static function getNonUniqueTypes() {
    $non_unique_types = [
      'previous-schac' => t('Previous SCHAC'),
    ];

    return $non_unique_types;
  }

  /**
   * Get an array of type key => type name pairs, as options.
   *
   * @return array
   *   An array of type key => type name pairs.
   *
   * @see \Drupal\ewp_institutions\OtherIdTypeManager::getNonUniqueTypes()
   */
  public function getNonUniqueTypeList() {
    // Populate the type list if it is not already populated.
    if (!isset($this->otherIdNonUniqueTypes)) {
      $this->otherIdNonUniqueTypes = static::getNonUniqueTypes();
    }

    $non_unique = $this->otherIdNonUniqueTypes;

    return $non_unique;
  }

  /**
   * Get an array of type key => type name pairs, as options.
   *
   * @return array
   *   An array of type key => type name pairs.
   *
   * @see \Drupal\ewp_institutions\OtherIdTypeManager::getUniqueTypes()
   * @see \Drupal\ewp_institutions\OtherIdTypeManager::getNonUniqueTypes()
   */
  public function getDefinedTypes() {
    // Populate the defined type list if it is not already populated.
    if (!isset($this->otherIdTypes)) {
      // Populate the unique type list if it is not already populated.
      if (!isset($this->otherIdUniqueTypes)) {
        $this->otherIdUniqueTypes = static::getUniqueTypes();
      }

      // Populate the non unique type list if it is not already populated.
      if (!isset($this->otherIdNonUniqueTypes)) {
        $this->otherIdNonUniqueTypes = static::getNonUniqueTypes();
      }

      $this->otherIdTypes = array_merge(
        $this->otherIdUniqueTypes,
        $this->otherIdNonUniqueTypes
      );
    }

    $defined_types = $this->otherIdTypes;

    return $defined_types;
  }

  /**
   * Get an array of type key => type name pairs, as options.
   *
   * @return array
   *   An array of type key => type name pairs.
   */
  public function getOptions() {
    // Build a list from the defined types
    $options = $this->getDefinedTypes();

    return $options;
  }

}
