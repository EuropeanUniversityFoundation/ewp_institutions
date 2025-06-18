<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ewp_institutions_get\JsonDataKeys;
use Drupal\ewp_institutions_get\InstitutionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings for field mapping.
 */
class FieldSettingsForm extends ConfigFormBase {

  const ENTITY_TYPE = InstitutionManager::ENTITY_TYPE;

  /**
   * The entity fields to exclude from mapping.
   *
   * @var array
   */
  protected $entityFieldsExclude;

  /**
   * The base fields to always exclude from mapping.
   *
   * @var array
   */
  protected $baseFieldsExclude = [
    'index_key',
    'id',
    'uuid',
    'langcode',
    'created',
    'changed',
  ];

  /**
   * The remote keys that match the Institution entity.
   *
   * @var array
   */
  protected $remoteKeys;

  /**
   * The remote keys to exclude from the options.
   *
   * @var array
   */
  protected $remoteExclude;

  /**
   * The remote keys to include in the options.
   *
   * @var array
   */
  protected $remoteInclude;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * JSON Data Keys service.
   *
   * @var \Drupal\ewp_institutions_get\JsonDataKeys
   */
  protected $dataKeys;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\ewp_institutions_get\JsonDataKeys $data_keys
   *   JSON Data Keys service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    EntityFieldManagerInterface $entity_field_manager,
    JsonDataKeys $data_keys,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->entityFieldManager = $entity_field_manager;
    $this->dataKeys           = $data_keys;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_field.manager'),
      $container->get('ewp_institutions_get.keys'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ewp_institutions_get.field_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_get_field_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ewp_institutions_get.field_settings');

    $form['#tree'] = TRUE;

    $form['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Exclude entity fields'),
      '#description' => $this
        ->t('Check the @fields to be excluded from field mapping, @example.', [
          '@fields' => 'target entity fields',
          '@example' => 'such as base fields and Entity References',
        ]),
    ];

    $form['fields']['field_exclude'] = [
      '#type' => 'checkboxes',
    ];

    // Load the individual entity fields.
    $fields = $this->entityFieldManager
      ->getFieldDefinitions(self::ENTITY_TYPE, self::ENTITY_TYPE);

    // Generate the options.
    $options = [];

    foreach ($fields as $field_name => $field) {
      if (!in_array($field_name, $this->baseFieldsExclude)) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['fields']['field_exclude']['#options'] = $options;

    // Get the excluded fields from configuration.
    $this->entityFieldsExclude = (array) $config->get('field_exclude');

    foreach ($this->entityFieldsExclude as $field) {
      if (array_key_exists($field, $options)) {
        $form['fields']['field_exclude'][$field]['#default_value'] = TRUE;
      }
    }

    $form['keys'] = [
      '#type' => 'details',
      '#title' => $this->t('Manage remote keys'),
      '#open' => TRUE,
    ];

    $form['keys']['key_exclude'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Exclude remote keys'),
      '#description' => $this->t('Check @keys to exclude from @options.', [
        '@keys' => 'the default remote keys',
        '@options' => 'the field mapping options',
      ]),
    ];

    $this->remoteKeys = $this->dataKeys->getDefaultKeys();
    // Build the checkbox options.
    $options = $this->dataKeys->getAssocKeys($this->remoteKeys);

    $form['keys']['key_exclude']['#options'] = $options;

    // Get the excluded keys from configuration.
    $this->remoteExclude = (array) $config->get('remote_exclude');

    foreach ($this->remoteExclude as $field) {
      $form['keys']['key_exclude'][$field]['#default_value'] = TRUE;
    }

    // Get the included keys from configuration.
    $this->remoteInclude = (array) $config->get('remote_include');

    $default_text = ($this->remoteInclude) ?
      implode("\n", $this->remoteInclude) : '';

    $form['keys']['key_include'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Include remote keys'),
      '#description' => $this->t('List @keys to include in @options @howto.', [
        '@keys' => 'the additional remote keys',
        '@options' => 'the field mapping options',
        '@howto' => '(one per line)',
      ]),
      '#default_value' => $default_text,
      '#rows' => 5,
    ];

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
    $config = $this->config('ewp_institutions_get.field_settings');

    // Fields to exclude.
    $excluded_fields = $this->baseFieldsExclude;

    $field_exclude = $form_state->getValue('fields')['field_exclude'];

    foreach ($field_exclude as $key => $value) {
      if ($field_exclude[$key]) {
        $excluded_fields[] = $key;
      }
    }

    $config->set('field_exclude', $excluded_fields);

    // Remote keys to exclude.
    $key_exclude = $form_state->getValue('keys')['key_exclude'];

    foreach ($key_exclude as $key => $value) {
      if ($key_exclude[$key]) {
        $excluded_keys[] = $key;
      }
    }

    $config->set('remote_exclude', $excluded_keys ?? []);

    // Remote keys to include.
    $key_include = $form_state->getValue('keys')['key_include'];

    $included_keys = array_filter(
      array_map('trim', explode("\n", $key_include))
    );

    $config->set('remote_include', $included_keys);

    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
