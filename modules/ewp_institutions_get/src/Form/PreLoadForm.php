<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Temporary Storage
   */
  protected $tempStore;

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    // Load the settings.
    $config = \Drupal::config('ewp_institutions_get.settings');
    $this->indexEndpoint = $config->get('ewp_institutions_get.index_endpoint');
    $this->indexLinkKey = 'list';
    $this->indexLinks = [];
    $this->indexLabels = [];
    $this->tempStore = $temp_store_factory->get('ewp_institutions_get');

    if (! empty($this->indexEndpoint)) {
      // Initialize an HTTP client
      $client = \Drupal::httpClient();
      $response = NULL;

      // Build the HTTP request
      try {
        $request = $client->get($this->indexEndpoint);
        $response = $request->getBody();
      } catch (GuzzleException $e) {
        $response = $e->getResponse()->getBody();
      } catch (Exception $e) {
        watchdog_exception('ewp_institutions_get', $e->getMessage());
      }

      // Validate the response
      $validated = \Drupal::service('ewp_institutions_get.json')->validate($response);

      // Build the index and the item list
      if ($validated) {
        $this->indexLinks = \Drupal::service('ewp_institutions_get.json')->idLinks($response, $this->indexLinkKey);
        $this->indexLabels = \Drupal::service('ewp_institutions_get.json')->idLabel($response);
      }
    } else {
      $warning = $this->t("Index endpoint is not defined.");
      \Drupal::service('messenger')->addWarning($warning);
    }

  }

  // Uses Symfony's ContainerInterface to declare dependency to be passed to constructor
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore')
    );
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
  * Make the API call
  */
  public function getInstitutions(array $form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');
    $endpoint = $this->indexLinks[$index_item];

    // Initialize an HTTP client
    $client = \Drupal::httpClient();
    $response = NULL;

    if (! empty($endpoint)) {
      // Build the HTTP request
      try {
        $request = $client->get($endpoint);
        $response = $request->getBody();
      } catch (GuzzleException $e) {
        $response = $e->getResponse()->getBody();
      } catch (Exception $e) {
        watchdog_exception('ewp_institutions_get', $e->getMessage());
      }

      // Validate the response
      $validated = \Drupal::service('ewp_institutions_get.json')->validate($response);

      if ($validated) {
        $title = $this->indexLabels[$index_item];
        $columns = $this->columns;
        $show_attr = $this->showAttr;
        $message = \Drupal::service('ewp_institutions_get.json')->toTable($title, $response, $columns, $show_attr);
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
