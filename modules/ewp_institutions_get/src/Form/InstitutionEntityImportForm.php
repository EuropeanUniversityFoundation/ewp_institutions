<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormStateInterface;
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

    // Place the original form in a details element
    $form['add-form'] += [
      '#type' => 'details',
      '#title' => 'Add form'
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
      }
    } else {
      $warning = $this->t("Index endpoint is not defined.");
      \Drupal::service('messenger')->addWarning($warning);
    }

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
        'wrapper' => 'data',
      ],
      '#validated' => TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="index_select"]' => ['value' => ''],
        ],
      ],
      '#weight' => '-8',
    ];

    $form['data'] = [
      '#type' => 'markup',
      '#markup' => '<div id="data"></div>',
      '#weight' => '-6',
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

    foreach ($form['add-form'] as $name => $array) {
      // Target the fields in the form render array
      if ((substr($name,0,1) !== '#') && (array_key_exists('widget', $array))) {
        // Remove non mapped fields from the form
        // Preserve the required fields that aren't mapped
        if (!array_key_exists($name, $fieldmap) && !$array['widget']['#required']) {
          unset($form['add-form'][$name]);
        }
      }
    }

    // dpm($fieldmap);
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
      if ($item_updated && $index_updated < $item_updated) {
        $refresh = FALSE;
      } else {
        $refresh = TRUE;
      }

      $json_data = \Drupal::service('ewp_institutions_get.fetch')
        ->load($index_item, $endpoint);

      if ($json_data) {
        // Build the options list
        $options += \Drupal::service('ewp_institutions_get.json')
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

    // JSON data has to be stored at this point per previous step
    $json_data = \Drupal::service('ewp_institutions_get.fetch')
      ->load($index_item, $endpoint);
    $hei_list = \Drupal::service('ewp_institutions_get.json')
      ->idLabel($json_data);

    $hei_item = $form_state->getValue('hei_select');

    $title = $hei_list[$hei_item];

    $message = \Drupal::service('ewp_institutions_get.json')
      ->preview($title, $json_data, $hei_item);

    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(
      new HtmlCommand('#data', $message));
    return $ajax_response;
  }

}
