<?php

namespace Drupal\ewp_institutions_get;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Error;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

/**
 * JSON data fetching service.
 */
class JsonDataFetcher {

  use StringTranslationTrait;

  /**
   * HTTP Client for API calls.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * JSON data processing service.
   *
   * @var \Drupal\ewp_institutions_get\JsonDataProcessor
   */
  protected $jsonDataProcessor;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * An instance of the key/value store.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Constructs a new JsonDataFetcher.
   *
   * @param \GuzzleHttp\Client $http_client
   *   HTTP client.
   * @param \Drupal\ewp_institutions_get\JsonDataProcessor $json_data_processor
   *   JSON data processing service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    Client $http_client,
    JsonDataProcessor $json_data_processor,
    LoggerChannelFactoryInterface $logger_factory,
    SharedTempStoreFactory $temp_store_factory,
    TranslationInterface $string_translation,
  ) {
    $this->httpClient = $http_client;
    $this->jsonDataProcessor = $json_data_processor;
    $this->logger = $logger_factory->get('ewp_institutions_get');
    $this->tempStore = $temp_store_factory->get('ewp_institutions_get');
    $this->stringTranslation = $string_translation;
  }

  /**
   * Load JSON:API data from tempstore or external API.
   */
  public function load($temp_store_key, $endpoint, $refresh = FALSE) {
    // If tempstore is empty OR should be refreshed.
    if (empty($this->tempStore->get($temp_store_key)) || $refresh) {
      // Get the data from the provided endpoint and store it.
      $this->tempStore->set($temp_store_key, $this->get($endpoint));
      $message = $this->t("Loaded @key into temporary storage", [
        '@key' => $temp_store_key,
      ]);
      $this->logger->notice($message);
    }

    // Return whatever is in storage.
    return $this->tempStore->get($temp_store_key);
  }

  /**
   * Get JSON:API data from an external API endpoint.
   */
  public function get($endpoint) {
    // Prepare the JSON string.
    $json_data = '';

    $response = NULL;

    // Build the HTTP request.
    try {
      $request = $this->httpClient->get($endpoint);
      $response = $request->getBody();
    }
    catch (BadResponseException $e) {
      $response = $e->getResponse()->getBody();
    }
    catch (\Exception $e) {
      Error::logException($this->logger, $e);
    }

    if ($this->jsonDataProcessor->validate($response)) {
      // Extract the data from the Guzzle Stream.
      $decoded = json_decode($response, TRUE);
      // Encode the data for persistency.
      $json_data = json_encode($decoded);
    }

    // Return the data.
    return $json_data;
  }

  /**
   * Check the tempstore for the updated date.
   */
  public function checkUpdated($temp_store_key) {
    if (!empty($this->tempStore->get($temp_store_key))) {
      return $this->tempStore->getMetadata($temp_store_key)->getUpdated();
    }
    else {
      return NULL;
    }
  }

  /**
   * Get the updated value from an endpoint.
   */
  public function getUpdated($temp_store_key, $endpoint) {
    // Check when this item was last updated.
    $item_updated = $this->checkUpdated($temp_store_key);

    if ($temp_store_key != InstitutionManager::INDEX_KEYWORD) {
      // Check when the index was last updated.
      $index_updated = $this->checkUpdated(InstitutionManager::INDEX_KEYWORD);
    }
    else {
      // Assign for comparison.
      $index_updated = $item_updated;
    }

    $refresh = ($item_updated && $index_updated <= $item_updated) ? FALSE : TRUE;

    return $this->load($temp_store_key, $endpoint, $refresh);
  }

}
