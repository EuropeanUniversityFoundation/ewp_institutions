<?php

namespace Drupal\ewp_institutions_get;

/**
 * Service for JSON data processing
 */
class JsonDataProcessor {

  /**
   * Validate JSON:API data object
   */
  public function validate($json) {
    $decoded = json_decode($json, TRUE);

    if (array_key_exists('data', $decoded)) {
      $status = TRUE;
      $data = $decoded['data'];
    } else {
      $status = FALSE;
      $data = [];
    }

    $output['status'] = $status;
    $output['data'] = $data;
    return $output;
  }

}
