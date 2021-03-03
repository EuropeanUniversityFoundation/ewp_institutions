<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FieldMappingForm extends ConfigFormBase {

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType = 'hei';

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $entityBundle = 'hei';

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity fields to exclude from mapping.
   *
   * @var array
   */
  protected $entityFieldsExclude = ['id', 'uuid', 'langcode'];

  /**
   * The remote keys that match the Institution entity.
   *
   * @var array
   */
  protected $remoteKeys = [
    // 'id',
    // 'uuid',
    // 'langcode',
    'status',
    'label',
    'created',
    'changed',
    'abbreviation',
    'contact',
    'hei_id',
    'logo_url',
    'mailing_address',
    'mobility_factsheet_url',
    'name',
    'other_id',
    'street_address',
    'website_url'
  ];

  /**
   * The remote keys to exclude from the options.
   *
   * @var array
   */
  protected $remoteKeysExclude = ['status', 'created'];

  /**
   * The remote keys to include in the options.
   *
   * @var array
   */
  protected $remoteKeysInclude = ['city', 'country'];

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      EntityFieldManagerInterface $entity_field_manager
  ) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ewp_institutions_get.fieldmap',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_get_field_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ewp_institutions_get.fieldmap');
    $fieldmap = $config->get('field_mapping');

    $form['#tree'] = TRUE;
    $form['field_mapping'] = [
      '#title' => $this->t('Field mapping'),
      '#type' => 'fieldset',
      '#prefix' => '<div id="field-mapping">',
      '#suffix' => '</div>',
    ];

    // Build the select options
    $options = [];

    // Load the remote keys and exclude some
    foreach ($this->remoteKeys as $key) {
      if (! in_array($key, $this->remoteKeysExclude)) {
        $options[$key] = $key;
      }
    }

    // Then include some
    foreach ($this->remoteKeysInclude as $key) {
      $options[$key] = $key;
    }

    // Load the individual entity fields
    $properties = $this->entityFieldManager
      ->getFieldDefinitions($this->entityType, $this->entityBundle);

    // Then exclude some
    foreach ($this->entityFieldsExclude as $i => $key) {
      if (array_key_exists($key, $properties)) {
        unset($properties[$key]);
      }
    }

    foreach ($properties as $property_name => $property) {
      $form['field_mapping'][$property_name] = [
        '#type' => 'select',
        '#title' => $property->getLabel(),
        '#description' => $property->getDescription(),
        '#options' => (array) $options,
        '#empty_value' => '',
        '#empty_option' => $this->t('- No mapping -'),
        '#default_value' => isset($fieldmap[$property_name]) ? $fieldmap[$property_name] : '',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ewp_institutions_get.fieldmap');

    $config->set('field_mapping', $form_state->getValue('field_mapping'));

    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
