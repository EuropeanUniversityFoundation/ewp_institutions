<?php

namespace Drupal\ewp_institutions\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'ewp_other_hei_id_default' formatter.
 *
 * @FieldFormatter(
 *   id = "ewp_other_hei_id_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "ewp_other_hei_id"
 *   }
 * )
 */
class OtherHeiIdDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $type_manager = \Drupal::service('ewp_institutions.other_id_types');
    $types = $type_manager->getDefinedTypes();

    $elements = [];

    foreach ($items as $delta => $item) {
      $value = $item->value;
      $key = $item->type;
      $type = (array_key_exists($key, $types)) ? $types[$key]->render() : $key ;

      $elements[$delta] = [
        '#theme' => 'other_id',
        '#value' => $value,
        '#type' => $type,
      ];
    }

    return $elements;
  }

}
