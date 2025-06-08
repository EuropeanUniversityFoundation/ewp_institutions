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
 *
 */
class FieldMappingForm extends ConfigFormBase {

  const ENTITY_TYPE = InstitutionManager::ENTITY_TYPE;

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
   * The entity fields to exclude from mapping.
   *
   * @var array
   */
  protected $entityFieldsExclude;

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

    $this->remoteKeys = $this->dataKeys->getDefaultKeys();
    // Build the select options.
    $options = $this->dataKeys->getAssocKeys(
      $this->remoteKeys,
      $this->remoteKeysExclude,
      $this->remoteKeysInclude
    );

    // Load the individual entity fields.
    $fields = $this->entityFieldManager
      ->getFieldDefinitions(self::ENTITY_TYPE, self::ENTITY_TYPE);

    // Then exclude fields as defined in the field settings.
    foreach ($this->entityFieldsExclude as $excluded) {
      if (array_key_exists($excluded, $fields)) {
        unset($fields[$excluded]);
      }
    }

    foreach ($fields as $field_name => $field) {
      $default = $fieldmap[$field_name] ?? '';

      $form['field_mapping'][$field_name] = [
        '#type' => 'select',
        '#title' => $field->getLabel(),
        '#description' => $field->getDescription(),
        '#options' => (array) $options,
        '#empty_value' => '',
        '#empty_option' => $this->t('- No mapping -'),
        '#default_value' => $default,
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
