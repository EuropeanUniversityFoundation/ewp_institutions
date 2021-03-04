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

    $field_settings = $this->config('ewp_institutions_get.field_settings');
    $this->entityFieldsExclude = (array) $field_settings->get('field_exclude');
    $this->remoteKeysExclude = (array) $field_settings->get('remote_exclude');
    $this->remoteKeysInclude = (array) $field_settings->get('remote_include');

    $form['#tree'] = TRUE;
    $form['field_mapping'] = [
      '#title' => $this->t('Field mapping'),
      '#type' => 'fieldset',
    ];

    $this->remoteKeys = \Drupal\ewp_institutions_get\RemoteKeys::getDefaultKeys();
    // Build the select options
    $options = \Drupal\ewp_institutions_get\RemoteKeys::getAssocKeys(
      $this->remoteKeys,
      $this->remoteKeysExclude,
      $this->remoteKeysInclude
    );

    // Load the individual entity fields
    $fields = $this->entityFieldManager
      ->getFieldDefinitions($this->entityType, $this->entityBundle);

    // Then exclude fields as defined in the field settings
    foreach ($this->entityFieldsExclude as $excluded) {
      if (array_key_exists($excluded, $fields)) {
        unset($fields[$excluded]);
      }
    }

    foreach ($fields as $field_name => $field) {
      $form['field_mapping'][$field_name] = [
        '#type' => 'select',
        '#title' => $field->getLabel(),
        '#description' => $field->getDescription(),
        '#options' => (array) $options,
        '#empty_value' => '',
        '#empty_option' => $this->t('- No mapping -'),
        '#default_value' => isset($fieldmap[$field_name]) ? $fieldmap[$field_name] : '',
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
