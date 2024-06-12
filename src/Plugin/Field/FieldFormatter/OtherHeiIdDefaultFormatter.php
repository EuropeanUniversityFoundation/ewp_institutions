<?php

namespace Drupal\ewp_institutions\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ewp_institutions\OtherIdTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class OtherHeiIdDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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
      $label,
      $view_mode,
      array $third_party_settings,
      OtherIdTypeManager $other_id_manager
    ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('ewp_institutions.other_id_types')
    );
  }

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
    $types = $this->otherIdManager->getDefinedTypes();

    $elements = [];

    foreach ($items as $delta => $item) {
      $value = $item->value;
      $key = $item->type;
      $type = (array_key_exists($key, $types)) ? $types[$key]->render() : $key;

      $elements[$delta] = [
        '#theme' => 'other_id',
        '#value' => $value,
        '#type' => $type,
      ];
    }

    return $elements;
  }

}
