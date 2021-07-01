<?php

namespace Drupal\ewp_institutions_ref\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ewp_institutions\Entity\InstitutionEntity;
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
class InstitutionSelectWidget extends WidgetBase {

  const WRAPPER = 'select_wrapper';
  const INDEX_SELECT = 'index_select';
  const HEI_SELECT = 'hei_select';

  /**
   * Field name
   *
   * @var string
   */
  protected $fieldName;

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
    // Get the field name from this particular field definiton
    $this->fieldName = $items->getFieldDefinition()->getName();

    // Load stored values
    $ref_entities = $items->referencedEntities();
    $ref_value = isset($ref_entities[$delta]) ? $ref_entities[$delta] : NULL;

    // Lock the widget if the target type is not Institution
    $storage_definition = $this->fieldDefinition->getFieldStorageDefinition();
    $target_type = $storage_definition->getSetting('target_type');
    if ($target_type != InstitutionManager::ENTITY_TYPE) {
      $element += [
        '#type' => 'markup',
        '#markup' => $this->t('Target type must be set to Institution.'),
        '#default_value' => $ref_value,
      ];
      return $element;
    }

    // This element is meant to store a value away from prying eyes
    $element += [
      '#type' => 'value',
      '#prefix' => '<div id="target-id">',
      '#suffix' => '</div>',
    ];

    // Add custom validation
    $element['#element_validate'][] = [get_class($this), 'validateElement'];

    // Determine the Index key for the select element
    $ref_index = (!empty($ref_value)) ? $ref_value
      ->get(InstitutionManager::INDEX_FIELD)->getValue()[0]['value'] : '';
    $state_index = $form_state->getValue(self::INDEX_SELECT);
    //
    if (!empty($state_index)) {
      \Drupal::logger('formElement')->notice(t('$state_index is ') . $state_index);
    }
    //
    $default_index = (empty($state_index)) ? $ref_index : $state_index;

    // Determine the Institution key for the select element
    $ref_unique = (!empty($ref_value)) ? $ref_value
      ->get(InstitutionManager::UNIQUE_FIELD)->getValue()[0]['value'] : '';
    $state_unique = $form_state->getValue(self::HEI_SELECT);
    //
    if (!empty($state_unique)) {
      \Drupal::logger('formElement')->notice(t('$state_unique is ') . $state_unique);
    }
    //
    $state_unique = ($state_unique === $ref_unique) ? NULL : $state_unique;
    //
    \Drupal::logger('formElement')->notice(t('$state_unique is now ') . $state_unique);
    //
    $default_unique = (empty($state_unique)) ? $ref_unique : $state_unique;

    // Determine the Institution entity ID
    $ref_id = (!empty($ref_value)) ? $ref_value->id() : NULL;
    if (!empty($state_index) && !empty($state_unique)) {
      $default_id = $this->getInstitutionId($state_index, $state_unique);
    } else {
      $default_id = $ref_id;
    }

    $default_value = (!empty($default_id)) ? InstitutionEntity::load($default_id) : NULL;
    $element['#default_value'] = $default_value;
    $element['#value'] = [$delta => $default_value];
    // Wrapper for select elements
    $element[self::WRAPPER] = [
      '#type' => 'fieldset',
      '#title' => $element['#title'],
      // '#tree' => TRUE,
    ];

    // Load the settings.
    $config = \Drupal::config('ewp_institutions_get.settings');
    $this->indexEndpoint = $config->get('ewp_institutions_get.index_endpoint');
    $this->indexLinks = [];
    $this->indexLabels = [];

    // Build the options for the Index select element
    $this->buildIndexOptions();

    // Build the Index select element
    $element[self::WRAPPER][self::INDEX_SELECT] = [
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

    // Build the options for the Institution select element
    $this->heiList = $this->buildInstitutionOptions($default_index);

    // Build the Institution select element
    $element[self::WRAPPER][self::HEI_SELECT] = [
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
    // return $element;
    return ['target_id' => $element];
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
    //
    $message = json_encode(array_keys($user_input));
    \Drupal::logger('validateElement')->error(t('$user_input contains ') . $message);
    //

    if (\array_key_exists(self::INDEX_SELECT, $user_input)) {
      $index_input = $user_input[self::INDEX_SELECT];
      \Drupal::logger('validateElement')->notice(t('INDEX_SELECT input: ') . $index_input);
      $form_state->setValue(self::INDEX_SELECT, $index_input);
    }

    // There is no top level key for HEI_SELECT in user input
    $triggeringElement = $form_state->getTriggeringElement();
     switch ($triggeringElement['#type']) {
      case 'select':
        $field_name = $triggeringElement['#array_parents'][0];
        $delta = $triggeringElement['#array_parents'][2];
        $col = $triggeringElement['#array_parents'][3];
        $wrapper = $triggeringElement['#array_parents'][4];

        if (\array_key_exists($field_name, $user_input)) {
          $hei_key = $user_input[$field_name][$delta][$col][$wrapper][self::HEI_SELECT];
          //
          \Drupal::logger('validateElement')->notice(t('HEI_SELECT is ') . $hei_key);
          //
          $form_state->setValue(self::HEI_SELECT, $hei_key);
        }
        break;

      default:
        $field_name = $element['#parents'][0];
        $delta = $element['#parents'][1];
        $col = $element['#parents'][2];
        dpm($element);
        // $state_index = $form_state->getValue(self::INDEX_SELECT);
        // $form_state->setValue(self::HEI_SELECT, $element[self::WRAPPER][self::HEI_SELECT]['#value']);
        // $state_unique = $form_state->getValue(self::HEI_SELECT);
        // dpm($element);
        // $value = [$element['#delta'] => $element['#value']];
        // $form_state->setValue($element['#parents'][0], $element['#value']);
        // unset($element['#value'][self::WRAPPER]);
        // dpm($element['#parents'][0]);
        // dpm($element['#value']);
        $form_state->unsetValue([$field_name, $delta, $col, self::WRAPPER]);
        dpm($form_state->getValues());
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    dpm($values);
    foreach ($values as $key => $value) {
      // The entity_autocomplete form element returns an array when an entity
      // was "autocreated", so we need to move it up a level.
      if (is_array($value['target_id'])) {
        unset($values[$key]['target_id']);
        $values[$key] += $value['target_id'];

        if (array_key_exists(self::WRAPPER, $values[$key])) {
          unset($values[$key][self::WRAPPER]);
        }
      }
    }

    dpm($values);
    return $values;
  }

  /**
  * Rebuild the list of Institutions
  */
  public function refreshInstitutions(array &$form, FormStateInterface $form_state) {
    $form_state->setValue(self::HEI_SELECT, '');
    $triggeringElement = $form_state->getTriggeringElement();
    //
    // $message = json_encode($triggeringElement['#array_parents']);
    // \Drupal::logger('refreshInstitutions')->warning(t('$triggeringElement: ') . $message);
    //
    $field_name = $triggeringElement['#array_parents'][0];
    $delta = $triggeringElement['#array_parents'][2];
    // dpm($form[$field_name]['widget'][$delta]['target_id'][self::WRAPPER][self::HEI_SELECT]);
    dpm($form_state->getValue(self::INDEX_SELECT));
    dpm($form_state->getValue(self::HEI_SELECT));

    return $form[$field_name]['widget'][$delta]['target_id'][self::WRAPPER][self::HEI_SELECT];
  }

  /**
  * Recalculate the target ID
  */
  public function refreshTargetId(array &$form, FormStateInterface $form_state) {
    $triggeringElement = $form_state->getTriggeringElement();
    //
    // $message = json_encode($triggeringElement['#array_parents']);
    // \Drupal::logger('refreshTargetId')->warning(t('#array_parents: ') . $message);
    //
    $field_name = $triggeringElement['#array_parents'][0];
    $delta = $triggeringElement['#array_parents'][2];
    // dpm($form[$field_name]['widget'][$delta]['target_id'][self::WRAPPER][self::HEI_SELECT]);
    dpm($form_state->getValue(self::INDEX_SELECT));
    dpm($form_state->getValue(self::HEI_SELECT));

    return $form[$field_name]['widget'][$delta]['target_id'];
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
