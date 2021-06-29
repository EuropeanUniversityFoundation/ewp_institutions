<?php

namespace Drupal\ewp_institutions_ref\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ewp_institutions_get\InstitutionManager;

/**
 * Plugin implementation of the 'ewp_institutions_select' widget.
 *
 * @FieldWidget(
 *   id = "ewp_institutions_select",
 *   label = @Translation("Select Institution"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class InstitutionSelectWidget extends OptionsSelectWidget {

  const INDEX_SELECT = 'index_select';
  const HEI_SELECT = 'hei_select';

  /**
   * Index endpoint
   *
   * @var string
   */
  protected $indexEndpoint;

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
   * Institution list
   *
   * @var array
   */
  protected $heiList;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $storage_definition = $this->fieldDefinition->getFieldStorageDefinition();
    $target_type = $storage_definition->getSetting('target_type');
    if ($target_type != InstitutionManager::ENTITY_TYPE) {
      // fallback to the parent widget
      return $element;
    }

    // Get the field name from this particular field definiton
    $field_name = $items->getFieldDefinition()->getName();

    unset($element['#options']);
    $element['#type'] = 'value';
    $element['#prefix'] = '<div id="target-id">';
    $element['#suffix'] = '</div>';

    // Load stored values
    $references = $items->referencedEntities();
    $ref_value = isset($references[$delta]) ? $references[$delta] : NULL;
    $ref_id = (!empty($ref_value)) ? $ref_value->id() : NULL;
    $ref_index = (!empty($ref_value)) ? $ref_value
      ->get(InstitutionManager::INDEX_FIELD)->getValue()[0]['value'] : '';
    $ref_unique = (!empty($ref_value)) ? $ref_value
      ->get(InstitutionManager::UNIQUE_FIELD)->getValue()[0]['value'] : '';

    $state_index = $form_state->getValue(self::INDEX_SELECT);
    $default_index = (empty($state_index)) ? $ref_index : $state_index;

    $state_unique = $form_state->getValue(self::HEI_SELECT);
    $state_unique = ($state_unique === $ref_unique) ? NULL : $state_unique;
    $default_unique = (empty($state_unique)) ? $ref_unique : $state_unique;

    if (!empty($state_index) && !empty($state_unique)) {
      $default_id = $this->getInstitutionId($state_index, $state_unique);
    } else {
      $default_id = $ref_id;
    }

    $element['#default_value'] = [$default_id];
    $element['#value'] = [$default_id];

    $element['select_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $element['#title'],
      '#tree' => TRUE,
    ];

    // Load the settings.
    $config = \Drupal::config('ewp_institutions_get.settings');
    $this->indexEndpoint = $config->get('ewp_institutions_get.index_endpoint');
    $this->indexLinks = [];
    $this->indexLabels = [];

    $this->buildIndexOptions();

    // Build the Index
    $element['select_wrapper'][self::INDEX_SELECT] = [
      '#type' => 'select',
      '#title' => $this->t('Index'),
      // '#required' => $element['#required'],
      '#options' => $this->indexLabels,
      '#default_value' => $default_index,
      '#empty_value' => '',
      '#ajax' => [
        'callback' => [$this, 'refreshInstitutions'],
        'disable-refocus' => TRUE,
        'event' => 'change',
        'wrapper' => 'hei-select',
      ],
      '#attributes' => [
        'name' => 'index_select',
      ],
      '#weight' => '-9',
    ];

    $this->heiList = $this->buildInstitutionOptions($default_index);

    // Build the Institution list
    $element['select_wrapper'][self::HEI_SELECT] = [
      '#type' => 'select',
      '#title' => $this->t('Institution'),
      '#prefix' => '<div id="hei-select">',
      '#suffix' => '</div>',
      // '#required' => $element['#required'],
      '#options' => $this->heiList,
      '#default_value' => $default_unique,
      '#empty_value' => '',
      '#ajax' => [
        'callback' => [$this, 'refreshTargetId'],
        'disable-refocus' => TRUE,
        'event' => 'change',
        'wrapper' => 'target-id',
      ],
      '#validated' => TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="index_select"]' => ['value' => ''],
        ],
      ],
      '#weight' => '-8',
    ];

    // dpm($element);
    return $element;
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();

    if (\array_key_exists(self::INDEX_SELECT, $user_input)) {
      $index_key = $user_input[self::INDEX_SELECT];
      $form_state->setValue(self::INDEX_SELECT, $index_key);
    }

    $triggeringElement = $form_state->getTriggeringElement();
    switch ($triggeringElement['#type']) {
      case 'select':
        $field_name = $triggeringElement['#array_parents'][0];
        $delta = $triggeringElement['#array_parents'][2];
        $wrapper = $triggeringElement['#array_parents'][3];

        // if (\array_key_exists($field_name, $user_input)) {
        //   $hei_key = $user_input[$field_name][$delta][$wrapper][self::HEI_SELECT];
        //   $form_state->setValue(self::HEI_SELECT, $hei_key);
        // }
        break;

      default:
        // $state_index = $form_state->getValue(self::INDEX_SELECT);
        // $form_state->setValue(self::HEI_SELECT, $element['select_wrapper'][self::HEI_SELECT]['#value']);
        // $state_unique = $form_state->getValue(self::HEI_SELECT);
        dpm($form_state->getValues());
        break;
    }

    $message = json_encode($element['#value']);
    \Drupal::logger('ewp_institutions_ref')->notice(t('element_value: ') . $message);

    parent::validateElement($element, $form_state);
  }

  /**
  * Rebuild the list of Institutions
  */
  public function refreshInstitutions(array &$form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();
    $field_name = $triggeringElement['#array_parents'][0];
    $delta = $triggeringElement['#array_parents'][2];

    return $form[$field_name]['widget'][$delta]['select_wrapper'][self::HEI_SELECT];
  }

  /**
  * Recalculate the target ID
  */
  public function refreshTargetId(array &$form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();
    $field_name = $triggeringElement['#array_parents'][0];
    $delta = $triggeringElement['#array_parents'][2];

    return $form[$field_name]['widget'][$delta];
  }

  /**
  * Build the options for the Index select element
  */
  private function buildIndexOptions() {
    // Prepare the Index
    if (! empty($this->indexEndpoint)) {
      $index_data = \Drupal::service('ewp_institutions_get.fetch')
        ->getUpdated(InstitutionManager::INDEX_KEYWORD, $this->indexEndpoint);

      if ($index_data) {
        $this->indexLinks = \Drupal::service('ewp_institutions_get.json')
          ->idLinks($index_data, InstitutionManager::INDEX_LINK_KEY);
        $this->indexLabels = \Drupal::service('ewp_institutions_get.json')
          ->idLabel($index_data);
      }
    } else {
      $warning = $this->t("Index endpoint is not defined.");
      \Drupal::service('messenger')->addWarning($warning);
    }
  }

  /**
  * Build the options for the Institutions select element
  */
  private function buildInstitutionOptions($index_key) {
    // Prepare the Institution list
    $options = ['' => '- None -'];
    $endpoint = ($index_key) ? $this->indexLinks[$index_key] : '';

    if (! empty($endpoint)) {
      $json_data = \Drupal::service('ewp_institutions_get.fetch')
        ->getUpdated($index_key, $endpoint);

      if ($json_data) {
        // Build the options list
        $options += \Drupal::service('ewp_institutions_get.json')
          ->idLabel($json_data);
      }
    }

    return $options;
  }

  /**
  * Get the Institution ID from the selected parameters
  */
  private function getInstitutionId($index_key, $hei_key) {
    // Create a new Institution if none exists with the same key
    $hei = \Drupal::service('ewp_institutions_get.manager')
      ->getInstitution($hei_key, $index_key);

    if (!empty($hei)) {
      foreach ($hei as $id => $hei_obj) {
        \Drupal::logger('ewp_institutions_ref')->notice(t('id: ') . $id);
        return $id;
      }
    }

    return NULL;
  }
}
