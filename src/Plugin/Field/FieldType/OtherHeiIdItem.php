<?php

namespace Drupal\ewp_institutions\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'ewp_other_hei_id' field type.
 *
 * @FieldType(
 *   id = "ewp_other_hei_id",
 *   label = @Translation("Other HEI ID"),
 *   description = {
 *     @Translation("Stores pairs of ID type and value."),
 *     @Translation("Allows selection of predefined ID types."),
 *     @Translation("Allows custom ID types."),
 *   },
 *   category = "ewp_institutions",
 *   default_widget = "ewp_other_hei_id_default",
 *   default_formatter = "ewp_other_hei_id_default"
 * )
 */
class OtherHeiIdItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['type'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('ID type'))
      ->setRequired(TRUE);

    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Text value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'type' => [
          'type' => 'varchar_ascii',
          'length' => 32,
        ],
        'value' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    $text = $this->t('The maximum length of the field in characters.');

    $elements['max_length'] = [
      '#type' => 'number',
      '#title' => t('Maximum length'),
      '#default_value' => $this->getSetting('max_length'),
      '#required' => TRUE,
      '#description' => $text,
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
