<?php

namespace Drupal\ewp_institutions_lookup;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ewp_institutions_get\DataFormatter;
use Drupal\ewp_institutions_get\JsonDataFetcher;
use Drupal\ewp_institutions_get\JsonDataProcessor;

/**
* Institution lookup service.
*/
class InstitutionLookupManager {

  use StringTranslationTrait;

  const TEMPSTORE = 'lookup';

  const ID_KEY    = 'id';
  const ATTR_KEY  = 'attributes';
  const LABEL_KEY = 'label';
  const INDEX_KEY = 'country';

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
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\ewp_institutions_get\DataFormatter $data_formatter
   *   Data formatting service.
   * @param \Drupal\ewp_institutions_get\JsonDataFetcher $json_data_fetcher
   *   JSON data fetching service.
   * @param \Drupal\ewp_institutions_get\JsonDataProcessor $json_data_processor
   *   JSON data processing service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    DataFormatter $data_formatter,
    JsonDataFetcher $json_data_fetcher,
    JsonDataProcessor $json_data_processor
  ) {
    $this->configFactory     = $config_factory;
    $this->dataFormatter     = $data_formatter;
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
   *   An array containing the index key keyed by Institution ID.
   */
  public function lookup(string $hei_id): array {
    $data = $this->extract($hei_id);

    if (! empty($data)) {
      return [
        $data[self::ID_KEY] => $data[self::ATTR_KEY][self::INDEX_KEY]
      ];
    }

    return [];
  }

  /**
   * Extract data from a remote lookup index based on Institution ID.
   *
   * @param string $hei_id
   *   The Institution ID.
   *
   * @return array
   *   An array containing a data item from a remote lookup index.
   */
  public function extract(string $hei_id): array {
    $config = $this->configFactory->get('ewp_institutions_lookup.settings');
    $endpoint = $config->get('lookup_endpoint');

    $json_data = $this->jsonDataFetcher
      ->load(self::TEMPSTORE, $endpoint);
    $data = $this->jsonDataProcessor->toArray($json_data);

    foreach ($data as $idx => $item_data) {
      if ($item_data[self::ID_KEY] === $hei_id) {
        return $item_data;
      }
    }

    return [];
  }

  /**
   * Format data for preview.
   *
   * @param string $hei_id
   *   The Institution ID.
   *
   * @return array
   *   A render array with the preview of the data from the given Institution.
   */
  public function preview(string $hei_id): array {
    $data = $this->extract($hei_id);
    $attr = $data[InstitutionLookupManager::ATTR_KEY];
    $title = $attr[InstitutionLookupManager::LABEL_KEY];

    return $this->dataFormatter->preview($title, [$data], $hei_id, FALSE);
  }

  /**
   * Generate import link.
   *
   * @param string $hei_id
   *   The Institution ID.
   *
   * @return Drupal\Core\Link
   *   A Link object pointing to the import form route.
   */
  public function importLink(string $hei_id): Link {
    $lookup = $this->lookup($hei_id);
    $index_key = $lookup[$hei_id];

    $text = $this->t('Click here to import.');
    $route_name = 'entity.hei.import_form';
    $route_parameters = [
      'index_key' => $index_key,
      'hei_key' => $hei_id
    ];

    return Link::createFromRoute($text, $route_name, $route_parameters);
  }

}
