<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FieldMappingForm extends ConfigFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      EntityFieldManagerInterface $entity_field_manager,
      EntityTypeBundleInfoInterface $entity_type_bundle_info,
      EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($config_factory);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ewp_institutions_get.settings.fieldmap',
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
    // Get all Content Entity Types
    $content_entity_types = [];
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $key => $value) {
      if ($value->getGroup() === 'content') {
        $content_entity_types[$key] = $value;
      }
    }

    // Build a list of options
    $entity_type_list = ['' => $this->t('- None -')];

    foreach ($content_entity_types as $key => $value) {
      $entity_type_list[$key] = $value->getLabel()->render();
    }

    $form['entity_type_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#options' => $entity_type_list,
      '#default_value' => '',
      '#empty_value' => '',
      '#ajax' => [
        'callback' => '::getEntityBundles',
        'disable-refocus' => TRUE,
        'event' => 'change',
        'wrapper' => 'bundle-select',
      ],
      '#weight' => '-9',
    ];

    $form['entity_bundle_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity bundle'),
      '#prefix' => '<div id="bundle-select">',
      '#suffix' => '</div>',
      '#options' => [],
      '#default_value' => '',
      '#empty_value' => '',
      '#validated' => TRUE,
      '#ajax' => [
        'callback' => '::getBundleFields',
        // 'disable-refocus' => TRUE,
        // 'event' => 'change',
        // 'wrapper' => 'field-select-group',
      ],
      '#weight' => '-8',
    ];

    // $mappings = $this->configFactory()
    //   ->getEditable('ewp_institutions_get.settings.fieldmap');

    // $form['#tree'] = TRUE;
    // $form['field_mapping'] = [
    //   '#title' => $this->t('Field mapping'),
    //   '#type' => 'fieldset',
    // ];
    //
    // $properties = $this->entityFieldManager->getFieldDefinitions('hei', 'hei');
    // foreach ($properties as $property_name => $property) {
    //   $form['field_mapping'][$property_name] = [
    //     '#type' => 'select',
    //     '#title' => $property->getLabel(),
    //     '#description' => $property->getDescription(),
    //     '#options' => (array) $claims,
    //     '#empty_value' => 0,
    //     '#empty_option' => $this->t('- No mapping -'),
    //     '#default_value' => isset($mappings[$property_name]) ? $mappings[$property_name] : $default_value,
    //   ];
    // }

    $form['debug'] = [
      '#type' => 'markup',
      '#markup' => '<div class="debug"></div>',
      '#weight' => '-6',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
  * Fetch the entity bundles and build select list
  */
  public function getEntityBundles(array $form, FormStateInterface $form_state) {
    $entity_type = $form_state->getValue('entity_type_select');

    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);

    $options = ['' => '- None -'];
    foreach ($bundle_info as $key => $value) {
      $options[$key] .= $value['label'];
    }

    $form['entity_bundle_select']['#options'] = $options;
    return $form['entity_bundle_select'];
  }

  /**
  * Fetch the entity bundle fields and build select lists
  */
  public function getBundleFields(array $form, FormStateInterface $form_state) {
    $entity_type = $form_state->getValue('entity_type_select');
    $entity_bundle = $form_state->getValue('entity_bundle_select');

    $properties = $this->entityFieldManager->getFieldDefinitions($entity_type, $entity_bundle);

    foreach ($properties as $key => $value) {
      $message .= '<pre>' . $key . ': ' . $value->getLabel() . '</pre>';
    }

    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(
      new HtmlCommand('.debug', $message));
    return $ajax_response;
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

    return parent::submitForm($form, $form_state);
  }

}
