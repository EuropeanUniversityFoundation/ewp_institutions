<?php

namespace Drupal\ewp_institutions\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ewp_institutions\OtherIdTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ewp_other_hei_id_unique' formatter.
 */
#[FieldFormatter(
  id: 'ewp_other_hei_id_unique',
  label: new TranslatableMarkup('Unique'),
  field_types: [
    'ewp_other_hei_id',
  ],
)]
class OtherHeiIdUniqueFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  const DISPLAYED_ID = 'displayed_id';
  const DISPLAY_LABEL = 'display_label';

  /**
   * Other ID type manager.
   *
   * @var \Drupal\ewp_institutions\OtherIdTypeManagerInterface
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
    OtherIdTypeManagerInterface $other_id_manager,
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
      self::DISPLAYED_ID => 'erasmus',
      self::DISPLAY_LABEL => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $types = $this->otherIdManager->getUniqueTypeList();

    $form[self::DISPLAYED_ID] = [
      '#title' => $this->t('Unique ID type'),
      '#type' => 'select',
      '#options' => $types,
      '#default_value' => $this->getSetting(self::DISPLAYED_ID),
    ];

    $form[self::DISPLAY_LABEL] = [
      '#title' => $this->t('Display ID type'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting(self::DISPLAY_LABEL),
    ];

    $form += parent::settingsForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $types = $this->otherIdManager->getUniqueTypeList();

    $summary = [];

    $summary[] = $this->t('Unique ID type: @type', [
      '@type' => $types[$this->getSetting(self::DISPLAYED_ID)],
    ]);

    $summary[] = $this->t('Display ID type: @bool', [
      '@bool' => ($this->getSetting(self::DISPLAY_LABEL))
        ? $this->t('Yes')
        : $this->t('No'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $types = $this->otherIdManager->getUniqueTypeList();

    foreach ($items as $delta => $item) {
      $key = $item->type;

      if ($key === $this->getSetting(self::DISPLAYED_ID)) {
        $type = (array_key_exists($item->type, $types))
          ? $types[$item->type]->render()
          : $item->type;

        $elements[$delta] = [
          '#theme' => 'other_id_unique',
          '#value' => $item->value,
        ];

        if ($this->getSetting(self::DISPLAY_LABEL)) {
          $elements[$delta]['#type'] = $type;
        }
      }
    }

    return $elements;
  }

}
