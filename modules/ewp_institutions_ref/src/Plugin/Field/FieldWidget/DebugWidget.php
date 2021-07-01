<?php

namespace Drupal\ewp_institutions_ref\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'debug_select' widget.
 *
 * @FieldWidget(
 *   id = "debug_select",
 *   label = @Translation("Autocomplete debug"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class DebugWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    dpm($element);

    return $element;
  }

}
