<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\ewp_institutions_get\Form\PreviewForm;

/**
 * Alternative for Institution Add form.
 *
 * @ingroup ewp_institutions
 */
class InstitutionEntitySelectForm extends PreviewForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hei_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Build the form header with the AJAX components
    $form['header'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select an Institution to import'),
      '#weight' => '-100'
    ];

    $form['header']['index_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Index'),
      '#options' => $this->indexLabels,
      '#default_value' => '',
      '#empty_value' => '',
      '#ajax' => [
        'callback' => '::getInstitutionList',
        'disable-refocus' => TRUE,
        'event' => 'change',
        'wrapper' => 'hei-select',
      ],
      '#attributes' => [
        'name' => 'index_select',
      ],
      '#weight' => '-9',
    ];

    $form['header']['hei_select'] = [
      '#type' => 'select',
      '#title' => $this->t('Institution'),
      '#prefix' => '<div id="hei-select">',
      '#suffix' => '</div>',
      '#options' => [],
      '#default_value' => '',
      '#empty_value' => '',
      '#ajax' => [
        'callback' => '::previewInstitution',
        'disable-refocus' => TRUE,
        'event' => 'change',
        'wrapper' => 'data',
      ],
      '#validated' => TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="index_select"]' => ['value' => ''],
        ],
      ],
      '#weight' => '-8',
    ];

    $form['data'] = [
      '#type' => 'container',
      '#prefix' => '<div id="data">',
      '#suffix' => '</div>',
      '#weight' => '-7',
    ];

    $form['data']['status'] = [
      '#type' => 'hidden',
      '#value' => '',
      '#attributes' => [
        'name' => 'data_status',
      ],
    ];

    $form['data']['preview'] = [
      '#type' => 'markup',
      '#markup' => '',
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => '-6',
    ];

    $form['actions']['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#attributes' => [
        'class' => [
          'button--primary',
        ]
      ],
      '#states' => [
        'disabled' => [
          ':input[name="hei_select"]' => ['value' => ''],
        ],
        'visible' => [
          ':input[name="data_status"]' => ['value' => ''],
        ],
      ],
    ];

    // dpm($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');
    $hei_id = $form_state->getValue('hei_select');

    $form_state->setRedirect('entity.hei.import_form',[
      'index_key' => $index_item,
      'hei_key' => $hei_id
    ]);
  }

  /**
  * Fetch the data and build select list
  */
  public function getInstitutionList(array $form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');

    $endpoint = ($index_item) ? $this->indexLinks[$index_item] : '';

    $options = ['' => '- None -'];

    if (! empty($endpoint)) {
      // Check when the index was last updated
      $index_updated = \Drupal::service('ewp_institutions_get.fetch')
        ->checkUpdated('index');

      // Check when this item was last updated
      $item_updated = \Drupal::service('ewp_institutions_get.fetch')
        ->checkUpdated($index_item);

      // Decide whether to force a refresh
      $refresh = ($item_updated && $index_updated < $item_updated) ? FALSE : TRUE ;

      $json_data = \Drupal::service('ewp_institutions_get.fetch')
        ->load($index_item, $endpoint);

      if ($json_data) {
        // Build the options list
        $options += \Drupal::service('ewp_institutions_get.json')
          ->idLabel($json_data);
      }
    }

    $form['header']['hei_select']['#options'] = $options;

    return $form['header']['hei_select'];
  }

  /**
  * Fetch the data and preview Institution
  */
  public function previewInstitution(array $form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');

    $endpoint = ($index_item) ? $this->indexLinks[$index_item] : '';

    // JSON data has to be stored at this point per previous step
    $json_data = \Drupal::service('ewp_institutions_get.fetch')
      ->load($index_item, $endpoint);

    $hei_list = \Drupal::service('ewp_institutions_get.json')
      ->idLabel($json_data);

    $hei_id = $form_state->getValue('hei_select');

    // Check if an entity with the same hei_id already exists
    $exists = \Drupal::entityTypeManager()->getStorage('hei')
      ->loadByProperties(['hei_id' => $hei_id]);

    if (!empty($exists)) {
      foreach ($exists as $id => $hei) {
        $link = $hei->toLink();
        $renderable = $link->toRenderable();
      }

      $error = $this->t('Institution with ID <code>@hei_id</code> already exists: @link', [
        '@hei_id' => $hei_id,
        '@link' => render($renderable),
      ]);

      \Drupal::service('messenger')->addError($error);

      $message = StatusMessages::renderMessages();

      $status = $hei_id;
    } else {
      $title = $hei_list[$hei_id];

      $data = \Drupal::service('ewp_institutions_get.json')
        ->toArray($json_data);

      $show_empty = FALSE;

      $message = \Drupal::service('ewp_institutions_get.format')
        ->preview($title, $data, $hei_id, $show_empty);

      $status = '';
    }

    $form['data']['messages']['#markup'] = render($message);
    $form['data']['status']['#value'] = $status;

    return $form['data'];
  }

}
