<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

class TestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_get_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load the settings.
    $config = \Drupal::config('ewp_institutions_get.settings');

    // Index endpoint field.
    $form['index_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Index endpoint'),
      '#default_value' => $config->get('ewp_institutions_get.index_endpoint'),
      '#description' => $this->t('External API endpoint that returns the main index.'),
      '#disabled' => TRUE,
      '#weight' => '-9',
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => '-7',
    ];

    $form['actions']['call'] = [
      '#type' => 'button',
      '#value' => $this->t('Perform API request'),
      '#attributes' => [
        'class' => [
          'button--primary',
        ]
      ],
      '#states' => [
        'disabled' => [
          ':input[name="index_endpoint"]' => ['value' => ''],
        ],
      ],
      '#ajax' => [
        'callback' => '::performRequest',
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
  * Make the API call
  */
  public function performRequest(array $form, FormStateInterface $form_state) {
    $endpoint = $form_state->getValue('index_endpoint');

    // Initialize an HTTP client
    $client = \Drupal::httpClient();
    $response = NULL;

    // Build the HTTP request
    try {
      $request = $client->get($endpoint);
      $response = $request->getBody();
    } catch (GuzzleException $e) {
      $response = $e->getResponse()->getBody();
    } catch (Exception $e) {
      watchdog_exception('ewp_institutions_get', $e->getMessage());
    }

    $processed = $this->toTable($response);
    $message = ($response) ? $processed : 'Nothing to display.' ;

    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(
     new HtmlCommand('.response_data', $message));
    return $ajax_response;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here...
  }

  /**
   * Convent JSON:API data to HTML table
   */
  protected function toTable($data) {
    $processed = '';

    $decoded = json_decode($data, TRUE);

    // $data = $decoded['data'];
    $data = $decoded;

    if ($data) {
      $processed .= '<table>';
      $processed .= '<thead><tr>';
      $processed .= '<th>type</th>';
      $processed .= '<th>id</th>';
      $processed .= '<th>attributes:title</th>';
      $processed .= '<th>links:self</th>';
      $processed .= '</tr></thead>';
      $processed .= '<tbody>';

      foreach ($decoded as $item => $fields) {
        $processed .= '<tr>';
        // $processed .= '<td>' . $fields['type'] . '</td>';
        $processed .= '<td>' . 'country' . '</td>';
        // $processed .= '<td>' . $fields['id'] . '</td>';
        $processed .= '<td>' . $fields['iso_code'] . '</td>';
        // $processed .= '<td>' . $fields['attributes']['title'] . '</td>';
        $processed .= '<td>' . $fields['name'] . '</td>';
        // $url = $fields['links']['self'];
        $url = 'https://hei.dev.uni-foundation.eu/sites/default/files/json/';
        $url .= $fields['iso_code'] . '.json';
        $link = '<a href="' . $url . '" target="_blank">' . $url . '</a>';
        $processed .= '<td>' . $link . '</td>';
        $processed .= '</tr>';
      }

      $processed .= '</tbody>';
      $processed .= '</table>';
    }

    return $processed;
  }

}
