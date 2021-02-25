<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

class PreLoadForm extends FormBase {

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
   * Additional columns for the data table
   *
   * @var array
   */
  protected $columns = [];

  /**
   * Attributes overview in the data table
   *
   * @var array
   */
  protected $showAttr = TRUE;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    // Load the settings.
    $config = \Drupal::config('ewp_institutions_get.settings');
    $this->indexEndpoint = $config->get('ewp_institutions_get.index_endpoint');
    $this->indexLinkKey = 'list';
    $this->indexLinks = [];
    $this->indexLabels = [];

    if (! empty($this->indexEndpoint)) {
      $json_data = \Drupal::service('ewp_institutions_get.fetch')->load('index', $this->indexEndpoint);

      if ($json_data) {
        $this->indexLinks = \Drupal::service('ewp_institutions_get.json')->idLinks($json_data, $this->indexLinkKey);
        $this->indexLabels = \Drupal::service('ewp_institutions_get.json')->idLabel($json_data);
      }
    } else {
      $warning = $this->t("Index endpoint is not defined.");
      \Drupal::service('messenger')->addWarning($warning);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_get_preload_form';
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
      '#weight' => '-9',
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => '-7',
    ];

    $form['actions']['get'] = [
      '#type' => 'button',
      '#value' => $this->t('GET Institutions'),
      '#attributes' => [
        'class' => [
          'button--primary',
        ]
      ],
      '#states' => [
        'disabled' => [
          ':input[name="index_select"]' => ['value' => ''],
        ],
      ],
      '#ajax' => [
        'callback' => '::getInstitutions',
      ],
    ];

    $form['data'] = [
      '#type' => 'markup',
      '#markup' => '<div class="response_data"></div>',
      '#weight' => '-6',
    ];

    return $form;
  }

  /**
  * Fetch the data and display as a table
  */
  public function getInstitutions(array $form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');
    $endpoint = $this->indexLinks[$index_item];

    if (! empty($endpoint)) {
      // Check when the index was last updated
      $index_updated = \Drupal::service('ewp_institutions_get.fetch')->checkUpdated('index');
      // Check when this item was last updated
      $item_updated = \Drupal::service('ewp_institutions_get.fetch')->checkUpdated($index_item);
      // Decide whether to force a refresh
      if ($item_updated && $index_updated < $item_updated) {
        $refresh = FALSE;
      } else {
        $refresh = TRUE;
      }

      $json_data = \Drupal::service('ewp_institutions_get.fetch')->load($index_item, $endpoint, $refresh);

      if ($json_data) {
        $title = $this->indexLabels[$index_item];
        $columns = $this->columns;
        $show_attr = $this->showAttr;
        $message = \Drupal::service('ewp_institutions_get.json')->toTable($title, $json_data, $columns, $show_attr);
      } else {
        $message = $this->t('Nothing to display.');
      }
    } else {
      $message = $this->t('This endpoint is not defined.');
    }

    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(
      new HtmlCommand('.response_data', $message));
    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here...
  }

}
