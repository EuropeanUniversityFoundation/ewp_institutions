<?php

namespace Drupal\ewp_institutions\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ewp_institutions\OtherIdTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class OtherHeiIdDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  const CUSTOM = 'custom';

  /**
   * Other ID type manager.
   *
   * @var \Drupal\ewp_institutions\OtherIdTypeManager
   */
  protected $otherIdManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    OtherIdTypeManager $other_id_manager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->otherIdManager = $other_id_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('ewp_institutions.other_id_types')
    );
  }

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
      '#title' => $this->t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $text = 'Text shown inside the form field until a value is entered.';
    $hint = 'Usually a sample value or description of the expected format.';

    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t($text . ' ' . $hint),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Textfield size: @size', [
      '@size' => $this->getSetting('size'),
    ]);

    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = $this->t('Placeholder: @placeholder', [
        '@placeholder' => $this->getSetting('placeholder'),
      ]);
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

    // Get the field name from this particular field definiton.
    $field_name = $items->getFieldDefinition()->getName();

    // Get the options from the Other ID type manager service.
    $options = $this->otherIdManager->getOptions();
    $options[self::CUSTOM] = '- ' . $this->t('custom type') . ' -';

    // Get the field defaults.
    $default_type = $items[$delta]->type ?? NULL;
    $default_option = NULL;
    $default_custom = NULL;
    $default_value = $items[$delta]->value ?? NULL;

    // Handle the custom type case.
    if ($default_type) {
      if (array_key_exists($default_type, $options)) {
        $default_option = $default_type;
        $default_custom = NULL;
      }
      else {
        $default_option = self::CUSTOM;
        $default_custom = $default_type;
      }
    }

    $element['type'] = [
      '#type' => 'select',
      '#options' => $options,
      '#empty_option' => '- ' . $this->t('ID type') . ' -',
      '#empty_value' => '',
      '#default_value' => $default_option,
      '#attributes' => [
        'id' => [$field_name . '-type-' . $delta],
      ],
    ];

    $element[self::CUSTOM] = [
      '#type' => 'textfield',
      '#default_value' => $default_custom,
      '#size' => 15,
      '#placeholder' => $this->t('custom type key'),
      '#states' => [
        'visible' => [
          'select[id="' . $field_name . '-type-' . $delta . '"]' => [
            'value' => self::CUSTOM,
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
      '#attributes' => ['class' => ['inline-shrink']],
    ];

    // If cardinality is 1, ensure a proper label is output for the field.
    $cardinality = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getCardinality();

    if ($cardinality === 1) {
      $element['type']['#title'] = $element['#title'];
      $element[self::CUSTOM]['#title'] = '&nbsp;';
      $element['value']['#title'] = '&nbsp;';
    }

    return $element;
  }

  /**
   * Validate the field and replace any 'custom' key with the new custom type.
   */
  public function validate($element, FormStateInterface $form_state) {
    // Extract all relevant values.
    $type = $element['type']['#value'];
    $custom = $element[self::CUSTOM]['#value'];
    $value = $element['value']['#value'];

    // Store the custom type instead of the generic key.
    if (($type === self::CUSTOM) && isset($custom)) {
      $type = $custom;
    }

    // Prepare the clean values.
    $new_value['type'] = $type;
    $new_value['value'] = $value;

    // Handle the weight for multiple value fields.
    if (array_key_exists('_weight', $element)) {
      $weight = $element['_weight']['#value'];
      $new_value['_weight'] = $weight;
    }

    // Set the value of the entire form element.
    $form_state->setValueForElement($element, $new_value);
  }

}
