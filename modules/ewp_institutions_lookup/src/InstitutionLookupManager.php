<?php

namespace Drupal\ewp_institutions_lookup;

use Drupal\ewp_institutions_get\DataFormatter;
use Drupal\ewp_institutions_get\JsonDataFetcher;
use Drupal\ewp_institutions_get\JsonDataProcessor;

/**
* Institution lookup service.
*/
class InstitutionLookupManager {

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
   * @param \Drupal\ewp_institutions_get\DataFormatter $data_formatter
   *   Data formatting service.
   * @param \Drupal\ewp_institutions_get\JsonDataFetcher $json_data_fetcher
   *   JSON data fetching service.
   * @param \Drupal\ewp_institutions_get\JsonDataProcessor $json_data_processor
   *   JSON data processing service.
   */
  public function __construct(
    DataFormatter $data_formatter,
    JsonDataFetcher $json_data_fetcher,
    JsonDataProcessor $json_data_processor
  ) {
    $this->dataFormatter     = $data_formatter;
    $this->jsonDataFetcher   = $json_data_fetcher;
    $this->jsonDataProcessor = $json_data_processor;
  }

  /**
   * Looks up an Institution ID across different sources.
   *
   * @param string $hei_id
   *   The Institution ID.
   */
  public function lookup($hei_id) {
    // code...
  }

  /**
   * Looks up an Institution ID within the system.
   *
   * @param string $hei_id
   *   The Institution ID.
   */
  public function lookupEntity($hei_id) {
    // code...
  }

  /**
   * Looks up an Institution ID in a remote lookup index.
   *
   * @param string $hei_id
   *   The Institution ID.
   */
  public function lookupRemote($hei_id) {
    // code...
  }

}
