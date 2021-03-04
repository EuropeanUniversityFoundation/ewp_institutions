<?php

namespace Drupal\ewp_institutions_get;

/**
 * Class RemoteKeys
 *
 * Helper class to handle JSON:API object keys
 */
class RemoteKeys {

  /**
   * Returns the default keys expected in a full JSON:API response
   */
  public static function getDefaultKeys() {
    $default_keys = [
      'id',
      'uuid',
      'langcode',
      'status',
      'label',
      'created',
      'changed',
      'abbreviation',
      'contact',
      'hei_id',
      'logo_url',
      'mailing_address',
      'mobility_factsheet_url',
      'name',
      'other_id',
      'street_address',
      'website_url'
    ];

    return $default_keys;
  }

  /**
   * Returns an associative array of keys after excluding and including
   */
  public static function getAssocKeys(array $keys, array $excluded = [], array $included = []) {
    $assoc = [];

    // Gather the initial keys if not excluded
    foreach ($keys as $key) {
      if (! in_array($key, $excluded)) {
        $assoc[$key] = $key;
      }
    }

    // Add the included keys
    foreach ($included as $key) {
      $assoc[$key] = $key;
    }

    return $assoc;
  }
}
