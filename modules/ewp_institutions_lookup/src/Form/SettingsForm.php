<?php

namespace Drupal\ewp_institutions_lookup\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Utility\Error;
use Drupal\ewp_institutions_get\DataFormatter;
use Drupal\ewp_institutions_get\JsonDataFetcher;
use Drupal\ewp_institutions_get\JsonDataProcessor;
use Drupal\ewp_institutions_lookup\InstitutionLookupManager;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings for Institutions Lookup.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * Data formatting service.
   *
   * @var \Drupal\ewp_institutions_get\DataFormatter
   */
  protected $dataFormatter;

  /**
   * Guzzle\Client instance.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\ewp_institutions_get\JsonDataFetcher $json_data_fetcher
   *   JSON data fetching service.
   * @param \Drupal\ewp_institutions_get\JsonDataProcessor $json_data_processor
   *   JSON data processing service.
   * @param \Drupal\ewp_institutions_get\DataFormatter $data_formatter
   *   Data formatting service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Guzzle\Client instance.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    JsonDataFetcher $json_data_fetcher,
    JsonDataProcessor $json_data_processor,
    DataFormatter $data_formatter,
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->jsonDataFetcher   = $json_data_fetcher;
    $this->jsonDataProcessor = $json_data_processor;
    $this->dataFormatter     = $data_formatter;
    $this->httpClient        = $http_client;
    $this->logger            = $logger_factory->get('ewp_institutions_get');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('ewp_institutions_get.fetch'),
      $container->get('ewp_institutions_get.json'),
      $container->get('ewp_institutions_get.format'),
      $container->get('http_client'),
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_lookup_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('ewp_institutions_lookup.settings');

    // Index endpoint field.
    $form['lookup_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lookup endpoint'),
      '#default_value' => $config->get('lookup_endpoint'),
      '#description' => $this
        ->t('External API endpoint that returns the lookup index.'),
    ];

    $form['refresh'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Refresh temporary storage on Save'),
      '#default_value' => FALSE,
      '#return_value' => TRUE,
    ];

    $form['actions']['get'] = [
      '#type' => 'button',
      '#value' => $this->t('GET API Lookup Index'),
      '#states' => [
        'disabled' => [
          ':input[name="lookup_endpoint"]' => ['value' => ''],
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
   * Load the index and display as a table.
   */
  public function getIndex(array $form, FormStateInterface $form_state) {
    $endpoint = $form_state->getValue('lookup_endpoint');

    $json_data = $this->jsonDataFetcher
      ->load(InstitutionLookupManager::TEMPSTORE, $endpoint);

    if ($json_data) {
      $title = $this->t('Lookup index');
      $data = $this->jsonDataProcessor->toArray($json_data);
      $columns = [InstitutionLookupManager::LABEL_KEY];
      $show_attr = TRUE;

      $message = $this->dataFormatter
        ->toTable($title, $data, $columns, $show_attr);
    }
    else {
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
    $endpoint = $form_state->getValue('lookup_endpoint');

    if ($endpoint) {
      $status = NULL;

      // Build the HTTP request.
      try {
        $request = $this->httpClient->request('GET', $endpoint);
        $status = $request->getStatusCode();
      }
      catch (ClientException $e) {
        $status = $e->getResponse()->getStatusCode();
      }
      catch (\Exception $e) {
        Error::logException($this->logger, $e);
      }

      if ($status != '200') {
        $form_state->setErrorByName(
          'lookup_endpoint',
          $this->t('The given endpoint is invalid.')
        );
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ewp_institutions_lookup.settings');
    $endpoint = $form_state->getValue('lookup_endpoint');
    $config->set('lookup_endpoint', $endpoint);
    $config->save();

    $refresh = $form_state->getValue('refresh');

    if ($refresh && !empty($endpoint)) {
      $this->jsonDataFetcher->load('lookup', $endpoint, TRUE);
    }

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ewp_institutions_lookup.settings',
    ];
  }

}
