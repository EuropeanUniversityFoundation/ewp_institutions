<?php

namespace Drupal\ewp_institutions_user\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ewp_institutions_user\InstitutionUserBridge;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsForm extends ConfigFormBase {

  const LIMITED   = 'limited';
  const UNLIMITED = 'unlimited';

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
   * The EWP Institutions User bridge service.
   *
   * @var \Drupal\ewp_institutions_user\InstitutionUserBridge
   */
  protected $userBridge;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\ewp_institutions_user\InstitutionUserBridge $user_bridge
   *   The EWP Institutions User bridge service.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      ModuleHandlerInterface $module_handler,
      InstitutionUserBridge $user_bridge
  ) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->userBridge = $user_bridge;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('ewp_institutions_user.bridge')
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
      ->t('These settings apply to the User\'s Institution base field.');

    $form['info'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $info . '</p>'
    ];

    // Cardinality.
    $cardinality = $config->get('cardinality');

    if (\is_numeric($cardinality) && $cardinality > 0) {
      $default_option = self::LIMITED;
      $default_value = $cardinality;
    } else {
      $default_option = self::UNLIMITED;
      $default_value = 1;
    }

    $caveat = '<strong>' . $this->t("Warning") . ':</strong> ';
    $caveat .= $this
      ->t("This setting only impacts the User form, not the field storage.");

    $form['cardinality_label'] = [
      '#type' => 'label',
      '#title' => $this->t('Allowed number of values'),
      '#description' => $caveat
    ];

    $form['cardinality'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['inline-widget']],
      '#attached' => ['library' => ['ewp_core/inline_widget']],
    ];

    $form['cardinality']['options'] = [
      '#type' => 'select',
      '#options' => [
        self::LIMITED   => $this->t('Limited'),
        self::UNLIMITED => $this->t('Unlimited')
      ],
      '#default_value' => $default_option,
      '#attributes' => [
        'name' => 'cardinality_select',
      ],
    ];

    $form['cardinality']['number'] = [
      '#type' => 'number',
      '#min' => 1,
      '#size' => 2,
      '#default_value' => $default_value,
      '#states' => [
        'visible' => [
          ':input[name="cardinality_select"]' => ['value' => self::LIMITED],
        ],
      ],
    ];

    // Required.
    $required = $config->get('required');

    $form['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required field'),
      '#default_value' => ($required) ? $required : FALSE
    ];

    // Auto create.
    $auto_create = $config->get('auto_create');

    $auto_create_title = $this
      ->t("Create referenced entities if they don't already exist");

    $auto_create_description = '<strong>' . $this->t("Warning") . ':</strong> ';
    $auto_create_description .= $this
      ->t("EWP Institutions GET module must be enabled.");

    $form['auto_create'] = [
      '#type' => 'checkbox',
      '#title' => $auto_create_title,
      '#default_value' => ($auto_create) ? $auto_create : FALSE
    ];

    $auto_create_disabled = ! $this->moduleHandler
      ->moduleExists('ewp_institutions_get');

    if ($auto_create_disabled) {
      $form['auto_create']['#default_value'] = FALSE;
      $form['auto_create']['#description'] = $auto_create_description;
      $form['auto_create']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $form_state->setValue('options', $user_input['cardinality_select']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ewp_institutions_user.settings');

    if ($form_state->getValue('options') === self::LIMITED) {
      $cardinality = (int) $form_state->getValue('number');
    } else {
      $cardinality = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
    }

    $config->set('cardinality', $cardinality);

    $required = (boolean) $form_state->getValue('required');
    $config->set('required', $required);

    $auto_create = (boolean) $form_state->getValue('auto_create');
    $config->set('auto_create', $auto_create);

    $config->save();

    $this->userBridge->updateBaseField();

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
