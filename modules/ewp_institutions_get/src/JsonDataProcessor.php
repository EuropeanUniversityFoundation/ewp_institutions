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
   * Create an array of id => attributes['label'] or similar
   */
  public function idLabel($json) {
    $decoded = json_decode($json, TRUE);

    $data = $decoded['data'];

    $index = [];

    foreach ($data as $item => $fields) {
      if (array_key_exists('attributes', $fields)) {
        if (array_key_exists('label', $fields['attributes'])) {
          // the expectation is to find an entity label
          $index[$fields['id']] = $fields['attributes']['label'];
        } elseif (array_key_exists('title', $fields['attributes'])) {
          // alternatively one might find a node title instead
          $index[$fields['id']] = $fields['attributes']['title'];
        } else {
          // when none of these attributes can be found, use the ID itself
          $index[$fields['id']] = $fields['id'];
        }
      } else {
        // when no attribute object can be found, use the ID itself
        $index[$fields['id']] = $fields['id'];
      }
    }

    // Sort by label for improved usability
    \natcasesort($index);
    return $index;
  }

  /**
   * Create an array of id => links
   */
  public function idLinks($json, $link_key) {
    $decoded = json_decode($json, TRUE);

    $data = $decoded['data'];

    $index = [];

    foreach ($data as $item => $fields) {
      if (array_key_exists('links', $fields) && array_key_exists($link_key, $fields['links'])) {
        if (array_key_exists('href', $fields['links'][$link_key])) {
          // When the link key points to an object
          $index[$fields['id']] = $fields['links'][$link_key]['href'];
        } else {
          // When the link key points to the URL
          $index[$fields['id']] = $fields['links'][$link_key];
        }
      } else {
        // when no link can be found, leave it empty
        $index[$fields['id']] = '';
      }
    }

    return $index;
  }

  /**
   * Convert JSON:API data to array
   */
  public function toArray($json, $expand = FALSE) {
    $decoded = json_decode($json, TRUE);

    $data = $decoded['data'];

    foreach ($data as $index => $data_array) {
      if ($expand && array_key_exists('attributes', $data_array)) {
        foreach ($data_array['attributes'] as $attr => $value) {
          if (! empty($value)) {
            if (! is_array($value)) {
              $data[$index]['attributes'][$attr] = [['value' => $value]];
            }
            if (count(array_filter(array_keys($value), 'is_string')) > 0) {
              $data[$index]['attributes'][$attr] = [$value];
            }
          }
        }
      }
    }

    return $data;
  }

}
