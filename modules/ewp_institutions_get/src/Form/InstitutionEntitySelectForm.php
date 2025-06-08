<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Element\StatusMessages;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alternative for Institution Add form.
 *
 * @ingroup ewp_institutions
 */
class InstitutionEntitySelectForm extends PreviewForm {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);

    $instance->account           = $container->get('current_user');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->renderer          = $container->get('renderer');

    return $instance;
  }

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
    // Give a user with permission the opportunity to add an entity manually.
    if ($this->account->hasPermission('bypass import institution entities')) {
      $add_link = Link::fromTextAndUrl($this->t('add a new Institution'),
        Url::fromRoute('entity.hei.add_form'))->toString();

      $warning = $this->t('You can bypass this form and @add_link manually.', [
        '@add_link' => $add_link,
      ]);

      $form['messages'] = [
        '#type' => 'markup',
        '#markup' => $warning,
        '#weight' => '-20',
      ];
    }

    // Build the form header with the AJAX components.
    $form['header'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select an Institution to import'),
      '#weight' => '-10',
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
      '#type' => 'fieldset',
      '#title' => $this->t('Data'),
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
      '#markup' => '<p><em>' . $this->t('Nothing to display.') . '</em></p>',
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
        ],
      ],
    ];

    $form['actions']['load'] = [
      '#type' => 'submit',
      '#submit' => ['::loadImportForm'],
      '#value' => $this->t('Load Import form'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');
    $hei_id = $form_state->getValue('hei_select');

    if (empty($index_item)) {
      $error = $this->t('Select an entry from the Index.');
      $target = 'index_select';
    }
    elseif (empty($hei_id)) {
      $error = $this->t('Select an Institution from the list.');
      $target = 'hei_select';
    }

    if (!empty($error) && !empty($target)) {
      $form_state->setErrorByName($target, $error);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');
    $hei_id = $form_state->getValue('hei_select');

    $form_state->setRedirect('entity.hei.auto_import', [
      'index_key' => $index_item,
      'hei_key' => $hei_id,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function loadImportForm(array &$form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');
    $hei_id = $form_state->getValue('hei_select');

    $form_state->setRedirect('entity.hei.import_form', [
      'index_key' => $index_item,
      'hei_key' => $hei_id,
    ]);
  }

  /**
   * Fetch the data and build select list.
   */
  public function getInstitutionList(array $form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');

    $endpoint = ($index_item) ? $this->indexLinks[$index_item] : '';

    $options = ['' => '- None -'];

    if (!empty($endpoint)) {
      $json_data = $this->jsonDataFetcher
        ->getUpdated($index_item, $endpoint);

      if ($json_data) {
        // Build the options list.
        $options += $this->jsonDataProcessor
          ->idLabel($json_data);
      }
    }

    $form['header']['hei_select']['#options'] = $options;

    return $form['header']['hei_select'];
  }

  /**
   * Fetch the data and preview Institution.
   */
  public function previewInstitution(array $form, FormStateInterface $form_state) {
    $index_item = $form_state->getValue('index_select');

    $endpoint = ($index_item) ? $this->indexLinks[$index_item] : '';

    // JSON data has to be stored at this point per previous step.
    $json_data = $this->jsonDataFetcher
      ->load($index_item, $endpoint);

    $hei_list = $this->jsonDataProcessor
      ->idLabel($json_data);

    $hei_id = $form_state->getValue('hei_select');

    // Check if an entity with the same hei_id already exists.
    $exists = $this->entityTypeManager->getStorage('hei')
      ->loadByProperties(['hei_id' => $hei_id]);

    if (!empty($exists)) {
      foreach ($exists as $hei) {
        $code = ['#markup' => '<code>' . $hei_id . '</code>'];
        $link = $hei->toLink();
        $renderable = $link->toRenderable();
      }

      $error = $this->t('Institution with ID @hei_id already exists: @link', [
        '@hei_id' => $this->renderer->render($code),
        '@link' => $this->renderer->render($renderable),
      ]);

      $this->messenger->addError($error);

      $message = StatusMessages::renderMessages();

      $status = $hei_id;
    }
    else {
      $title = $hei_list[$hei_id];

      $data = $this->jsonDataProcessor
        ->toArray($json_data);

      $show_empty = FALSE;

      $message = $this->dataFormatter
        ->preview($title, $data, $hei_id, $show_empty);

      $status = '';
    }

    $form['data']['preview']['#markup'] = $this->renderer->render($message);
    $form['data']['status']['#value'] = $status;

    return $form['data'];
  }

}
