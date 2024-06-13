<?php

namespace Drupal\ewp_institutions_get;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Data formatter service.
 */
class DataFormatter {

  use StringTranslationTrait;

  // JSON data keys
  const TYPE_KEY = 'type';
  const ID_KEY = 'id';
  const ATTR_KEY = 'attributes';

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Constructs a new DataFormatter.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    RendererInterface $renderer,
    TranslationInterface $string_translation
  ) {
    $this->renderer = $renderer;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Format data as HTML table.
   */
  public function toTable($title, $data, $columns = [], $show_attr = TRUE) {
    $header = [self::TYPE_KEY, self::ID_KEY];

    // Additional columns.
    if (!empty($columns)) {
      foreach ($columns as $key => $value) {
        $header[] = $value;
      }
    }

    // Attributes overview.
    if ($show_attr) {
      $header[] = self::ATTR_KEY;
    }

    $rows = [];

    foreach ($data as $fields) {
      // Load the default columns first.
      $type = $fields[self::TYPE_KEY];
      $id = $fields[self::ID_KEY];
      $row = [$type, $id];

      // Load the additional columns, if any.
      foreach ($columns as $key => $value) {
        $cell = '';

        if (array_key_exists(self::ATTR_KEY, $fields)) {
          if (array_key_exists($value, $fields[self::ATTR_KEY])) {
            if (is_array($fields[self::ATTR_KEY][$value])) {
              $array = $fields[self::ATTR_KEY][$value];

              if (count(array_filter(array_keys($array), 'is_string')) > 0) {
                // A keyed array implies a single value of a complex field.
                $cell = $this->t('complex');
              }
              else {
                // Otherwise assume a field with multiple values.
                $cell = $this->t('multiple');
              }
            }
            else {
              $cell = $fields[self::ATTR_KEY][$value];
            }
          }
        }

        array_push($row, $cell);
      }

      // Load the attributes overview.
      if ($show_attr) {
        $attributes = '';

        if (array_key_exists(self::ATTR_KEY, $fields)) {
          $attr_list = [];

          foreach ($fields[self::ATTR_KEY] as $key => $value) {
            if (!empty($value)) {
              // Handle complex attributes.
              if (is_array($value)) {
                if (count(array_filter(array_keys($value), 'is_string')) > 0) {
                  // A keyed array implies a single value of a complex field.
                  $attr_list[] = $key . '*';
                }
                else {
                  // Otherwise assume a field with multiple values.
                  $attr_list[] = $key . ' (' . count($value) . ')';
                }
              }
              else {
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

    $total = '<p><strong>' . $this->t('Total: @count', [
      '@count' => count($data),
    ]) . '</strong></p>';

    $build['intro'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $title . '</h2>' . $total,
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return [
      '#type' => '#markup',
      '#markup' => $this->renderer->render($build),
    ];
  }

  /**
   * Format data for preview.
   */
  public function preview($title, $data, $item, $show_empty = TRUE) {
    foreach ($data as $key => $value) {
      if ($value[self::ID_KEY] == $item) {
        $attributes = $value[self::ATTR_KEY];
      }
    }

    $build['intro'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $title . '</h2>',
    ];

    $content = '';
    $empty = '<em>' . $this->t('empty') . '</em>';

    foreach ($attributes ?? [] as $key => $value) {
      if (!empty($value)) {
        // Handle complex attributes.
        if (is_array($value)) {
          $list = '';

          if (count(array_filter(array_keys($value), 'is_string')) > 0) {
            // A keyed array implies a single value of a complex field.
            $list .= '<ul>';

            foreach ($attributes[$key] as $subkey => $subvalue) {
              if ($subvalue || $show_empty) {
                $subvalue = ($subvalue) ? $subvalue : $empty;
                $list .= '<li><strong>' . $subkey . ':</strong> ';
                $list .= $subvalue . '</li>';
              }
            }

            $list .= '</ul>';
          }
          else {
            // Otherwise assume a field with multiple values.
            $list .= '<ol start="0">';

            foreach ($attributes[$key] as $delta => $fieldvalue) {
              if (is_array($fieldvalue)) {
                $sublist = '';

                // In case of multiple value complex field.
                $sublist .= '<ul>';

                foreach ($attributes[$key][$delta] as $subkey => $subvalue) {
                  if ($subvalue || $show_empty) {
                    $subvalue = ($subvalue) ? $subvalue : $empty;
                    $sublist .= '<li><strong>' . $subkey . ':</strong> ';
                    $sublist .= $subvalue . '</li>';
                  }
                }

                $sublist .= '</ul>';

                $submarkup = $sublist;
              }
              else {
                $submarkup = ' ' . $fieldvalue . '<br />';
              }

              $list .= '<li>' . $submarkup . '</li>';
            }

            $list .= '</ol>';
          }

          $markup = $list;
        }
        else {
          $markup = ' ' . $value . '<br />';
        }
      }
      else {
        $markup = ' ' . $empty . '<br />';
      }

      $content .= '<strong>' . $key . ':</strong>';
      $content .= $markup;
    }

    $build['data'] = [
      '#type' => 'markup',
      '#markup' => $content,
    ];

    return [
      '#type' => '#markup',
      '#markup' => $this->renderer->render($build),
    ];
  }

}
