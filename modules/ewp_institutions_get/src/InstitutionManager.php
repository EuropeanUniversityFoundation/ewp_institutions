<?php

namespace Drupal\ewp_institutions_get;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ewp_institutions\Entity\InstitutionEntity;

/**
 * Service for managing Institution entities
 */
class InstitutionManager {

  use StringTranslationTrait;

  const ENTITY_TYPE = 'hei';
  const UNIQUE_FIELD = 'hei_id';
  const INDEX_FIELD = 'index_key';
  const INDEX_KEYWORD = 'index';
  const INDEX_LINK_KEY = 'list';

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Field mapping
   *
   * @var array
   */
  protected $fieldmap;

  /**
   * Index endpoint
   *
   * @var string
   */
  protected $indexEndpoint;

  /**
   * Data for target Institution
   *
   * @var array
   */
  protected $heiItemData;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\ewp_institutions_get\JsonDataFetcher $json_data_fetcher
   *   JSON data fetching service.
   * @param \Drupal\ewp_institutions_get\JsonDataProcessor $json_data_processor
   *   JSON data processing service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      EntityTypeManagerInterface $entity_type_manager,
      JsonDataFetcher $json_data_fetcher,
      JsonDataProcessor $json_data_processor,
      LoggerChannelFactoryInterface $logger_factory,
      TranslationInterface $string_translation
  ) {
    $this->configFactory      = $config_factory;
    $this->entityTypeManager  = $entity_type_manager;
    $this->jsonDataFetcher    = $json_data_fetcher;
    $this->jsonDataProcessor  = $json_data_processor;
    $this->logger             = $logger_factory->get('ewp_institutions_get');
    $this->stringTranslation  = $string_translation;

    $this->indexEndpoint = $this->configFactory
      ->get('ewp_institutions_get.settings')
      ->get('ewp_institutions_get.index_endpoint');

    $this->fieldmap = $this->configFactory
      ->get('ewp_institutions_get.fieldmap')
      ->get('field_mapping');

    $this->heiItemData = [];
  }

  /**
   * Get the ID of an Institution entity;
   *   optionally, create a new entity from an index key
   *
   * @param string $hei_id
   *   Unique Institution identifier.
   * @param string $create_from
   *   Key found in the API Index.
   *
   * @return array $ids
   *   An array of entity IDs found in the system
   */
  public function getInstitution($hei_id, $create_from = NULL) {
    // Check if an entity with the same hei_id already exists
    $exists = $this->entityTypeManager
      ->getStorage(self::ENTITY_TYPE)
      ->loadByProperties([self::UNIQUE_FIELD => $hei_id]);

    if (empty($exists) && !empty($create_from)) {
      $new = $this->createInstitution($create_from, $hei_id);
      if (!empty($new)) {
        $exists = $this->entityTypeManager
          ->getStorage(self::ENTITY_TYPE)
          ->loadByProperties([self::UNIQUE_FIELD => $hei_id]);
      }
    }

    foreach ($exists as $key => $value) {
      $log_var = $key;
    }
    $message = $this->t('getInstitution returns @id', ['@id' => $log_var]);
    $this->logger->notice($message);

    return $exists;
  }

  /**
   * Create a new Institution entity
   *
   * @param string $index_key
   *   Key found in the API Index.
   * @param string $hei_key
   *   Key found in the HEI list.
   *
   * @return int|NULL
   *   The ID of the new Institution entity
   */
  public function createInstitution($index_key, $hei_key) {
    if (!empty($this->checkErrors($index_key, $hei_key))) {
      return NULL;
    }

    $index_data = $this->jsonDataFetcher
      ->load(self::INDEX_KEYWORD, $this->indexEndpoint);

    $index_links = $this->jsonDataProcessor
      ->idLinks($index_data, self::INDEX_LINK_KEY);

    $hei_data = $this->jsonDataFetcher
      ->getUpdated($index_key, $index_links[$index_key]);

    $this->heiItemData = $this->jsonDataProcessor
      ->extract($hei_data, $hei_key);

    // Remove empty values from the fieldmap
    foreach ($this->fieldmap as $key => $value) {
      if (empty($this->fieldmap[$key])) {
        unset($this->fieldmap[$key]);
      }
    }

    // Remove non mapped values from the entity data
    foreach ($this->heiItemData as $key => $value) {
      if (! array_key_exists($key, $this->fieldmap)) {
        unset($this->heiItemData[$key]);
      }
    }

    // Create an array with the new data
    $new_data = [];
    foreach ($this->heiItemData as $key => $value) {
      $new_data[$this->fieldmap[$key]] = $value;
    }

    // Add the Index key to the new data
    $new_data[self::INDEX_FIELD] = $index_key;

    $new_entity = $this->entityTypeManager
      ->getStorage(self::ENTITY_TYPE)
      ->create($new_data);
    $new_entity->save();

    $created = $this->entityTypeManager
      ->getStorage(self::ENTITY_TYPE)
      ->loadByProperties([self::UNIQUE_FIELD => $hei_key]);

    foreach ($created as $key => $value) {
      $id = $key;
    }

    $message = $this->t('createInstitution returns @id', ['@id' => $id]);
    $this->logger->notice($message);

    return $created;
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