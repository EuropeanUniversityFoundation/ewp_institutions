<?php

namespace Drupal\ewp_institutions;

/**
 * Defines an interface for an Other ID types service.
 */
interface OtherIdTypeManagerInterface {

  /**
   * Get an array of type key => type name pairs, as options.
   *
   * @return array
   *   An array of type key => type name pairs.
   */
  public function getUniqueTypeList(): array;

  /**
   * Get an array of type key => type name pairs, as options.
   *
   * @return array
   *   An array of type key => type name pairs.
   */
  public function getNonUniqueTypeList(): array;

  /**
   * Get an array of type key => type name pairs, as options.
   *
   * @return array
   *   An array of type key => type name pairs.
   */
  public function getDefinedTypes(): array;

}
