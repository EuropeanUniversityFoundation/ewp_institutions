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

    // Load the fieldmap
    $config = $this->config('ewp_institutions_get.fieldmap');
    $fieldmap = $config->get('field_mapping');

    // Remove empty values
    foreach ($fieldmap as $key => $value) {
      if (empty($fieldmap[$key])) {
        unset($fieldmap[$key]);
      }
    }

    foreach ($form['add_form'] as $name => $array) {
      // Target the fields in the form render array
      if ((substr($name,0,1) !== '#') && (array_key_exists('widget', $array))) {
        // Remove non mapped, non required fields from the form
        // If a default value is set, it will not be lost
        if (!array_key_exists($name, $fieldmap) && !$array['widget']['#required']) {
          unset($form['add_form'][$name]);
        } else {
          $form['add_form'][$name]['#prefix'] = '<div id="' . $name . '">';
          $form['add_form'][$name]['#suffix'] = '</div>';
        }
      }
    }

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

    if (empty($this->indexEndpoint)) {
      // Missing endpoint is a deal breaker
      $error = $this->t("Index endpoint is not defined.");
    } else {
      $index_data = \Drupal::service('ewp_institutions_get.fetch')
        ->load('index', $this->indexEndpoint);

      if (! $index_data) {
        // Missing index data is a deal breaker
        $error = $this->t("No available data.");
      } else {
        $this->indexLinks = \Drupal::service('ewp_institutions_get.json')
          ->idLinks($index_data, $this->indexLinkKey);
        $this->indexLabels = \Drupal::service('ewp_institutions_get.json')
          ->idLabel($index_data);

        if (! array_key_exists($index_key, $this->indexLinks)) {
          // Invalid index key is a deal breaker
          $error = $this->t("Invalid index key: @index_key", [
            '@index_key' => $index_key
          ]);
        } else {
          // SUCCESS! First path argument is validated
          $this->indexKey = $index_key;
          $endpoint = $this->indexLinks[$this->indexKey];

          if (empty($endpoint)) {
            // Missing endpoint is a deal breaker
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

            if (! $item_data) {
              // Missing item data is a deal breaker
              $error = $this->t("No available data for @index_item", [
                '@index_item' => $this->indexLabels[$this->indexKey]
              ]);
            } else {
              $hei_list = \Drupal::service('ewp_institutions_get.json')
                ->idLabel($item_data);

              if (! array_key_exists($hei_key, $hei_list)) {
                // Invalid item key is a deal breaker
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

      $title = $hei_list[$this->institutionKey];
      $hei_data = \Drupal::service('ewp_institutions_get.json')
        ->toArray($item_data);
      $show_empty = FALSE;
      $preview = \Drupal::service('ewp_institutions_get.format')
        ->preview($title, $hei_data, $this->institutionKey, $show_empty);
      $form['data']['preview']['#markup'] = render($preview);
    }


    // dpm($form);

    return $form;
  }

  /**
  * Fetch the data and build select list
  */
  public function getInstitutionList($index_item) {
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
    } else {
      $title = $hei_list[$hei_id];

      $data = \Drupal::service('ewp_institutions_get.json')
        ->toArray($json_data);

      $show_empty = FALSE;

      $message = \Drupal::service('ewp_institutions_get.format')
        ->preview($title, $data, $hei_id, $show_empty);
    }

    $form['header']['messages']['#markup'] = render($message);

    return $form['header']['messages'];
  }

}
