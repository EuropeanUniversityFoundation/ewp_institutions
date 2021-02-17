<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\ewp_institutions_get\Form\PreLoadForm;

class PreviewForm extends PreLoadForm {

  /**
   * JSON data
   *
   * @var string
   */
  protected $json_data;

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
        $options += \Drupal::service('ewp_institutions_get.json')->idLabel($response);
      }
    }

    $form['hei_select']['#options'] = $options;
    return $form['hei_select'];
  }

  /**
  * Extract a single Institution from the JSON data
  */
  public function previewInstitution(array $form, FormStateInterface $form_state) {
    $message = 'YEAH!';

    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(
      new HtmlCommand('.response_data', $message));
    return $ajax_response;

  }

}
