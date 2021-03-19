<?php

namespace Drupal\ewp_institutions_get;

/**
 * Service for data formatting
 */
class DataFormatter {

  /**
   * Format data as HTML table
   */
  public function toTable($title, $data, $columns = [], $show_attr = TRUE) {
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

  /**
   * Format data for preview
   */
  public function preview($title, $data, $item) {
    foreach ($data as $key => $value) {
      if ($value['id'] == $item) {
        $attributes = $value['attributes'];
      }
    }

    $build['intro'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $title . '</h2>',
    ];

    $content = '';

    foreach ($attributes as $key => $value) {
      if (! empty($value)) {
        // handle complex attributes
        if (is_array($value)) {
          $list = '';

          if (count(array_filter(array_keys($value), 'is_string')) > 0) {
            // associative array implies a single value of a complex field
            $list .= '<ul>';

            foreach ($attributes[$key] as $subkey => $subvalue) {
              $subvalue = ($subvalue) ? $subvalue : '<em>' . t('empty') . '</em>' ;
              $list .= '<li><strong>' . $subkey . ':</strong> ' . $subvalue . '</li>';
            }

            $list .= '</ul>';
          } else {
            // otherwise assume a field with multiple values
            $list .= '<ol>';

            foreach ($attributes[$key] as $delta => $fieldvalue) {
              if (is_array($fieldvalue)) {
                $sublist = '';

                // in case of multiple value complex field
                $sublist .= '<ul>';

                foreach ($attributes[$key][$delta] as $subkey => $subvalue) {
                  $subvalue = ($subvalue) ? $subvalue : '<em>' . t('empty') . '</em>' ;
                  $sublist .= '<li><strong>' . $subkey . ':</strong> ' . $subvalue . '</li>';
                }

                $sublist .= '</ul>';

                $submarkup = $sublist;
              } else {
                $submarkup = ' ' . $value . '<br />';
              }

              $list .= '<li><strong>delta ' . $delta . ':</strong> ' . $submarkup . '</li>';
            }

            $list .= '</ol>';
          }

          $markup = $list;
        } else {
          $markup = ' ' . $value . '<br />';
        }
      } else {
        $markup = ' <em>' . t('empty') . '</em><br />';
      }

      $content .= '<strong>' . $key . ':</strong>';
      $content .=  $markup;
    }

    $build['data'] = [
      '#type' => 'markup',
      '#markup' => $content,
    ];

    return [
      '#type' => '#markup',
      '#markup' => render($build)
    ];

  }

}
