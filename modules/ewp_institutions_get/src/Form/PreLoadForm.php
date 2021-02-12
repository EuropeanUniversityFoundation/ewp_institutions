<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

class PreLoadForm extends FormBase {

  /**
   * Index endpoint
   *
   * @var string
   */
  protected $index_endpoint;

  /**
   * API index
   *
   * @var array
   */
  protected $api_index;

  /**
   * API index item list
   *
   * @var array
   */
  protected $index_items;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    // Load the settings.
    $config = \Drupal::config('ewp_institutions_get.settings');
    $this->index_endpoint = $config->get('ewp_institutions_get.index_endpoint');
    $this->api_index = [];
    $this->index_items = [];

    if (! empty($this->index_endpoint)) {
      // Initialize an HTTP client
      $client = \Drupal::httpClient();
      $response = NULL;

      // Build the HTTP request
      try {
        $request = $client->get($this->index_endpoint);
        $response = $request->getBody();
      } catch (GuzzleException $e) {
        $response = $e->getResponse()->getBody();
      } catch (Exception $e) {
        watchdog_exception('ewp_institutions_get', $e->getMessage());
      }

      // Validate the response
      $validated = \Drupal::service('ewp_institutions_get.json')->validate($response);

      // Build the index and the item list
      if ($validated) {
        $this->api_index = \Drupal::service('ewp_institutions_get.json')->idLinks($response);
        $this->index_items = \Drupal::service('ewp_institutions_get.json')->idLabel($response);
      }
    } else {
      $warning = $this->t("Index endpoint is not defined.");
      \Drupal::service('messenger')->addWarning($warning);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_get_preload_form';
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
      '#weight' => '-9',
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => '-7',
    ];

    $form['actions']['get'] = [
      '#type' => 'button',
      '#value' => $this->t('GET Institutions'),
      '#attributes' => [
        'class' => [
          'button--primary',
        ]
      ],
      '#states' => [
        'disabled' => [
          ':input[name="index_select"]' => ['value' => ''],
        ],
      ],
      '#ajax' => [
        'callback' => '::getInstitutions',
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
  public function getInstitutions(array $form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');
    $endpoint = $this->api_index[$index_item];

    // Initialize an HTTP client
    $client = \Drupal::httpClient();
    $response = NULL;

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
        $processed = \Drupal::service('ewp_institutions_get.json')->toTable($response);
        $message = $processed;
      } else {
        $message = $this->t('Nothing to display.');
      }
    } else {
      $message = $this->t('This endpoint is not defined.');
    }

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

}
