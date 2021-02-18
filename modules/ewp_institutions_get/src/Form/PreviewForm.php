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
      '#options' => $this->index_items,
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
  * Make the API call and build select list
  */
  public function getInstitutionList(array $form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');
    $endpoint = ($index_item) ? $this->api_index[$index_item] : '';

    // Initialize an HTTP client
    $client = \Drupal::httpClient();
    $response = NULL;

    $options = ['' => '- None -'];

    if (! empty($endpoint)) {
      // Build the HTTP request
      try {
        $request = $client->get($endpoint);
        $response = $request->getBody();
      } catch (GuzzleException $e) {
        $response = $e->getResponse()->getBody();
      } catch (Exception $e) {
        watchdog_exception('ewp_institutions_get', $e->getMessage());
      }

      // Validate the response
      $validated = \Drupal::service('ewp_institutions_get.json')->validate($response);

      if ($validated) {
        // Extract the data from the Guzzle Stream
        $decoded = json_decode($response, TRUE);
        // Encode and store data for further operations
        $json_data = json_encode($decoded);
        $this->temp_store->set('hei_json_data', $json_data);
        // Build the options list
        $options += \Drupal::service('ewp_institutions_get.json')->idLabel($json_data);
      }
    }

    $form['hei_select']['#options'] = $options;
    return $form['hei_select'];
  }

  /**
  * Load data and preview Institution
  */
  public function previewInstitution(array $form, FormStateInterface $form_state) {
    // Retrieve the data from temporary storage
    $data = $this->temp_store->get('hei_json_data');

    $hei_item = $form_state->getValue('hei_select');
    $hei_list = \Drupal::service('ewp_institutions_get.json')->idLabel($data);
    $title = $hei_list[$hei_item];

    $message = \Drupal::service('ewp_institutions_get.json')->preview($title, $data);

    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(
      new HtmlCommand('.response_data', $message));
    return $ajax_response;

  }

}
