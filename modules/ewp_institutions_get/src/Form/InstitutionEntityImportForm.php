<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\ewp_institutions\Form\InstitutionEntityForm;

/**
 * Changes the Institution Add form.
 *
 * @ingroup ewp_institutions
 */
class InstitutionEntityImportForm extends InstitutionEntityForm {

  /**
   * Index endpoint
   *
   * @var string
   */
  protected $indexEndpoint;

  /**
   * Index link key
   *
   * @var string
   */
  protected $indexLinkKey;

  /**
   * Index item links
   *
   * @var array
   */
  protected $indexLinks;

  /**
   * Index item labels
   *
   * @var array
   */
  protected $indexLabels;

  /**
   * Index key for Institution
   *
   * @var string
   */
  protected $indexKey;

  /**
   * Item key for Institution
   *
   * @var string
   */
  protected $institutionKey;

  /**
   * Data for Institution
   *
   * @var array
   */
  protected $institutionData;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hei_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $index_key = NULL, $hei_key = NULL) {
    /* @var \Drupal\ewp_institutions\Entity\InstitutionEntity $entity */
    $form['add_form'] = parent::buildForm($form, $form_state);

    // Build the form header
    $form['header'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Selected Institution to import'),
      '#weight' => '-100'
    ];

    $form['header']['messages'] = [
      '#type' => 'markup',
      '#markup' => '',
      '#weight' => '1',
    ];

    $form['data'] = [
      '#type' => 'details',
      '#title' => $this->t('Data'),
      '#weight' => '-90',
    ];

    $form['data']['preview'] = [
      '#type' => 'markup',
      '#markup' => '',
      '#weight' => '1',
    ];

    // Load the settings.
    $settings = \Drupal::config('ewp_institutions_get.settings');
    $this->indexEndpoint = $settings->get('ewp_institutions_get.index_endpoint');
    $this->indexLinkKey = 'list';
    $this->indexLinks = [];
    $this->indexLabels = [];
    $this->indexKey = NULL;
    $this->institutionKey = NULL;
    $this->institutionData = [];
    $error = NULL;

    // Check for the API index endpoint
    if (empty($this->indexEndpoint)) {
      $error = $this->t("Index endpoint is not defined.");
    } else {
      $index_data = \Drupal::service('ewp_institutions_get.fetch')
        ->load('index', $this->indexEndpoint);

      // Check for the actual index data
      if (! $index_data) {
        $error = $this->t("No available data.");
      } else {
        $this->indexLinks = \Drupal::service('ewp_institutions_get.json')
          ->idLinks($index_data, $this->indexLinkKey);
        $this->indexLabels = \Drupal::service('ewp_institutions_get.json')
          ->idLabel($index_data);

        // Check for an index item matching the index key provided in the path
        if (! array_key_exists($index_key, $this->indexLinks)) {
          $error = $this->t("Invalid index key: @index_key", [
            '@index_key' => $index_key
          ]);
        } else {
          // SUCCESS! First path argument is validated
          $this->indexKey = $index_key;
          $endpoint = $this->indexLinks[$this->indexKey];

          // Check for the API endpoint for this index item
          if (empty($endpoint)) {
            $error = $this->t("Item endpoint is not defined.");
          } else {
            // Check when the index was last updated
            $index_updated = \Drupal::service('ewp_institutions_get.fetch')
              ->checkUpdated('index');
            // Check when this item was last updated
            $item_updated = \Drupal::service('ewp_institutions_get.fetch')
              ->checkUpdated($index_key);
            // Decide whether to force a refresh
            $refresh = ($item_updated && $index_updated < $item_updated) ? FALSE : TRUE ;
            // Load the data for this index item
            $item_data = \Drupal::service('ewp_institutions_get.fetch')
              ->load($index_key, $endpoint);

            // Check for the actual index item data
            if (! $item_data) {
              $error = $this->t("No available data for @index_item", [
                '@index_item' => $this->indexLabels[$this->indexKey]
              ]);
            } else {
              $hei_list = \Drupal::service('ewp_institutions_get.json')
                ->idLabel($item_data);

              // Check for an institution matching the key provided in the path
              if (! array_key_exists($hei_key, $hei_list)) {
                $error = $this->t("Invalid institution key: @hei_key", [
                  '@hei_key' => $hei_key
                ]);
              } else {
                // SUCCESS! Second path argument is validated
                $this->institutionKey = $hei_key;
                // Check if an entity with the same hei_id already exists
                $exists = \Drupal::entityTypeManager()->getStorage('hei')
                  ->loadByProperties(['hei_id' => $this->institutionKey]);

                if (!empty($exists)) {
                  foreach ($exists as $id => $hei) {
                    $link = $hei->toLink();
                    $renderable = $link->toRenderable();
                  }
                  $error = $this->t('Institution with ID <code>@hei_id</code> already exists: @link', [
                    '@hei_id' => $this->institutionKey,
                    '@link' => render($renderable),
                  ]);
                } else {
                  // Prerequisites are met and data is loaded at this point
                  $message = $this->t('Institution data loaded successfully.');
                  \Drupal::service('messenger')->addMessage($message);
                }
              }
            }
          }
        }
      }
    }

    if ($error) {
      \Drupal::service('messenger')->addError($error);
      // Delete the entity form
      unset($form['add_form']);
    } else {
      // Fill in the header with the extracted information
      $header_markup = '<p><strong>' . $this->t('Index entry') . ':</strong> ';
      $header_markup .= $this->indexLabels[$this->indexKey] . '</p>';
      $header_markup .= '<p><strong>' . $this->t('Institution') . ':</strong> ';
      $header_markup .= $hei_list[$this->institutionKey] . '</p>';
      $form['header']['messages']['#markup'] = $header_markup;

      // Fill in the data preview
      $title = $hei_list[$this->institutionKey];
      $hei_data = \Drupal::service('ewp_institutions_get.json')
        ->toArray($item_data);
      $show_empty = FALSE;
      $preview = \Drupal::service('ewp_institutions_get.format')
        ->preview($title, $hei_data, $this->institutionKey, $show_empty);
      $form['data']['preview']['#markup'] = render($preview);

      // Extract the data for the target entity
      foreach ($hei_data as $key => $array) {
        if ($array['id'] == $this->institutionKey) {
          $this->institutionData = $hei_data[$key]['attributes'];
          ksort($this->institutionData);
        }
      }

      // Load the fieldmap
      $config = $this->config('ewp_institutions_get.fieldmap');
      $fieldmap = $config->get('field_mapping');

      // Remove empty values from the fieldmap
      foreach ($fieldmap as $key => $value) {
        if (empty($fieldmap[$key])) {
          unset($fieldmap[$key]);
        }
      }

      // Remove non mapped values from the entity data
      foreach ($this->institutionData as $key => $value) {
        if (! array_key_exists($key, $fieldmap)) {
          unset($this->institutionData[$key]);
        }
      }

      // Begin processing the entity form
      foreach ($form['add_form'] as $field_name => $array) {
        // Target the fields in the form render array
        if ((substr($field_name,0,1) !== '#') && (array_key_exists('widget', $array))) {
          // Remove non mapped, non required fields from the form
          // If a default value is set, it will not be lost
          if (! array_key_exists($field_name, $fieldmap) && ! $array['widget']['#required']) {
            unset($form['add_form'][$field_name]);
          } else {
            if (array_key_exists($field_name, $this->institutionData)) {
              // Typical field widget structure
              $field_widget = $form['add_form'][$field_name]['widget'];
              if (! array_key_exists('#theme', $form['add_form'][$field_name]['widget'])) {
                // Special cases for certain widgets
                switch ($field_name) {
                  case 'status':
                    $field_widget['value']['#default_value'] = $this->institutionData[$field_name];
                    $field_widget['value']['#attributes']['disabled'] = 'disabled';
                    $form['add_form'][$field_name]['widget'] = $field_widget;
                    break;

                  default:
                    dpm($form['add_form'][$field_name]);
                    break;
                }
              } else {
                // Handle single 'value' property
                if (! is_array($this->institutionData[$field_name])) {
                  $form['add_form'][$field_name]['widget'] = $this->populateDefault(
                    $this->institutionData[$field_name],
                    $field_widget
                  );
                  // Move the form element to the main array
                  $form[$field_name] = $form['add_form'][$field_name];
                  unset($form['add_form'][$field_name]);
                } else {
                  $data_array = $this->institutionData[$field_name];
                  // Check the array keys to determine what kind of array it is
                  if (count(array_filter(array_keys($data_array), 'is_string')) > 0) {
                    // An associative array means a single value of a complex field
                    $delta = 0;
                    // Handle each field property individually
                    foreach ($data_array as $property => $value) {
                      $form['add_form'][$field_name]['widget'] = $this->populateDefault(
                        $data_array[$property],
                        $field_widget,
                        $delta,
                        $property
                      );
                    }
                    // Move the form element to the main array
                    $form[$field_name] = $form['add_form'][$field_name];
                    unset($form['add_form'][$field_name]);
                  } else {
                    // Otherwise assume a field with multiple values
                    $field_widget['#max_delta'] = sizeof($data_array) - 1;
                    // Replicate the field widget for each value to import
                    for ($d=1; $d < sizeof($data_array); $d++) {
                      $field_widget[$d] = $field_widget[0];
                      $field_widget[$d]['#delta'] = $d;
                      $field_widget[$d]['#weight'] = $d;
                    }
                    // Remove the Add more button
                    unset($field_widget['add_more']);
                    // Reordering field values with dragtable is still possible

                    foreach ($data_array as $delta => $value) {
                      // Handle single 'value' property
                      if (! is_array($data_array[$delta])) {
                        $form['add_form'][$field_name]['widget'] = $this->populateDefault(
                          $data_array[$delta],
                          $field_widget,
                          $delta,
                        );
                      } else {
                        // Handle each field property individually
                        foreach ($data_array[$delta] as $property => $value) {
                          $form['add_form'][$field_name]['widget'] = $this->populateDefault(
                            $data_array[$delta][$property],
                            $field_widget,
                            $delta,
                            $property
                          );
                        }
                      }
                    }
                    // Move the form element to the main array
                    $form[$field_name] = $form['add_form'][$field_name];
                    unset($form['add_form'][$field_name]);
                  }
                }
              }
            } else {
              // code...
            }
          }
        } else {
          $form[$field_name] = $form['add_form'][$field_name];
          unset($form['add_form'][$field_name]);
        }

      }

    }

    // dpm($form['add_form']);
    // dpm($props);
    // dpm($matches);
    // dpm($this->institutionData);

    return $form;
  }

  /**
   * Populate field widget with default value
   */
  protected function populateDefault($data_value, array &$widget, $delta = 0, $property = 'value') {
    $old_default = $widget[$delta][$property]['#default_value'];
    $new_default = $data_value;

    if ($old_default) {
      // If a default if provided, do not empty the value
      $default_value = ($new_default) ? $new_default : $old_default;
    } else {
      // Without a default, copy the new value, even if empty
      $default_value = $new_default;
    }

    $widget[$delta][$property]['#default_value'] = $default_value;

    return $widget;
  }

  /**
   * Disable field widget
   */
  protected function disable($data_value, array &$widget, $delta = 0, $property = 'value') {
    $required = $widget[$delta][$property]['#required'];
    $default = $widget[$delta][$property]['#default_value'];

    if (!empty($default_value)) {
      // Make it readonly if there is a default value
      $readonly = TRUE;
    } else {
      // Make it readonly unless required field is empty
      $readonly = ($required && empty($default_value)) ? FALSE : TRUE ;
    }

    if ($readonly) {
      $widget[$delta][$property]['#attributes']['readonly'] = 'readonly';
    }

    return $widget;
  }

}
