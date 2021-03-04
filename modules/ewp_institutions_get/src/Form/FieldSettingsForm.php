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

    dpm($config);

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

    // $config->set('field_exclude', $form_state->getValue('field_exclude'));
    // $config->set('remote_exclude', $form_state->getValue('remote_exclude'));
    // $config->set('remote_include', $form_state->getValue('remote_include'));

    $config->save();

    return parent::submitForm($form, $form_state);
  }

}
