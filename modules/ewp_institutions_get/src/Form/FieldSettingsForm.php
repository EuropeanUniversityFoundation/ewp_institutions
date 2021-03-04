<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FieldSettingsForm extends ConfigFormBase {

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
  protected $entityFieldsExclude;

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
  protected $remoteKeysExclude;

  /**
   * The remote keys to include in the options.
   *
   * @var array
   */
  protected $remoteKeysInclude;

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

    $form['field_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Exclude entity fields'),
      '#description' => $this->t('Check the target entity fields to be excluded from field mapping, such as base fields and Entity References'),
    ];

    $form['field_wrapper']['field_exclude'] = [
      '#type' => 'checkboxes',
    ];

    // Load the individual entity fields
    $properties = $this->entityFieldManager
      ->getFieldDefinitions($this->entityType, $this->entityBundle);

    // Generate the options
    foreach ($properties as $property_name => $property) {
      $options[$property_name] = $property->getLabel();
    }

    $form['field_wrapper']['field_exclude']['#options'] = $options;

    // Get the excluded fields from configuration
    $this->entityFieldsExclude = (array) $config->get('field_exclude');

    foreach ($this->entityFieldsExclude as $key => $value) {
      $form['field_wrapper']['field_exclude'][$value]['#default_value'] = TRUE;
    }

    $form['key_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Manage remote keys'),
      '#open' => TRUE,
    ];

    $form['key_wrapper']['key_exclude'] = [
      '#type' => 'checkboxes',
      '#title' => t('Exclude remote keys'),
      '#description' => $this->t('Check the default remote keys to exclude from the field mapping options'),
    ];

    $this->remoteKeys = \Drupal\ewp_institutions_get\RemoteKeys::getDefaultKeys();
    // Build the checkbox options
    $options = \Drupal\ewp_institutions_get\RemoteKeys::getAssocKeys($this->remoteKeys);

    $form['key_wrapper']['key_exclude']['#options'] = $options;

    // Get the excluded keys from configuration
    $this->remoteKeysExclude = (array) $config->get('remote_exclude');

    foreach ($this->remoteKeysExclude as $key => $value) {
      $form['key_wrapper']['key_exclude'][$value]['#default_value'] = TRUE;
    }

    // Get the included keys from configuration
    $this->remoteKeysInclude = (array) $config->get('remote_include');

    $default_text = ($this->remoteKeysInclude) ? implode("\n", $this->remoteKeysInclude) : '';

    $form['key_wrapper']['key_include'] = [
      '#type' => 'textarea',
      '#title' => t('Include remote keys'),
      '#description' => $this->t('List the additional remote keys to include in the field mapping options (one per line)'),
      '#default_value' => $default_text,
      '#rows' => 5,
    ];

    dpm($form);

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

    // Fields to exclude
    $field_exclude = $form_state->getValue('field_wrapper')['field_exclude'];

    foreach ($field_exclude as $key => $value) {
      if ($field_exclude[$key]) {
        $excluded_fields[] = $key;
      }
    }

    $config->set('field_exclude', $excluded_fields);

    // Remote keys to exclude
    $key_exclude = $form_state->getValue('key_wrapper')['key_exclude'];

    foreach ($key_exclude as $key => $value) {
      if ($key_exclude[$key]) {
        $excluded_keys[] = $key;
      }
    }

    $config->set('remote_exclude', $excluded_keys);

    // Remote keys to include
    $key_include = $form_state->getValue('key_wrapper')['key_include'];

    $included_keys = [];
    $included_keys = explode("\n", $key_include);
    $included_keys = array_map('trim', $included_keys);
    $included_keys = array_filter($included_keys, 'strlen');

    $config->set('remote_include', $included_keys);

    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
