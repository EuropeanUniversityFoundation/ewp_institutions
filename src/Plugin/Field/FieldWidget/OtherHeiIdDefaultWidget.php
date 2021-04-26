<?php

namespace Drupal\ewp_institutions\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'ewp_other_hei_id_default' widget.
 *
 * @FieldWidget(
 *   id = "ewp_other_hei_id_default",
 *   module = "ewp_institutions",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "ewp_other_hei_id"
 *   }
 * )
 */
class OtherHeiIdDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = $element + [
      '#type' => 'container',
      '#attributes' => ['class' => ['inline-widget']],
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];
    $element['#attached']['library'][] = 'ewp_core/inline_widget';

    // Get the field name from this particular field definiton
    $field_name = $items->getFieldDefinition()->getName();

    // Get the options from the Other ID type manager service
    $type_manager = \Drupal::service('ewp_institutions.other_id_types');
    $options = $type_manager->getOptions();
    $options['custom'] = '- ' . t('custom type') . ' -';

    // Get the field defaults
    $default_type = isset($items[$delta]->type) ? $items[$delta]->type : NULL;
    $default_option = NULL;
    $default_custom = NULL;
    $default_value = isset($items[$delta]->value) ? $items[$delta]->value : NULL;

    // Handle the custom type case
    if ($default_type) {
      if (array_key_exists($default_type, $options)) {
        $default_option = $default_type;
        $default_custom = NULL;
      } else {
        $default_option = 'custom';
        $default_custom = $default_type;
      }
    }

    $element['type'] = [
      '#type' => 'select',
      '#options' => $options,
      '#empty_option' => '- '.t('ID type').' -',
      '#empty_value' => '',
      '#default_value' => $default_option,
      '#attributes' => [
        'id' => [$field_name . '-type-' . $delta],
      ],
    ];

    $element['custom'] = [
      '#type' => 'textfield',
      '#default_value' => $default_custom,
      '#size' => 15,
      '#placeholder' => t('custom type key'),
      '#states' => [
        'visible' => [
          'select[id="' . $field_name . '-type-' . $delta . '"]' => [
            'value' => 'custom'
          ],
        ],
      ],
    ];

    $element['value'] = [
      '#type' => 'textfield',
      '#default_value' => $default_value,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
    ];

    // If cardinality is 1, ensure a proper label is output for the field.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element['#type'] = 'fieldset';
    }

    return $element;
  }

  /**
   * Validate the field and replace any 'custom' key with the new custom type
   */
  public function validate($element, FormStateInterface $form_state) {
    // Extract all relevant values
    $type = $element['type']['#value'];
    $custom = $element['custom']['#value'];
    $value = $element['value']['#value'];

    // Store the custom type instead of the generic key
    if (($type === 'custom') && isset($custom)) {
      $type = $custom;
    }

    // Prepare the clean values
    $new_value['type'] = $type;
    $new_value['value'] = $value;

    // Handle the weight for multiple value fields
    if (array_key_exists('_weight', $element)) {
      $weight = $element['_weight']['#value'];
      $new_value['_weight'] = $weight;
    }

    // Set the value of the entire form element.
    $form_state->setValueForElement($element, $new_value);
  }

}
