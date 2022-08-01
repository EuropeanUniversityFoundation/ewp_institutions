<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ewp_institutions_get\DataFormatter;
use Drupal\ewp_institutions_get\JsonDataFetcher;
use Drupal\ewp_institutions_get\JsonDataProcessor;
use Drupal\ewp_institutions_get\InstitutionManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * HTTP Client for API calls.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
  * Data formatting service.
  *
  * @var \Drupal\ewp_institutions_get\DataFormatter
  */
  protected $dataFormatter;

  /**
  * JSON data fetching service.
  *
  * @var \Drupal\ewp_institutions_get\JsonDataFetcher
  */
  protected $jsonDataFetcher;

  /**
  * JSON data processing service.
  *
  * @var \Drupal\ewp_institutions_get\JsonDataProcessor
  */
  protected $jsonDataProcessor;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\Client $http_client
   *   HTTP Client for API calls.
   * @param \Drupal\ewp_institutions_get\DataFormatter $data_formatter
   *   Data formatting service.
   * @param \Drupal\ewp_institutions_get\JsonDataFetcher $json_data_fetcher
   *   JSON data fetching service.
   * @param \Drupal\ewp_institutions_get\JsonDataProcessor $json_data_processor
   *   JSON data processing service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    Client $http_client,
    DataFormatter $data_formatter,
    JsonDataFetcher $json_data_fetcher,
    JsonDataProcessor $json_data_processor,
    TranslationInterface $string_translation
  ) {
    parent::__construct($config_factory);
    $this->httpClient        = $http_client;
    $this->dataFormatter     = $data_formatter;
    $this->jsonDataFetcher   = $json_data_fetcher;
    $this->jsonDataProcessor = $json_data_processor;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('ewp_institutions_get.format'),
      $container->get('ewp_institutions_get.fetch'),
      $container->get('ewp_institutions_get.json'),
      $container->get('string_translation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_get_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ewp_institutions_get.settings',
    ];
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
      '#default_value' => $config->get('index_endpoint'),
      '#description' => $this
        ->t('External API endpoint that returns the main index.'),
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

    $json_data = $this->jsonDataFetcher
      ->load(InstitutionManager::INDEX_KEYWORD, $endpoint);

    if ($json_data) {
      $title = $this->t('Index');
      $data = $this->jsonDataProcessor->toArray($json_data);
      $columns = ['label'];
      $show_attr = FALSE;

      $message = $this->dataFormatter
        ->toTable($title, $data, $columns, $show_attr);
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
      $status = NULL;
      // Build the HTTP request
      try {
        $request = $this->httpClient->get($endpoint);
        $status = $request->getStatusCode();
      } catch (GuzzleException $e) {
        $status = $e->getResponse()->getStatusCode();
      } catch (Exception $e) {
        watchdog_exception('ewp_institutions_get', $e->getMessage());
      }

      if ($status != '200') {
        $form_state->setErrorByName('index_endpoint', $this->t(
          'The given endpoint is invalid.'
        ));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ewp_institutions_get.settings');

    $config->set('index_endpoint', $form_state->getValue('index_endpoint'));

    $config->save();

    $refresh = $form_state->getValue('refresh');

    if ($refresh && !empty($endpoint)) {
      $json_data = $this->jsonDataFetcher
        ->load(InstitutionManager::INDEX_KEYWORD, $endpoint, TRUE);
    }

    return parent::submitForm($form, $form_state);
  }

}
