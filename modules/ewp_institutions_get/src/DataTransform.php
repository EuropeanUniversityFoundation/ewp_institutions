<?php

namespace Drupal\ewp_institutions_get;

class DataTransform {

  /**
   * Convent JSON:API data to HTML table
   */
  public static function toTable($data) {
    $processed = '';

    $decoded = json_decode($data, TRUE);

    // $data = $decoded['data'];
    $data = $decoded;

    if ($data) {
      $processed .= '<table>';
      $processed .= '<thead><tr>';
      $processed .= '<th>type</th>';
      $processed .= '<th>id</th>';
      $processed .= '<th>attributes:title</th>';
      $processed .= '<th>links:self</th>';
      $processed .= '</tr></thead>';
      $processed .= '<tbody>';

      foreach ($decoded as $item => $fields) {
        $processed .= '<tr>';
        // $processed .= '<td>' . $fields['type'] . '</td>';
        $processed .= '<td>' . 'country' . '</td>';
        // $processed .= '<td>' . $fields['id'] . '</td>';
        $processed .= '<td>' . $fields['iso_code'] . '</td>';
        // $processed .= '<td>' . $fields['attributes']['title'] . '</td>';
        $processed .= '<td>' . $fields['name'] . '</td>';
        // $url = $fields['links']['self'];
        $url = 'https://hei.dev.uni-foundation.eu/sites/default/files/json/';
        $url .= $fields['iso_code'] . '.json';
        $link = '<a href="' . $url . '" target="_blank">' . $url . '</a>';
        $processed .= '<td>' . $link . '</td>';
        $processed .= '</tr>';
      }

      $processed .= '</tbody>';
      $processed .= '</table>';
    }

    return $processed;
  }
}
