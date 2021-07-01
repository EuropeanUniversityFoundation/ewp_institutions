<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\ewp_institutions_get\Form\PreLoadForm;
use Drupal\ewp_institutions_get\InstitutionManager;

class InstitutionAutoImportForm extends PreLoadForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_get_auto_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['index_select'] = [
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

    $form['hei_select'] = [
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
        'wrapper' => 'view-hei-data',
      ],
      '#validated' => TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="index_select"]' => ['value' => ''],
        ],
      ],
      '#weight' => '-8',
    ];

    $form['messages'] = [
      '#type' => 'markup',
      '#markup' => '<div id="view-messages"></div>',
      '#weight' => '-6',
    ];

    $form['data'] = [
      '#type' => 'markup',
      '#markup' => '<div id="view-hei-data"></div>',
      '#weight' => '-5',
    ];

    return $form;
  }

  /**
  * Fetch the data and build select list
  */
  public function getInstitutionList(array $form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');
    $endpoint = ($index_item) ? $this->indexLinks[$index_item] : '';

    $options = ['' => '- None -'];

    if (! empty($endpoint)) {
      $json_data = \Drupal::service('ewp_institutions_get.fetch')
        ->getUpdated($index_item, $endpoint);

      if ($json_data) {
        // Build the options list
        $options += \Drupal::service('ewp_institutions_get.json')
          ->idLabel($json_data);
      }
    }

    $form['hei_select']['#options'] = $options;
    return $form['hei_select'];
  }

  /**
  * Fetch the data and preview Institution
  */
  public function previewInstitution(array $form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');
    $endpoint = ($index_item) ? $this->indexLinks[$index_item] : '';

    $output = '';

    // JSON data has to be stored at this point per previous step
    $json_data = \Drupal::service('ewp_institutions_get.fetch')
      ->load($index_item, $endpoint);
    $hei_list = \Drupal::service('ewp_institutions_get.json')
      ->idLabel($json_data);

    $hei_item = $form_state->getValue('hei_select');

    // Create a new Institution if none exists with the same key
    $hei = \Drupal::service('ewp_institutions_get.manager')
      ->getInstitution($hei_item, $index_item);
    if (!empty($hei)) {
      foreach ($hei as $id => $value) {
        $entity_id = $id;
      }
      $view_mode = 'full';
      $entity = \Drupal::entityTypeManager()
        ->getStorage(InstitutionManager::ENTITY_TYPE)
        ->load($entity_id);
      $view_builder = \Drupal::entityTypeManager()
        ->getViewBuilder(InstitutionManager::ENTITY_TYPE);
      $pre_render = $view_builder->view($entity, $view_mode);
      $html= render($pre_render);

      $text = $this->t('This institution is now available for selection.');
      $modal = \Drupal::request()->query->has('modal');
      if ($modal) {
        $text .= ' ';
        $text .= $this->t('You can close this popup and find it in the list.');
      }
      \Drupal::service('messenger')->addMessage($text);
    }
    else {
      $html = '';
    }

    $messages = StatusMessages::renderMessages();

    $ajax_response = new AjaxResponse();
    $ajax_response
      ->addCommand(new HtmlCommand('#view-messages', $messages))
      ->addCommand(new HtmlCommand('#view-hei-data', $html));
    return $ajax_response;
  }

}
