<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ewp_institutions_get\DataFormatter;
use Drupal\ewp_institutions_get\JsonDataFetcher;
use Drupal\ewp_institutions_get\JsonDataProcessor;
use Drupal\ewp_institutions_get\InstitutionManager;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class PreLoadForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Index endpoint.
   *
   * @var string
   */
  protected $indexEndpoint;

  /**
   * Index item links.
   *
   * @var array
   */
  protected $indexLinks;

  /**
   * Index item labels.
   *
   * @var array
   */
  protected $indexLabels;

  /**
   * HTTP Client for API calls.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   HTTP Client for API calls.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\ewp_institutions_get\DataFormatter $data_formatter
   *   Data formatting service.
   * @param \Drupal\ewp_institutions_get\JsonDataFetcher $json_data_fetcher
   *   JSON data fetching service.
   * @param \Drupal\ewp_institutions_get\JsonDataProcessor $json_data_processor
   *   JSON data processing service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    Client $http_client,
    ConfigFactoryInterface $config_factory,
    DataFormatter $data_formatter,
    JsonDataFetcher $json_data_fetcher,
    JsonDataProcessor $json_data_processor,
    MessengerInterface $messenger,
    TranslationInterface $string_translation,
  ) {
    $this->httpClient        = $http_client;
    $this->configFactory     = $config_factory;
    $this->dataFormatter     = $data_formatter;
    $this->jsonDataFetcher   = $json_data_fetcher;
    $this->jsonDataProcessor = $json_data_processor;
    $this->messenger         = $messenger;
    $this->stringTranslation = $string_translation;

    // Load the settings.
    $config = $this->configFactory->get('ewp_institutions_get.settings');
    $this->indexEndpoint = $config->get('index_endpoint');
    $this->indexLinks = [];
    $this->indexLabels = [];

    if (!empty($this->indexEndpoint)) {
      $json_data = $this->jsonDataFetcher
        ->getUpdated(InstitutionManager::INDEX_KEYWORD, $this->indexEndpoint);

      if ($json_data) {
        $this->indexLinks = $this->jsonDataProcessor
          ->idLinks($json_data, InstitutionManager::INDEX_LINK_KEY);
        $this->indexLabels = $this->jsonDataProcessor
          ->idLabel($json_data);
      }
    }
    else {
      $warning = $this->t("Index endpoint is not defined.");
      $this->messenger->addWarning($warning);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('ewp_institutions_get.format'),
      $container->get('ewp_institutions_get.fetch'),
      $container->get('ewp_institutions_get.json'),
      $container->get('messenger'),
      $container->get('string_translation'),
    );
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
      '#options' => $this->indexLabels,
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
        ],
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
   * Fetch the data and display as a table.
   */
  public function getInstitutions(array $form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');
    $endpoint = $this->indexLinks[$index_item];

    if (!empty($endpoint)) {
      $json_data = $this->jsonDataFetcher->getUpdated($index_item, $endpoint);

      if ($json_data) {
        $title = $this->indexLabels[$index_item];
        $data = $this->jsonDataProcessor->toArray($json_data);

        $message = $this->dataFormatter->toTable($title, $data, [], TRUE);
      }
      else {
        $message = $this->t('Nothing to display.');
      }
    }
    else {
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
