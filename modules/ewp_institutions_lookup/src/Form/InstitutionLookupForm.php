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
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ewp_institutions_get\InstitutionManager;
use Drupal\ewp_institutions_lookup\InstitutionLookupManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InstitutionLookupForm extends FormBase {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\ewp_institutions_lookup\InstitutionLookupManager $lookup_manager
   *   Institution lookup service.
   * @param \Drupal\ewp_institutions_get\InstitutionManager $hei_manager
   *   Institution entity manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    AccountProxyInterface $account,
    ConfigFactoryInterface $config_factory,
    InstitutionLookupManager $lookup_manager,
    InstitutionManager $hei_manager,
    MessengerInterface $messenger,
    RendererInterface $renderer
  ) {
    $this->account       = $account;
    $this->lookupManager = $lookup_manager;
    $this->heiManager    = $hei_manager;
    $this->messenger     = $messenger;
    $this->renderer      = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('ewp_institutions_lookup.manager'),
      $container->get('ewp_institutions_get.manager'),
      $container->get('messenger'),
      $container->get('renderer'),
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
        '@link' => $this->renderer->render($renderable),
      ]);
      $this->messenger->addWarning($warning);
    }
    else {
      $lookup = $this->lookupManager->lookup($hei_id);

      if (! empty($lookup)) {
        if ($this->account->hasPermission('add institution entities')) {
          $import_link = $this->lookupManager->importLink($hei_id)->toString();
        }
        $success = $this->t('Institution found. @link', [
          '@link' => $import_link ?? ''
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
