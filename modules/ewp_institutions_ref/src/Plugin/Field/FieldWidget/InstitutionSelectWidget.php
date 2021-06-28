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

    unset($element['#options']);
    $element['#type'] = 'value';

    // Load default values
    $references = $items->referencedEntities();
    $default_value = isset($references[$delta]) ? $references[$delta] : NULL;
    $default_id = (!empty($default_value)) ? $default_value->id() : NULL;
    $default_index = (!empty($default_value)) ? $default_value
      ->get(InstitutionManager::INDEX_FIELD)->getValue()[0]['value'] : '';
    $default_unique = (!empty($default_value)) ? $default_value
      ->get(InstitutionManager::UNIQUE_FIELD)->getValue()[0]['value'] : '';
    dpm($default_unique);

    // Load the settings.
    $config = \Drupal::config('ewp_institutions_get.settings');
    $this->indexEndpoint = $config->get('ewp_institutions_get.index_endpoint');
    $this->indexLinks = [];
    $this->indexLabels = [];

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

    $element['#default_value'] = [$default_id];

    $element['select-wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $element['#title'],
    ];

    $element['select-wrapper']['index_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Index'),
      '#options' => $this->indexLabels,
      // '#default_value' => $this->indexLabels[$default_index],
      '#default_value' => $default_index,
      '#empty_value' => '',
      '#ajax' => [
        'callback' => [$this, 'refresh'],
        'disable-refocus' => TRUE,
        'event' => 'change',
      ],
      '#attributes' => [
        'name' => 'index_select',
      ],
      '#weight' => '-9',
    ];

    $endpoint = ($default_index) ? $this->indexLinks[$default_index] : '';

    $options = ['' => '- None -'];

    if (! empty($endpoint)) {
      $json_data = \Drupal::service('ewp_institutions_get.fetch')
        ->getUpdated($default_index, $endpoint);

      if ($json_data) {
        // Build the options list
        $options += \Drupal::service('ewp_institutions_get.json')
          ->idLabel($json_data);
      }
    }


    $element['select-wrapper']['hei_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Institution'),
      '#options' => $options,
      '#default_value' => $default_unique,
      '#empty_value' => '',
      '#validated' => TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="index_select"]' => ['value' => ''],
        ],
      ],
      '#weight' => '-8',
    ];

    dpm($element);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = Html::decodeEntities(strip_tags($label));
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if ($this->multiple) {
      // Multiple select: add a 'none' option for non-required fields.
      if (!$this->required) {
        return t('- None -');
      }
    }
    else {
      // Single select: add a 'none' option for non-required fields,
      // and a 'select a value' option for required fields that do not come
      // with a value selected.
      if (!$this->required) {
        return t('- None -');
      }
      if (!$this->has_value) {
        return t('- Select a value -');
      }
    }
  }

  /**
  * Rebuild the form
  */
  public function refresh(array $form, FormStateInterface $form_state) {
    // $triggering = $form_state->getTriggeringElement();
    // $message = json_encode($triggering->getFieldStorageDefinition());
    // $value = $form_state->getValue($triggering['#array_parents']);
    // \Drupal::logger('ewp_institutions_ref')->notice($message);

    // $form_state->setRebuild(TRUE);
    return $form;
  }
}
