<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ewp_institutions\Form\InstitutionEntityForm;

/**
 * Alternative for Institution Edit form.
 *
 * @ingroup ewp_institutions
 */
class InstitutionEntityUpdateForm extends InstitutionEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['add-form'] = parent::buildForm($form, $form_state);

    foreach ($form['add-form'] as $name => $array) {
      // Target the fields
      if ((substr($name, 0, 1) !== '#')
        && (array_key_exists('widget', $array))) {
        foreach ($array['widget'] as $idx => $widget) {
          // Target the field deltas
          if (ctype_digit((string) $idx)) {
            // Target the field properties
            foreach ($widget as $key => $prop) {
              if (!in_array(substr($key, 0, 1), ['#', '_'])) {
                // Requires different handling depending on form field type
                switch ($prop['#type']) {
                  case 'select':
                    // Eliminate the options in select lists
                    $form['add-form'][$name]['widget'][$idx][$key]['#options'] = [];
                    break;

                  default:
                    // Make the form field readonly
                    $form['add-form'][$name]['widget'][$idx][$key]['#attributes'] = [
                      'readonly' => 'readonly',
                    ];
                    break;
                }
              }
            }
          }
        }
      }
    }

    // dpm($form);

    return $form;
  }

}
