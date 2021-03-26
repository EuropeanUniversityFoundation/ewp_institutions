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
   * Index JSON data
   *
   * @var string
   */
  protected $indexData;

  /**
   * Index JSON key with "link"
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
   * Index item key for target Institution
   *
   * @var string
   */
  protected $indexKey;

  /**
   * Institution list
   *
   * @var array
   */
  protected $heiList;

  /**
   * Institution JSON data
   *
   * @var string
   */
  protected $heiData;

  /**
   * Item key for target Institution
   *
   * @var string
   */
  protected $heiKey;

  /**
   * Data for target Institution
   *
   * @var array
   */
  protected $heiItemData;

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
    $this->indexData = NULL;
    $this->indexLinks = [];
    $this->indexLabels = [];
    $this->indexKey = NULL;

    $this->heiData = NULL;
    $this->heiList = [];
    $this->heiKey = NULL;
    $this->heiItemData = [];

    $error = $this->checkErrors($index_key, $hei_key);

    if ($error) {
      \Drupal::service('messenger')->addError($error);
      // Delete the entity form
      unset($form['add_form']);
    }
    else {
      // Fill in the header with the extracted information
      $header_markup = '<p><strong>' . $this->t('Index entry') . ':</strong> ';
      $header_markup .= $this->indexLabels[$this->indexKey] . '</p>';
      $header_markup .= '<p><strong>' . $this->t('Institution') . ':</strong> ';
      $header_markup .= $this->heiList[$this->heiKey] . '</p>';
      $form['header']['messages']['#markup'] = $header_markup;

      // Fill in the data preview
      $title = $this->heiList[$this->heiKey];
      $hei_data = \Drupal::service('ewp_institutions_get.json')
        ->toArray($this->heiData);
      $show_empty = FALSE;
      $preview = \Drupal::service('ewp_institutions_get.format')
        ->preview($title, $hei_data, $this->heiKey, $show_empty);
      $form['data']['preview']['#markup'] = render($preview);

      // Extract the data for the target entity
      foreach ($hei_data as $key => $array) {
        if ($array['id'] == $this->heiKey) {
          $this->heiItemData = $hei_data[$key]['attributes'];
          ksort($this->heiItemData);
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
      foreach ($this->heiItemData as $key => $value) {
        if (! array_key_exists($key, $fieldmap)) {
          unset($this->heiItemData[$key]);
        }
      }

      // Begin processing the entity form
      foreach ($form['add_form'] as $field_name => $array) {
        // Target the fields in the form render array
        if ((substr($field_name,0,1) !== '#') && (array_key_exists('widget', $array))) {
          // Target the field widget
          $field_widget = $form['add_form'][$field_name]['widget'];
          // Remove the Add more button for unlimited cardinality fields
          unset($field_widget['add_more']);
          // Reordering field values with dragtable is still possible

          // Handle non mapped, non required fields
          if (! array_key_exists($field_name, $fieldmap) && ! $array['widget']['#required']) {
            switch ($field_name) {
              case 'index_key':
                // Custom base field to hold the API index key
                $field_widget = $this->setDefault($this->indexKey,$field_widget);
                $field_widget = $this->setReadOnly($field_widget);
                // Move the form element to the main array
                $form['add_form'][$field_name]['widget'] = $field_widget;
                $form[$field_name] = $form['add_form'][$field_name];
                break;

              case 'status':
                // Preserve the Published status field
                $form[$field_name] = $form['add_form'][$field_name];
                break;

              default:
                break;
            }
            // Remove non mapped, non required fields from the form
            // If a default value is set, it will not be lost
            unset($form['add_form'][$field_name]);
          }
          else {
            // Handle mapped fields
            if (array_key_exists($field_name, $this->heiItemData)) {
              // Special cases for certain widgets
              if (! array_key_exists('#theme', $field_widget)) {
                switch ($field_name) {
                  case 'status':
                    $field_widget['value']['#default_value'] = $this->heiItemData[$field_name];
                    $form['add_form'][$field_name]['widget'] = $field_widget;
                    break;

                  default:
                    // dpm($form['add_form'][$field_name]);
                    break;
                }
              }
              // Generic field widgets with delta property
              else {
                // Handle single 'value' property
                if (! is_array($this->heiItemData[$field_name])) {
                  $field_widget = $this->setDefault($this->heiItemData[$field_name],$field_widget);
                  $field_widget = $this->setReadOnly($field_widget);
                  // Move the form element to the main array
                  $form['add_form'][$field_name]['widget'] = $field_widget;
                  $form[$field_name] = $form['add_form'][$field_name];
                  unset($form['add_form'][$field_name]);
                }
                // Handle multiple properties and multiple values
                else {
                  $data_array = $this->heiItemData[$field_name];
                  // An associative array means a single value of a complex field
                  if (count(array_filter(array_keys($data_array), 'is_string')) > 0) {
                    $delta = 0;
                    // Handle each field property individually
                    foreach ($data_array as $property => $value) {
                      $field_widget = $this->setDefault($data_array[$property],$field_widget,$delta,$property);
                      $field_widget = $this->setReadOnly($field_widget,$delta,$property);
                    }
                    // Move the form element to the main array
                    $form['add_form'][$field_name]['widget'] = $field_widget;
                    $form[$field_name] = $form['add_form'][$field_name];
                    unset($form['add_form'][$field_name]);
                  }
                  // Otherwise assume a field with multiple values
                  else {
                    // Check for a limit on the number of field values
                    $deltas = $field_widget['#cardinality'];
                    $max = ($deltas > 0) ? $deltas : sizeof($data_array);
                    $field_widget['#max_delta'] = $max - 1;
                    // Replicate the field widget for each value to import
                    for ($d=1; $d < $max; $d++) {
                      $field_widget[$d] = $field_widget[0];
                      $field_widget[$d]['#delta'] = $d;
                      $field_widget[$d]['#weight'] = $d;
                    }

                    // Truncate the data array if needed
                    $data_slice = array_slice($data_array, 0, $max);
                    foreach ($data_slice as $delta => $value) {
                      // Handle single 'value' property
                      if (! is_array($data_slice[$delta])) {
                        $field_widget = $this->setDefault($data_slice[$delta],$field_widget,$delta);
                        $field_widget = $this->setReadOnly($field_widget,$delta);
                      }
                      // Handle each field property individually
                      else {
                        foreach ($data_slice[$delta] as $property => $value) {
                          $field_widget = $this->setDefault($data_slice[$delta][$property],$field_widget,$delta,$property);
                          $field_widget = $this->setReadOnly($field_widget,$delta,$property);
                        }
                      }
                    }
                    // Move the form element to the main array
                    $form['add_form'][$field_name]['widget'] = $field_widget;
                    $form[$field_name] = $form['add_form'][$field_name];
                    unset($form['add_form'][$field_name]);
                  }
                }
              }
            }
            // Preserve form elements for non mapped, required fields
            else {
              $form[$field_name] = $form['add_form'][$field_name];
              unset($form['add_form'][$field_name]);
            }
          }
        }
        // Preserve all other form properties
        else {
          $form[$field_name] = $form['add_form'][$field_name];
          unset($form['add_form'][$field_name]);
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Enable all disabled fields prior to submission
    if (! empty($form['add_form']['status']['widget']['#attributes']['disabled'])) {
      unset($form['add_form']['status']['widget']['#attributes']['disabled']);
    }

    return parent::submitForm($form, $form_state);
  }

  /**
   * Check for errors prior to rebuilding the form
   */
  protected function checkErrors($index_key = NULL, $hei_key = NULL) {
    $error = NULL;

    // Check for the API index endpoint
    if (empty($this->indexEndpoint)) {
      $error = $this->t("Index endpoint is not defined.");
    }
    else {
      $index_data = \Drupal::service('ewp_institutions_get.fetch')
        ->load('index', $this->indexEndpoint);

      // Check for the actual index data
      if (! $index_data) {
        $error = $this->t("No available data.");
      }
      else {
        $this->indexData = $index_data;
        $this->indexLinks = \Drupal::service('ewp_institutions_get.json')
          ->idLinks($this->indexData, $this->indexLinkKey);
        $this->indexLabels = \Drupal::service('ewp_institutions_get.json')
          ->idLabel($this->indexData);

        // Check for an index item matching the index key provided in the path
        if (! array_key_exists($index_key, $this->indexLinks)) {
          $error = $this->t("Invalid index key: @index_key", [
            '@index_key' => $index_key
          ]);
        }
        else {
          // SUCCESS! First path argument is validated
          $this->indexKey = $index_key;
          $endpoint = $this->indexLinks[$this->indexKey];

          // Check for the API endpoint for this index item
          if (empty($endpoint)) {
            $error = $this->t("Item endpoint is not defined.");
          }
          else {
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
            }
            else {
              $this->heiData = $item_data;
              $this->heiList = \Drupal::service('ewp_institutions_get.json')
                ->idLabel($this->heiData);

              // Check for an institution matching the key provided in the path
              if (! array_key_exists($hei_key, $this->heiList)) {
                $error = $this->t("Invalid institution key: @hei_key", [
                  '@hei_key' => $hei_key
                ]);
              }
              else {
                // SUCCESS! Second path argument is validated
                $this->heiKey = $hei_key;
                // Check if an entity with the same hei_id already exists
                $exists = \Drupal::entityTypeManager()->getStorage('hei')
                  ->loadByProperties(['hei_id' => $this->heiKey]);

                if (!empty($exists)) {
                  foreach ($exists as $id => $hei) {
                    $link = $hei->toLink();
                    $renderable = $link->toRenderable();
                  }
                  $error = $this->t('Institution with ID <code>@hei_id</code> already exists: @link', [
                    '@hei_id' => $this->heiKey,
                    '@link' => render($renderable),
                  ]);
                }
                else {
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

    return $error;
  }


  /**
   * Populate field widget with default value
   */
  protected function setDefault($data_value, array &$widget, $delta = 0, $property = 'value') {
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
  protected function setReadOnly(array &$widget, $delta = 0, $property = 'value') {
    $required = $widget['#required'];
    $default = $widget[$delta][$property]['#default_value'];

    if (! empty($default)) {
      // Make it readonly if there is a default value
      $readonly = TRUE;
    } else {
      // Make it readonly unless required field is empty
      $readonly = ($required && empty($default)) ? FALSE : TRUE ;
    }

    if ($readonly) {
      switch ($widget[$delta][$property]['#type']) {
        case 'select':
          // Select elements cannot be set as readonly
          // Instead limit the options to the default value
          $options = $widget[$delta][$property]['#options'];
          $widget[$delta][$property]['#options'] = [$default => $options[$default]];
          unset($widget[$delta][$property]['#empty_option']);
          unset($widget[$delta][$property]['#empty_value']);
          break;

        default:
          $widget[$delta][$property]['#attributes']['readonly'] = 'readonly';
          break;
      }
      // Some inline styling to illustrate the change
      $widget[$delta][$property]['#attributes']['style'] = "background-color: #EEFFEE";
    }

    return $widget;
  }

}
