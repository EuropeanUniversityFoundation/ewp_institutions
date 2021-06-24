<?php

namespace Drupal\ewp_institutions\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'ewp_other_hei_id_unique' formatter.
 *
 * @FieldFormatter(
 *   id = "ewp_other_hei_id_unique",
 *   label = @Translation("Unique"),
 *   field_types = {
 *     "ewp_other_hei_id"
 *   }
 * )
 */
class OtherHeiIdUniqueFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'displayed_id' => 'erasmus',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    /*return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);*/

    $type_manager = \Drupal::service('ewp_institutions.other_id_types');
    $types = $type_manager->getUniqueTypeList();

    $form['displayed_id'] = [
      '#title' => $this->t('Unique ID type'),
      '#type' => 'select',
      '#options' => $types,
      '#default_value' => $this->getSetting('displayed_id'),
    ];

    return $form;
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
    $types = $type_manager->getUniqueTypeList();

    foreach ($items as $delta => $item) {
      $key = $item->type;
      if($key == $this->getSetting('displayed_id')){
        $value = $item->value;
        $type = (array_key_exists($key, $types)) ? $types[$key]->render() : $key ;

        $elements[$delta] = [
          '#theme' => 'other_id',
          '#value' => $value,
          '#type' => $type,
        ];
      }
    }

    return $elements;
  }

}
