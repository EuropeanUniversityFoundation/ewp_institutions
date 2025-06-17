<?php

namespace Drupal\ewp_institutions;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ewp_core\SelectOptionsProviderInterface;

/**
 * Provides lists of Other ID types.
 */
class OtherIdTypeManager implements OtherIdTypeManagerInterface, SelectOptionsProviderInterface {

  /**
   * An array of type key => type name pairs where type must be unique.
   *
   * @var array|null
   */
  protected $otherIdUniqueTypes;

  /**
   * An array of type key => type name pairs where type can be not unique.
   *
   * @var array|null
   */
  protected $otherIdNonUniqueTypes;

  /**
   * An array of type key => type name pairs.
   *
   * @var array|null
   */
  protected $otherIdTypes;

  /**
   * Curated list of unique EWP Other ID types.
   *
   * @return array
   *   An array of type key => type name pairs where type must be unique.
   */
  public static function getUniqueTypes(): array {
    $unique_types = [
      'erasmus' => new TranslatableMarkup('Erasmus institutional code'),
      'erasmus-charter' => new TranslatableMarkup('Erasmus Charter number'),
      'pic' => new TranslatableMarkup('PIC identifier'),
    ];

    return $unique_types;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\ewp_institutions\OtherIdTypeManager::getUniqueTypes()
   */
  public function getUniqueTypeList(): array {
    // Populate the type list if it is not already populated.
    if (!isset($this->otherIdUniqueTypes)) {
      $this->otherIdUniqueTypes = static::getUniqueTypes();
    }

    return $this->otherIdUniqueTypes;
  }

  /**
   * Curated list of non unique EWP Other ID types.
   *
   * @return array
   *   An array of type key => type name pairs where type can be not unique.
   */
  public static function getNonUniqueTypes(): array {
    $non_unique_types = [
      'previous-schac' => new TranslatableMarkup('Previous SCHAC'),
    ];

    return $non_unique_types;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\ewp_institutions\OtherIdTypeManager::getNonUniqueTypes()
   */
  public function getNonUniqueTypeList(): array {
    // Populate the type list if it is not already populated.
    if (!isset($this->otherIdNonUniqueTypes)) {
      $this->otherIdNonUniqueTypes = static::getNonUniqueTypes();
    }

    return $this->otherIdNonUniqueTypes;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\ewp_institutions\OtherIdTypeManager::getUniqueTypes()
   * @see \Drupal\ewp_institutions\OtherIdTypeManager::getNonUniqueTypes()
   */
  public function getDefinedTypes(): array {
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

    return $this->otherIdTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectOptions(): array {
    // Build a list from the defined types.
    return $this->getDefinedTypes();
  }

}
