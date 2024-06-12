<?php

namespace Drupal\ewp_institutions_get;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * JSON data processing service.
 */
class JsonDataProcessor {

  use StringTranslationTrait;

  // JSON data keys
  const DATA_KEY = 'data';
  const TYPE_KEY = 'type';
  const ID_KEY = 'id';
  const ATTR_KEY = 'attributes';
  const TITLE_KEY = 'title';
  const LABEL_KEY = 'label';
  const LINKS_KEY = 'links';
  const HREF_KEY = 'href';
  // Drupal array keys
  const VALUE_KEY = 'value';

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new JsonDataProcessor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    TranslationInterface $string_translation
  ) {
    $this->logger = $logger_factory->get('ewp_institutions_get');
  }

  /**
   * Extract attributes for a single key in JSON data.
   */
  public function extract($json, $target_key) {
    $decoded = json_decode($json, TRUE);

    $data = $decoded[self::DATA_KEY];

    $target_data = [];

    foreach ($data as $key => $array) {
      if ($array[self::ID_KEY] == $target_key) {
        // Get expanded data array
        $expanded_data = $this->toArray($json, TRUE);
        $target_data = $expanded_data[$key][self::ATTR_KEY];
        ksort($target_data);
      }
    }

    return $target_data;
  }

  /**
   * Create an array of id => attributes[self::LABEL_KEY] or similar.
   */
  public function idLabel($json) {
    $decoded = json_decode($json, TRUE);

    $data = $decoded[self::DATA_KEY];

    $index = [];

    foreach ($data as $fields) {
      if (array_key_exists(self::ATTR_KEY, $fields)) {
        if (array_key_exists(self::LABEL_KEY, $fields[self::ATTR_KEY])) {
          // the expectation is to find an entity label
          $index[$fields[self::ID_KEY]] = $fields[self::ATTR_KEY][self::LABEL_KEY];
        }
        elseif (array_key_exists(self::TITLE_KEY, $fields[self::ATTR_KEY])) {
          // alternatively one might find a node title instead
          $index[$fields[self::ID_KEY]] = $fields[self::ATTR_KEY][self::TITLE_KEY];
        }
        else {
          // when none of these attributes can be found, use the ID itself
          $index[$fields[self::ID_KEY]] = $fields[self::ID_KEY];
        }
      }
      else {
        // when no attribute object can be found, use the ID itself
        $index[$fields[self::ID_KEY]] = $fields[self::ID_KEY];
      }
    }

    // Sort by label for improved usability
    \natcasesort($index);
    return $index;
  }

  /**
   * Create an array of id => links.
   */
  public function idLinks($json, $link_key) {
    $decoded = json_decode($json, TRUE);

    $data = $decoded[self::DATA_KEY];

    $index = [];

    foreach ($data as $fields) {
      if (array_key_exists(self::LINKS_KEY, $fields) && array_key_exists($link_key, $fields[self::LINKS_KEY])) {
        if (array_key_exists(self::HREF_KEY, $fields[self::LINKS_KEY][$link_key])) {
          // When the link key points to an object
          $index[$fields[self::ID_KEY]] = $fields[self::LINKS_KEY][$link_key][self::HREF_KEY];
        }
        else {
          // When the link key points to the URL
          $index[$fields[self::ID_KEY]] = $fields[self::LINKS_KEY][$link_key];
        }
      }
      else {
        // when no link can be found, leave it empty
        $index[$fields[self::ID_KEY]] = '';
      }
    }

    return $index;
  }

  /**
   * Convert JSON:API data to array.
   */
  public function toArray($json, $expand = FALSE) {
    $decoded = json_decode($json, TRUE);

    $data = $decoded[self::DATA_KEY];

    if ($expand) {
      // Iterate over the index items
      foreach ($data as $index => $data_array) {
        // Target the attributes array
        if (array_key_exists(self::ATTR_KEY, $data_array)) {
          foreach ($data_array[self::ATTR_KEY] as $attr => $value) {
            if (!empty($value)) {
              // Treat simple values as indexed arrays with a key - value pair
              if (!is_array($value)) {
                $data[$index][self::ATTR_KEY][$attr] = [
                  [self::VALUE_KEY => $value],
                ];
              }
              // Encapsulate associative arrays in indexed arrays
              elseif (count(array_filter(array_keys($value), 'is_string')) > 0) {
                $data[$index][self::ATTR_KEY][$attr] = [$value];
              }
            }
          }
        }
      }
    }

    return $data;
  }

  /**
   * Validate JSON:API data object.
   */
  public function validate($json) {
    $decoded = json_decode($json, TRUE);
    $message = '';
    $status = TRUE;

    if ($decoded && array_key_exists(self::DATA_KEY, $decoded)) {
      foreach ($decoded[self::DATA_KEY] as $value) {
        if (!array_key_exists(self::TYPE_KEY, $value)) {
          $message = $this->t('Type key is not present.');
        }
        elseif (!array_key_exists(self::ID_KEY, $value)) {
          $message = $this->t('ID key is not present.');
        }
      }
    }
    else {
      $message = $this->t('Data key is not present.');
    }

    if ($message) {
      $this->logger->notice($message);
      $status = FALSE;
    }

    return $status;
  }

}
