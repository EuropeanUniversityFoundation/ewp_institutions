<?php

namespace Drupal\ewp_institutions_get;

class DataTransform {

  /**
   * Process JSON data
   */
  public static function toTable($data) {
    $processed = '';

    $decoded = json_decode($data, FALSE);

    // if ($decoded) {
      $processed .= '<table>';
      $processed .= '<thead><tr><td>key</td><td>value</td></tr>';
      $processed .= '<tbody>';

      foreach ($decoded as $i => $array) {
        $processed .= '<tr>';
        $processed .= '<td>' . $array['iso_code'] . '</td>';
        $processed .= '<td>' . $array['name'] . '</td>';
        $processed .= '</tr>';
      }

      $processed .= '</tbody>';
      $processed .= '</table>';
    // }

    return $processed;
  }
}
