<?php

namespace Drupal\ewp_institutions_lookup\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\Core\Render\RendererInterface;
use Drupal\ewp_institutions_get\InstitutionManager;
use Drupal\ewp_institutions_lookup\InstitutionLookupManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InstitutionLookupForm extends FormBase {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Institution lookup service.
   *
   * @var \Drupal\ewp_institutions_lookup\InstitutionLookupManager
   */
  protected $lookupManager;

  /**
   * Institution entity manager.
   *
   * @var \Drupal\ewp_institutions_get\InstitutionManager
   */
  protected $heiManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\ewp_institutions_lookup\InstitutionLookupManager $lookup_manager
   *   Institution lookup service.
   * @param \Drupal\ewp_institutions_get\InstitutionManager $hei_manager
   *   Institution entity manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    InstitutionLookupManager $lookup_manager,
    InstitutionManager $hei_manager,
    MessengerInterface $messenger
  ) {
    $this->configFactory = $config_factory;
    $this->lookupManager = $lookup_manager;
    $this->heiManager    = $hei_manager;
    $this->messenger     = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('ewp_institutions_lookup.manager'),
      $container->get('ewp_institutions_get.manager'),
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_lookup_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $result = $this->lookupManager->lookup('ua.pt');
    $form['hei_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Institution ID to lookup'),
      '#description' => $this->t('Format') . ': <code>domain.tld</code>',
      '#weight' => '-10',
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => '-8',
    ];

    $form['actions']['lookup'] = [
      '#type' => 'button',
      '#value' => $this->t('Lookup'),
      '#attributes' => [
        'class' => [
          'button--primary',
        ]
      ],
      '#states' => [
        'disabled' => [
          ':input[name="hei_id"]' => ['value' => ''],
        ],
      ],
      '#ajax' => [
        'callback' => '::lookup',
      ],
    ];

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<div class="result_message"></div>',
      '#weight' => '-6',
    ];

    $form['output'] = [
      '#type' => 'markup',
      '#markup' => '<div class="result_output"></div>',
      '#weight' => '-4',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return parent::submitForm($form, $form_state);
  }

  /**
   * AJAX callback to lookup an Institution by a given ID.
   */
  public function lookup(array &$form, FormStateInterface $form_state) {
    $hei_id = $form_state->getValue('hei_id');

    $output = '';

    $exists = $this->heiManager->getInstitution($hei_id);

    if (! empty($exists)) {
      foreach ($exists as $id => $hei) {
        $renderable = $hei->toLink()->toRenderable();
      }
      $warning = $this->t('Institution already exists: @link', [
        '@link' => RendererInterface::render($renderable),
      ]);
      $this->messenger->addWarning($warning);
    }
    else {
      $lookup = $this->lookupManager->lookup($hei_id);

      if (! empty($lookup)) {
        $import_link = $this->lookupManager->importLink($hei_id);
        $success = $this->t('Institution found. @link', [
          '@link' => $import_link->toString()
        ]);
        $this->messenger->addMessage($success);

        $output = $this->lookupManager->preview($hei_id);
      }
      else {
        $error = $this->t('No Institution found in lookup.');
        $this->messenger->addError($error);
      }
    }

    $message = StatusMessages::renderMessages();

    $ajax_response = new AjaxResponse();
    $ajax_response
      ->addCommand(new HtmlCommand('.result_message', $message))
      ->addCommand(new HtmlCommand('.result_output', $output));
    return $ajax_response;
  }
}
