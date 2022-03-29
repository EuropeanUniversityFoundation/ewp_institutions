<?php

namespace Drupal\ewp_institutions_lookup;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ewp_institutions_get\DataFormatter;
use Drupal\ewp_institutions_get\InstitutionManager;
use Drupal\ewp_institutions_get\JsonDataFetcher;
use Drupal\ewp_institutions_get\JsonDataProcessor;

/**
* Institution lookup service.
*/
class InstitutionLookupManager {

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
   * Institution entity manager.
   *
   * @var \Drupal\ewp_institutions_get\InstitutionManager
   */
  protected $heiManager;

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
   * @param \Drupal\ewp_institutions_get\DataFormatter $data_formatter
   *   Data formatting service.
   * @param \Drupal\ewp_institutions_get\InstitutionManager $hei_manager
   *   Data formatting service.
   * @param \Drupal\ewp_institutions_get\JsonDataFetcher $json_data_fetcher
   *   JSON data fetching service.
   * @param \Drupal\ewp_institutions_get\JsonDataProcessor $json_data_processor
   *   JSON data processing service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    DataFormatter $data_formatter,
    InstitutionManager $hei_manager,
    JsonDataFetcher $json_data_fetcher,
    JsonDataProcessor $json_data_processor
  ) {
    $this->configFactory     = $config_factory;
    $this->dataFormatter     = $data_formatter;
    $this->heiManager        = $hei_manager;
    $this->jsonDataFetcher   = $json_data_fetcher;
    $this->jsonDataProcessor = $json_data_processor;
  }

  /**
   * Look up an Institution ID in a remote lookup index.
   *
   * @param string $hei_id
   *   The Institution ID.
   *
   * @return array $result
   *   An array of Index key (i.e. country code) keyed by Institution ID.
   */
  public function lookup($hei_id) {
    $config = $this->configFactory->get('ewp_institutions_lookup.settings');
    $endpoint = $config->get('lookup_endpoint');

    $json_data = $this->jsonDataFetcher->load('lookup', $endpoint);
    $data = $this->jsonDataProcessor->toArray($json_data);

    $result = [];

    foreach ($data as $idx => $item) {
      if ($item['id'] === $hei_id) {
        $result[$hei_id] = $item['attributes']['country'];
      }
    }

    return $result;
  }

}
