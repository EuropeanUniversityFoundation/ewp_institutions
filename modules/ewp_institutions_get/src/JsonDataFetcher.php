<?php

namespace Drupal\ewp_institutions_get;

use Drupal\Core\TempStore\SharedTempStoreFactory;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service for JSON data fetching
 */
class JsonDataFetcher {

  /**
   * Stores the tempstore factory.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a new JsonDataFetcher.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(SharedTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * Load JSON:API data from tempstore or external API
   */
  public function load($temp_store_key, $endpoint, $refresh = FALSE) {
    // Get the tempstore
    $store = $this->tempStoreFactory->get('ewp_institutions_get');

    // If tempstore is empty OR should be refreshed
    if (empty($store->get($temp_store_key)) || $refresh) {
      // Get the data from the provided endpoint and store it
      $store->set($temp_store_key, $this->get($endpoint));
      $message = t("Loaded @key into temporary storage", [
        '@key' => $temp_store_key
      ]);
      \Drupal::logger('ewp_institutions_get')->notice($message);
    }

    // Return whatever is in storage
    return $store->get($temp_store_key);
  }

  /**
   * Get JSON:API data from an external API endpoint
   */
  public function get($endpoint) {
    // Prepare the JSON string
    $json_data = '';

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

    // Validate the response
    $validated = \Drupal::service('ewp_institutions_get.json')
      ->validate($response);

    if ($validated) {
      // Extract the data from the Guzzle Stream
      $decoded = json_decode($response, TRUE);
      // Encode the data for persistency
      $json_data = json_encode($decoded);
    }

    // Return the data
    return $json_data;
  }

  /**
   * Check the tempstore for the updated date
   */
  public function checkUpdated($temp_store_key) {
    // Get the tempstore
    $store = $this->tempStoreFactory->get('ewp_institutions_get');

    if (!empty($store->get($temp_store_key))) {
      $updated = $store->getMetadata($temp_store_key)->updated;
    } else {
      $updated = NULL;
    }

    return $updated;
  }

  /**
   * Get the updated value from an endpoint
   */
  public function getUpdated($temp_store_key, $endpoint) {
    if ($temp_store_key != 'index') {
      // Check when the index was last updated
      $index_updated = $this->checkUpdated('index');
      $message = t('Index was updated at @timestamp', [
        '@timestamp' => $index_updated
      ]);
      \Drupal::logger('ewp_institutions_get')->notice($message);
    }

    // Check when this item was last updated
    $item_updated = $this->checkUpdated($temp_store_key);
    $message = t('Item @key was updated at @timestamp', [
      '@key' => $temp_store_key, '@timestamp' => $item_updated
    ]);
    \Drupal::logger('ewp_institutions_get')->notice($message);

    $refresh = ($item_updated && $index_updated < $item_updated) ? FALSE : TRUE;

    $json_data = $this->load($temp_store_key, $endpoint, $refresh);

    return $json_data;
  }


}
