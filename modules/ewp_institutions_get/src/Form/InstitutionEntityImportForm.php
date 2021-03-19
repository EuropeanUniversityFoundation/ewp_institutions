<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\ewp_institutions\Form\InstitutionEntityForm;
use Drupal\ewp_institutions_get\Form\PreviewForm;

/**
 * Alternative for Institution Add form.
 *
 * @ingroup ewp_institutions
 */
class InstitutionEntityImportForm extends InstitutionEntityForm {

  /**
   * Index endpoint
   *
   * @var string
   */
  protected $indexEndpoint;

  /**
   * Index link key
   *
   * @var string
   */
  protected $indexLinkKey;

  /**
   * Index item links
   *
   * @var array
   */
  protected $indexLinks;

  /**
   * Index item labels
   *
   * @var array
   */
  protected $indexLabels;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hei_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\ewp_institutions\Entity\InstitutionEntity $entity */
    $form['add-form'] = parent::buildForm($form, $form_state);

    // Hide the original form
    $form['add-form'] += ['#type' => 'container'];
    $form['add-form']['#attributes'] += [
      'style' => [
        'display' => "display: none",
      ],
    ];

    // Load the settings.
    $settings = \Drupal::config('ewp_institutions_get.settings');
    $this->indexEndpoint = $settings->get('ewp_institutions_get.index_endpoint');
    $this->indexLinkKey = 'list';
    $this->indexLinks = [];
    $this->indexLabels = [];

    if (! empty($this->indexEndpoint)) {
      $json_data = \Drupal::service('ewp_institutions_get.fetch')
        ->load('index', $this->indexEndpoint);

      if ($json_data) {
        $this->indexLinks = \Drupal::service('ewp_institutions_get.json')
          ->idLinks($json_data, $this->indexLinkKey);
        $this->indexLabels = \Drupal::service('ewp_institutions_get.json')
          ->idLabel($json_data);
        } else {
          $error = $this->t("No available data.");
          \Drupal::service('messenger')->addError($error);
        }
    } else {
      $warning = $this->t("Index endpoint is not defined.");
      \Drupal::service('messenger')->addWarning($warning);
    }

    // Build the form header with the AJAX components
    $form['header'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select an Institution to import'),
      '#weight' => '-100'
    ];

    $form['header']['index_select'] = [
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

    $form['header']['hei_select'] = [
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
        'wrapper' => 'messages',
      ],
      '#validated' => TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="index_select"]' => ['value' => ''],
        ],
      ],
      '#weight' => '-8',
    ];

    $form['header']['messages'] = [
      '#type' => 'markup',
      '#markup' => '<div id="messages"></div>',
    ];

    // Load the fieldmap
    $config = $this->config('ewp_institutions_get.fieldmap');
    $fieldmap = $config->get('field_mapping');

    // Remove empty values
    foreach ($fieldmap as $key => $value) {
      if (empty($fieldmap[$key])) {
        unset($fieldmap[$key]);
      }
    }

    // foreach ($form['add-form'] as $name => $array) {
    //   // Target the fields in the form render array
    //   if ((substr($name,0,1) !== '#') && (array_key_exists('widget', $array))) {
    //     // Remove non mapped, non required fields from the form
    //     // If a default value is set, it will not be lost
    //     if (!array_key_exists($name, $fieldmap) && !$array['widget']['#required']) {
    //       unset($form['add-form'][$name]);
    //     }
    //   }
    // }
    //

    // dpm($form['add-form']);

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
      // Check when the index was last updated
      $index_updated = \Drupal::service('ewp_institutions_get.fetch')
        ->checkUpdated('index');

      // Check when this item was last updated
      $item_updated = \Drupal::service('ewp_institutions_get.fetch')
        ->checkUpdated($index_item);

      // Decide whether to force a refresh
      $refresh = ($item_updated && $index_updated < $item_updated) ? FALSE : TRUE ;

      $json_data = \Drupal::service('ewp_institutions_get.fetch')
        ->load($index_item, $endpoint);

      if ($json_data) {
        // Build the options list
        $options += \Drupal::service('ewp_institutions_get.json')
          ->idLabel($json_data);
      }
    }

    $form['header']['hei_select']['#options'] = $options;

    return $form['header']['hei_select'];
  }

  /**
  * Fetch the data and preview Institution
  */
  public function previewInstitution(array $form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');

    $endpoint = ($index_item) ? $this->indexLinks[$index_item] : '';

    // JSON data has to be stored at this point per previous step
    $json_data = \Drupal::service('ewp_institutions_get.fetch')
      ->load($index_item, $endpoint);

    $hei_list = \Drupal::service('ewp_institutions_get.json')
      ->idLabel($json_data);

    $hei_id = $form_state->getValue('hei_select');

    // Check if an entity with the same hei_id already exists
    $exists = \Drupal::entityTypeManager()->getStorage('hei')
      ->loadByProperties(['hei_id' => $hei_id]);

    if (!empty($exists)) {
      foreach ($exists as $id => $hei) {
        $link = $hei->toLink();
        $renderable = $link->toRenderable();
      }

      $error = $this->t('Institution with ID <code>@hei_id</code> already exists: @link', [
        '@hei_id' => $hei_id,
        '@link' => render($renderable),
      ]);

      \Drupal::service('messenger')->addError($error);

      $message = StatusMessages::renderMessages();
    } else {
      $title = $hei_list[$hei_id];

      $message = \Drupal::service('ewp_institutions_get.json')
        ->preview($title, $json_data, $hei_id);
    }

    $ajax_response = new AjaxResponse();
    $ajax_response
      ->addCommand(new HtmlCommand('#messages', $message));

    return $ajax_response;
  }

  /**
  * Populate the form
  */
  protected function populateForm(array $form, FormStateInterface $form_state) {
  }

}
