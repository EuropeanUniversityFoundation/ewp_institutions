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
    $status = TRUE;

    //  data key must be present
    if (array_key_exists('data', $decoded)) {
      foreach ($decoded['data'] as $key => $value) {
        // type key must be present
        if (! array_key_exists('type', $decoded['data'])) {
          $status = FALSE;
          watchdog_exception('ewp_institutions_get', 'type key does not exist!');
        // if key must be present
        } elseif (! array_key_exists('id', $decoded['data'])) {
          $status = FALSE;
          watchdog_exception('ewp_institutions_get', 'id key does not exist!');
        }
      }
    } else {
      $status = FALSE;
      watchdog_exception('ewp_institutions_get', 'data key does not exist!');
    }

    $data = ($status) ? $decoded['data'] : [] ;

    $output['status'] = $status;
    $output['data'] = $data;
    return $output;
  }

}
