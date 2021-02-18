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
   * Convert JSON:API data to HTML table
   */
  public function toTable($title, $json, $columns = [], $show_attr = TRUE) {
    $decoded = json_decode($json, TRUE);

    $data = $decoded['data'];

    $header = ['type','id'];

    // Additional columns
    if (!empty($columns)) {
      foreach ($columns as $key => $value) {
        $header[] = $value;
      }
    }

    // Attributes overview
    if ($show_attr) {
      $header[] = 'attributes';
    }

    $rows = [];

    foreach ($data as $item => $fields) {
      // Load the default columns first
      $type = $fields['type'];
      $id = $fields['id'];
      $row = [$type, $id];

      // Load the additional columns, if any
      foreach ($columns as $key => $value) {
        $cell = '';

        if (array_key_exists('attributes', $fields)) {
          if (array_key_exists($value, $fields['attributes'])) {
            if (is_array($fields['attributes'][$value])) {
              $array = $fields['attributes'][$value];

              if (count(array_filter(array_keys($array), 'is_string')) > 0) {
                // associative array implies a single value of a complex field
                $cell = 'complex';
              } else {
                // otherwise assume a field with multiple values
                $cell = 'multiple';
              }
            } else {
              $cell = $fields['attributes'][$value];
            }
          }
        }

        array_push($row, $cell);
      }

      // Load the attributes overview
      if ($show_attr) {
        $attributes = '';

        if (array_key_exists('attributes', $fields)) {
          $attr_list = [];

          foreach ($fields['attributes'] as $key => $value) {
            if (! empty($value)) {
              // handle complex attributes
              if (is_array($value)) {
                if (count(array_filter(array_keys($value), 'is_string')) > 0) {
                  // associative array implies a single value of a complex field
                  $attr_list[] = $key . '*';
                } else {
                  // otherwise assume a field with multiple values
                  $attr_list[] = $key . ' (' . count($value) . ')';
                }
              } else {
                $attr_list[] = $key;
              }
            }
          }

          $attributes = implode(', ', $attr_list);
        }

        array_push($row, $attributes);
      }

      $rows[] = $row;
    }

    $build['intro'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $title . '</h2>' .
        '<p><strong>' . t('Total') . ': </strong>' . count($data) . '</p>',
    ];

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
