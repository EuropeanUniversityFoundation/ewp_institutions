<?php

namespace Drupal\ewp_institutions_get;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Service for checking Institution import requirements
 */
class RequirementsCheck {

  use StringTranslationTrait;

  const INDEX_KEYWORD = 'index';
  const INDEX_LINK_KEY = 'list';

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Index endpoint
   *
   * @var string
   */
  protected $indexEndpoint;

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
   * @param \Drupal\ewp_institutions_get\JsonDataFetcher $json_data_fetcher
   *   JSON data fetching service.
   * @param \Drupal\ewp_institutions_get\JsonDataProcessor $json_data_processor
   *   JSON data processing service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      JsonDataFetcher $json_data_fetcher,
      JsonDataProcessor $json_data_processor,
      TranslationInterface $string_translation
  ) {
    $this->configFactory      = $config_factory;
    $this->jsonDataFetcher    = $json_data_fetcher;
    $this->jsonDataProcessor  = $json_data_processor;
    $this->stringTranslation  = $string_translation;

    $this->indexEndpoint = $this->configFactory
      ->get('ewp_institutions_get.settings')
      ->get('ewp_institutions_get.index_endpoint');
  }

  /**
   * Check for errors based on the provided arguments
   *
   * @param string $index_key
   *   Key found in the API Index.
   * @param string $hei_key
   *   Key found in the HEI list.
   *
   * @return string|NULL
   *   The error message if any error is detected
   */
  public function checkErrors($index_key = NULL, $hei_key = NULL) {
    // Check for the API index endpoint
    if (empty($this->indexEndpoint)) {
      return $this->t("Index endpoint is not defined.");
    }

    $index_data = $this->jsonDataFetcher
      ->load(self::INDEX_KEYWORD, $this->indexEndpoint);

    // Check for the actual index data
    if (! $index_data) {
      return $this->t("No available data.");
    }

    $index_links = $this->jsonDataProcessor
      ->idLinks($index_data, self::INDEX_LINK_KEY);
    $index_labels = $this->jsonDataProcessor
      ->idLabel($index_data);

    // Check for an index item matching the index key provided
    if (! array_key_exists($index_key, $index_links)) {
      return $this->t("Invalid index key: @index_key", [
        '@index_key' => $index_key
      ]);
    }

    // SUCCESS! First argument is validated

    $item_endpoint = $index_links[$index_key];

    // Check for the API endpoint for this index item
    if (empty($item_endpoint)) {
      return $this->t("Item endpoint is not defined.");
    }

    // Load the data for this index item
    $item_data = $this->jsonDataFetcher
      ->getUpdated($index_key, $item_endpoint);

    // Check for the actual index item data
    if (! $item_data) {
      return $this->t("No available data for @index_item", [
        '@index_item' => $index_labels[$index_key]
      ]);
    }

    $hei_list = $this->jsonDataProcessor
      ->idLabel($item_data);

    // Check for an institution matching the key provided
    if (! array_key_exists($hei_key, $hei_list)) {
      return $this->t("Invalid institution key: @hei_key", [
        '@hei_key' => $hei_key
      ]);
    }

    // SUCCESS! Second argument is validated

    return NULL;
  }

}
