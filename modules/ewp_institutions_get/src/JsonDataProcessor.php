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
    $message = '';
    $status = TRUE;

    if (array_key_exists('data', $decoded)) {
      foreach ($decoded['data'] as $key => $value) {
        if (! array_key_exists('type', $decoded['data'][0])) {
          $message = t('Type key is not present.');
        } elseif (! array_key_exists('id', $decoded['data'][0])) {
          $message = t('ID key is not present.');
        }
      }
    } else {
      $message = t('Data key is not present.');
    }

    if ($message) {
      \Drupal::logger('ewp_institutions_get')->notice($message);
      $status = FALSE;
    }

    return $status;
  }

  /**
   * Convert JSON:API data to HTML table
   */
  public function toTable($json) {
    $decoded = json_decode($json, TRUE);

    $data = $decoded['data'];

    $header = [
      'type' => t('Type'),
      'id' => t('ID'),
      'label' => t('Label'),
    ];

    $rows = [];

    foreach ($data as $item => $fields) {
      $type = $fields['type'];
      $id = $fields['id'];
      $label = $fields['attributes']['label'];

      $rows[] = [$type, $id, $label];
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return [
      '#type' => '#markup',
      '#markup' => render($build)
    ];

  }

}
