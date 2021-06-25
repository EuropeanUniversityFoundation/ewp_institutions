<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\ewp_institutions\Form\InstitutionEntityForm;
use Drupal\ewp_institutions_get\InstitutionManager;

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

    $error = $this->checkErrors($index_key, $hei_key);

    if ($error) {
      \Drupal::service('messenger')->addError($error);
      // Delete the entity form
      unset($form['add_form']);
      return $form;
    }

    $this->indexData = \Drupal::service('ewp_institutions_get.fetch')
      ->load(InstitutionManager::INDEX_KEYWORD, $this->indexEndpoint);

    $this->indexLinks = \Drupal::service('ewp_institutions_get.json')
      ->idLinks($this->indexData, InstitutionManager::INDEX_LINK_KEY);

    $this->indexLabels = \Drupal::service('ewp_institutions_get.json')
      ->idLabel($this->indexData);

    // First path argument
    $this->indexKey = $index_key;

    $this->heiData = \Drupal::service('ewp_institutions_get.fetch')
      ->getUpdated($this->indexKey, $this->indexLinks[$this->indexKey]);

    $this->heiList = \Drupal::service('ewp_institutions_get.json')
      ->idLabel($this->heiData);

    // Second path argument
    $this->heiKey = $hei_key;

    // This is populated later
    $this->heiItemData = [];

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
        // Get expanded data array
        $hei_expanded_data = \Drupal::service('ewp_institutions_get.json')
          ->toArray($this->heiData, TRUE);
        $this->heiItemData = $hei_expanded_data[$key]['attributes'];
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
        $widget = $form['add_form'][$field_name]['widget'];
        // Remove the Add more button for unlimited cardinality fields
        unset($widget['add_more']);
        // Reordering field values with dragtable is still possible

        // Handle non mapped, non required fields
        if (! array_key_exists($field_name, $fieldmap) && ! $array['widget']['#required']) {
          switch ($field_name) {
            case 'index_key':
              // Custom base field to hold the API index key
              $widget = $this->setDefault($this->indexKey,$widget);
              $widget = $this->setReadOnly($widget);
              // Move the form element to the main array
              $form['add_form'][$field_name]['widget'] = $widget;
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
            if (! array_key_exists('#theme', $widget)) {
              switch ($field_name) {
                case 'status':
                  $widget['value']['#default_value'] = $this->heiItemData[$field_name];
                  $form['add_form'][$field_name]['widget'] = $widget;
                  break;

                default:
                  // dpm($form['add_form'][$field_name]);
                  break;
              }
            }
            // Generic field widgets with delta index
            else {
              // Extract the field properties from the widget array
              $field_props = [];
              foreach ($widget[0] as $property => $value) {
                if (!in_array(substr($property,0,1), ['#', '_'])) {
                  $field_props[] = $property;
                }
              }

              // Handle single value in the API data (probably empty)
              if (! is_array($this->heiItemData[$field_name])) {
                $data_value = $this->heiItemData[$field_name];
                // Assign the value to all field properties
                $data_array = [];
                foreach ($field_props as $index => $property) {
                  $data_array[0][$property] = $data_value;
                }
                $this->heiItemData[$field_name] = $data_array;
              }

              // Handle expanded API data
              $data_array = $this->heiItemData[$field_name];

              // Check for a limit on the number of field values
              $cardinality = $widget['#cardinality'];
              // With unlimited values, the data size is the actual limit
              $max = ($cardinality > 0) ? $cardinality : sizeof($data_array);
              $widget['#max_delta'] = $max - 1;

              if ($max > sizeof($data_array)) {
                // Delete the widgets that will not be populated
                for ($d = sizeof($data_array); $d < $max; $d++) {
                  unset($widget[$d]);
                }
              }
              elseif ($cardinality < 0) {
                // Replicate the field widget for each value to import
                for ($d = 1; $d < $max; $d++) {
                  $widget[$d] = $widget[0];
                  $widget[$d]['#delta'] = $d;
                  $widget[$d]['#weight'] = $d;
                }
              }

              // Truncate the data array if needed
              $data_slice = array_slice($data_array, 0, $max);

              foreach ($data_slice as $delta => $value) {
                // Handle each field property individually
                foreach ($data_slice[$delta] as $property => $value) {
                  $widget = $this->setDefault($value,$widget,$delta,$property);
                  $widget = $this->setReadOnly($widget,$delta,$property);
                }

                // Move the form element to the main array
                $form['add_form'][$field_name]['widget'] = $widget;
                $form[$field_name] = $form['add_form'][$field_name];
                unset($form['add_form'][$field_name]);
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
    $error = \Drupal::service('ewp_institutions_get.manager')
      ->checkErrors($index_key, $hei_key);

    if (empty($error) && !empty($hei_key)) {
      // Check if an entity with the same hei_id already exists
      $exists = \Drupal::service('ewp_institutions_get.manager')
        ->getInstitutionId($hei_key);

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
      // If a default is provided, do not empty the value
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
          // Instead limit the options to the default value, when given
          if ($default) {
            $options = $widget[$delta][$property]['#options'];
            // The default value might not exist in the options
            if (! array_key_exists($default, $options)) {
              // Known edge case: Other ID widget
              if (array_key_exists('custom', $widget[$delta])) {
                // Store the default value in the custom field
                $widget[$delta]['custom']['#default_value'] = $default;
                // Apply all the other changes to the form element
                $widget[$delta]['custom']['#attributes']['readonly'] = 'readonly';
                $widget[$delta]['custom']['#attributes']['style'] = "background-color: #EEFFEE";
                // Rebuild the visibility states
                $widget[$delta]['custom']['#states'] = [
                  'visible' => [
                    'select[id="' . $widget['#field_name'] . '-type-' . $delta . '"]' => [
                      'value' => 'custom'
                    ],
                  ],
                ];
                // Set the default value to custom
                $default = 'custom';
              } else {
                // Generic fallback: use the key as option name
                $options[$default] = $default;
              }
            }
            $widget[$delta][$property]['#options'] = [$default => $options[$default]];
            unset($widget[$delta][$property]['#empty_option']);
            unset($widget[$delta][$property]['#empty_value']);
          } else {
            $widget[$delta][$property]['#options'] = [];
          }
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
