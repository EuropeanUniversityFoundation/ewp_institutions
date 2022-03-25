<?php

namespace Drupal\ewp_institutions_user\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_user_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('ewp_institutions_user.settings');

    // Info text.
    $info = $this
      ->t('These settings apply to the user\'s Institution base field.');

    $form['info'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $info . '</p>'
    ];

    // Cardinality.
    $form['cardinality_label'] = [
      '#type' => 'label',
      '#title' => $this->t('Allowed number of values'),
    ];

    $form['cardinality'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['inline-widget']],
      '#attached' => ['library' => ['ewp_core/inline_widget']],
    ];

    $form['cardinality']['options'] = [
      '#type' => 'select',
      '#options' => [
        $this->t('Limited'),
        $this->t('Unlimited')
      ],
      '#attributes' => [
        'name' => 'cardinality_select',
      ],
    ];

    $form['cardinality']['number'] = [
      '#type' => 'number',
      '#min' => 1,
      '#size' => 2,
      '#states' => [
        'visible' => [
          ':input[name="cardinality_select"]' => ['value' => 0],
        ],
      ],
    ];

    // Required.
    $form['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required field')
    ];

    // Auto create.
    $auto_create_title = $this
      ->t("Create referenced entities if they don't already exist");

    $auto_create_description = $this
      ->t("EWP Institutions GET module must be enabled.");

    $auto_create_disabled = $this->moduleHandler
      ->moduleExists('ewp_institutions_get');

    $form['auto_create'] = [
      '#type' => 'checkbox',
      '#title' => $auto_create_title,
      '#disabled' => $auto_create_disabled
    ];

    if ($auto_create_disabled) {
      $form['auto_create']['#description'] = $auto_create_description;
    }

    return $form;
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
    $config = $this->config('ewp_institutions_user.settings');

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ewp_institutions_user.settings',
    ];
  }

}
