<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_get_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('ewp_institutions_get.settings');

    // Index endpoint field.
    $form['index_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Index endpoint'),
      '#default_value' => $config->get('ewp_institutions_get.index_endpoint'),
      '#description' => $this->t('External API endpoint that returns the main index.'),
    ];

    $form['refresh'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Refresh temporary storage on Save'),
      '#default_value' => FALSE,
      '#return_value' => TRUE,
    ];

    $form['actions']['get'] = [
      '#type' => 'button',
      '#value' => $this->t('GET API Index'),
      '#states' => [
        'disabled' => [
          ':input[name="index_endpoint"]' => ['value' => ''],
        ],
      ],
      '#ajax' => [
        'callback' => '::getIndex',
      ],
    ];

    $form['data'] = [
      '#type' => 'markup',
      '#markup' => '<div class="response_data"></div>',
      '#weight' => '100',
    ];

    return $form;
  }

  /**
  * Load the index and display as a table
  */
  public function getIndex(array $form, FormStateInterface $form_state) {
    $endpoint = $form_state->getValue('index_endpoint');

    $json_data = \Drupal::service('ewp_institutions_get.fetch')->load('index', $endpoint);

    if ($json_data) {
      $title = $this->t('Index');
      $data = \Drupal::service('ewp_institutions_get.json')->toArray($json_data);
      $columns = ['label'];
      $show_attr = FALSE;
      $processed = \Drupal::service('ewp_institutions_get.format')->toTable($title, $data, $columns, $show_attr);
      $message = $processed;
    } else {
      $message = $this->t('Nothing to display.');
    }

    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(
      new HtmlCommand('.response_data', $message));
    return $ajax_response;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $endpoint = $form_state->getValue('index_endpoint');

    if ($endpoint) {
      // Initialize an HTTP client
      $client = \Drupal::httpClient();
      $status = NULL;

      // Build the HTTP request
      try {
        $request = $client->get($endpoint);
        $status = $request->getStatusCode();
      } catch (GuzzleException $e) {
        $status = $e->getResponse()->getStatusCode();
      } catch (Exception $e) {
        watchdog_exception('ewp_institutions_get', $e->getMessage());
      }

      if ($status != '200') {
        $form_state->setErrorByName('index_endpoint', $this->t('The given endpoint is invalid.'));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ewp_institutions_get.settings');
    $endpoint = $form_state->getValue('index_endpoint');
    $config->set('ewp_institutions_get.index_endpoint', $endpoint);
    $config->save();

    $refresh = $form_state->getValue('refresh');

    if ($refresh && !empty($endpoint)) {
      $json_data = \Drupal::service('ewp_institutions_get.fetch')->load('index', $endpoint, TRUE);
    }

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ewp_institutions_get.settings',
    ];
  }

}
