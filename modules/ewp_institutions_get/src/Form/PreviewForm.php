<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\ewp_institutions_get\Form\PreLoadForm;

class PreviewForm extends PreLoadForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_get_preview_form';
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
      '#validated' => TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="index_select"]' => ['value' => ''],
        ],
      ],
      '#weight' => '-8',
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => '-7',
    ];

    $form['actions']['get'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview Institution'),
      '#attributes' => [
        'class' => [
          'button--primary',
        ]
      ],
      '#states' => [
        'disabled' => [
          ':input[name="hei_select"]' => ['value' => ''],
        ],
      ],
      '#ajax' => [
        'callback' => '::previewInstitution',
      ],
    ];

    $form['data'] = [
      '#type' => 'markup',
      '#markup' => '<div class="response_data"></div>',
      '#weight' => '-6',
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
      // Check when the index was last updated
      $index_updated = \Drupal::service('ewp_institutions_get.fetch')->checkUpdated('index');
      // Check when this item was last updated
      $item_updated = \Drupal::service('ewp_institutions_get.fetch')->checkUpdated($index_item);
      // Decide whether to force a refresh
      if ($item_updated && $index_updated < $item_updated) {
        $refresh = FALSE;
      } else {
        $refresh = TRUE;
      }

      $json_data = \Drupal::service('ewp_institutions_get.fetch')->load($index_item, $endpoint);

      if ($json_data) {
        // Build the options list
        $options += \Drupal::service('ewp_institutions_get.json')->idLabel($json_data);
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
    $json_data = \Drupal::service('ewp_institutions_get.fetch')->load($index_item, $endpoint);
    $hei_list = \Drupal::service('ewp_institutions_get.json')->idLabel($json_data);

    $hei_item = $form_state->getValue('hei_select');

    $title = $hei_list[$hei_item];

    $data = \Drupal::service('ewp_institutions_get.json')->toArray($json_data);
    $message = \Drupal::service('ewp_institutions_get.format')->preview($title, $data, $hei_item);

    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(
      new HtmlCommand('.response_data', $message));
    return $ajax_response;
  }

}
