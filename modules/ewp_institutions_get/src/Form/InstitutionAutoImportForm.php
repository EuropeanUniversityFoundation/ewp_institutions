<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ewp_institutions_get\Form\PreLoadForm;
use Drupal\ewp_institutions_get\InstitutionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class InstitutionAutoImportForm extends PreLoadForm {

  use StringTranslationTrait;

  /**
   * Index endpoint.
   *
   * @var string
   */
  protected $indexEndpoint;

  /**
   * Index item links.
   *
   * @var array
   */
  protected $indexLinks;

  /**
   * Index item labels.
   *
   * @var array
   */
  protected $indexLabels;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Institution manager service.
   *
   * @var \Drupal\ewp_institutions_get\InstitutionManager
   */
  protected $institutionManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->entityTypeManager  = $container->get('entity_type.manager');
    $instance->institutionManager = $container->get('ewp_institutions_get.manager');
    $instance->requestStack       = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_get_auto_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['index_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Index'),
      '#options' => $this->indexLabels,
      '#default_value' => '',
      '#empty_value' => '',
      '#ajax' => [
        'callback' => '::getInstitutionList',
        'disable-refocus' => TRUE,
        'event' => 'change',
        'wrapper' => 'hei-select',
      ],
      '#attributes' => [
        'name' => 'index_select',
      ],
      '#weight' => '-9',
    ];

    $form['hei_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Institution'),
      '#prefix' => '<div id="hei-select">',
      '#suffix' => '</div>',
      '#options' => [],
      '#default_value' => '',
      '#empty_value' => '',
      '#ajax' => [
        'callback' => '::previewInstitution',
        'disable-refocus' => TRUE,
        'event' => 'change',
        'wrapper' => 'view-hei-data',
      ],
      '#validated' => TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="index_select"]' => ['value' => ''],
        ],
      ],
      '#weight' => '-8',
    ];

    $form['messages'] = [
      '#type' => 'markup',
      '#markup' => '<div id="view-messages"></div>',
      '#weight' => '-6',
    ];

    $form['data'] = [
      '#type' => 'markup',
      '#markup' => '<div id="view-hei-data"></div>',
      '#weight' => '-5',
    ];

    return $form;
  }

  /**
  * Fetch the data and build select list
  */
  public function getInstitutionList(array $form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');
    $endpoint = ($index_item) ? $this->indexLinks[$index_item] : '';

    $options = ['' => '- None -'];

    if (! empty($endpoint)) {
      $json_data = $this->jsonDataFetcher
        ->getUpdated($index_item, $endpoint);

      if ($json_data) {
        // Build the options list
        $options += $this->jsonDataProcessor
          ->idLabel($json_data);
      }
    }

    $form['hei_select']['#options'] = $options;
    return $form['hei_select'];
  }

  /**
  * Fetch the data and preview Institution
  */
  public function previewInstitution(array $form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');
    $endpoint = ($index_item) ? $this->indexLinks[$index_item] : '';

    $output = '';

    // JSON data has to be stored at this point per previous step
    $json_data = $this->jsonDataFetcher
      ->load($index_item, $endpoint);
    $hei_list = $this->jsonDataProcessor
      ->idLabel($json_data);

    $hei_item = $form_state->getValue('hei_select');

    // Create a new Institution if none exists with the same key
    $hei = $this->institutionManager
      ->getInstitution($hei_item, $index_item);
    if (!empty($hei)) {
      foreach ($hei as $id => $value) {
        $entity_id = $id;
      }
      $view_mode = 'full';
      $entity = $this->entityTypeManager
        ->getStorage(InstitutionManager::ENTITY_TYPE)
        ->load($entity_id);
      $view_builder = $this->entityTypeManager
        ->getViewBuilder(InstitutionManager::ENTITY_TYPE);
      $pre_render = $view_builder->view($entity, $view_mode);
      $html= render($pre_render);

      $text = $this->t('This institution is now available for selection.');
      $modal = $this->requestStack->getCurrentRequest()->query->has('modal');
      if ($modal) {
        $text .= ' ';
        $text .= $this->t('You can close this popup and find it in the list.');
      }
      $this->messenger->addMessage($text);
    }
    else {
      $html = '';
    }

    $messages = StatusMessages::renderMessages();

    $ajax_response = new AjaxResponse();
    $ajax_response
      ->addCommand(new HtmlCommand('#view-messages', $messages))
      ->addCommand(new HtmlCommand('#view-hei-data', $html));
    return $ajax_response;
  }

}
