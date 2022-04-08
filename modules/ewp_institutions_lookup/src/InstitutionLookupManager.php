<?php

namespace Drupal\ewp_institutions_lookup;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ewp_institutions_get\DataFormatter;
use Drupal\ewp_institutions_get\InstitutionManager;
use Drupal\ewp_institutions_get\JsonDataFetcher;
use Drupal\ewp_institutions_get\JsonDataProcessor;

/**
* Institution lookup service.
*/
class InstitutionLookupManager {

  use DependencySerializationTrait;
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

  /**
   * Add lookup functionality to the API index key form element.
   *
   * @param array $form
   * @param Drupal\Core\Form\FormStateInterface $form_state
   */
  public function heiFormAlter(&$form, FormStateInterface $form_state) {
    $base_field = InstitutionManager::INDEX_FIELD;

    // If the base field is in the Institution form, changes may be needed,
    if (\array_key_exists($base_field, $form)) {
      // When no default value is set, provide the option to look it up.
      if (empty($form[$base_field]['widget'][0]['value']['#default_value'])) {
        $prefix = '<div id="lookup-target">';
        $suffix = '</div>';
        $form[$base_field]['widget'][0]['value']['#prefix'] = $prefix;
        $form[$base_field]['widget'][0]['value']['#suffix'] = $suffix;

        $form[$base_field]['widget'][0]['lookup'] = [
          '#type' => 'button',
          '#value' => $this->t('Lookup'),
          '#attributes' => ['style' => "margin: 0"],
          '#states' => [
            'enabled' => [
              ':input[name="' . $base_field . '[0][value]"]' => ['value' => ''],
            ],
          ],
          '#ajax' => [
            'callback' => [$this, 'lookupCallback'],
            'wrapper' => 'lookup-target',
          ],
        ];
      }
    }
  }

  /**
  * Lookup callback.
  */
  public function lookupCallback(array $form, FormStateInterface $form_state) {
    $base_field = InstitutionManager::INDEX_FIELD;

    $hei_id = $form_state->getValue('hei_id')[0]['value'];

    $lookup = $this->lookup($hei_id);

    $form[$base_field]['widget'][0]['value']['#value'] = $lookup[$hei_id];

    return $form[$base_field]['widget'][0]['value'];
  }

}
