<?php

namespace Drupal\ewp_institutions_user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\user\Entity\User;
use Drupal\ewp_institutions\Entity\InstitutionEntity;
use Drupal\ewp_institutions_user\Event\SetUserInstitutionEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to trigger an Event.
 */
class TriggerSetEventForm extends FormBase {

  /**
   * The current user entity.
   */
  protected $currentUser;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
  * The constructor.
  *
  * @param \Drupal\Core\Session\AccountProxy $current_user
  *   A proxied implementation of AccountInterface.
  * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
  *   The event dispatcher service.
   */
  public function __construct(
    AccountProxy $current_user,
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->currentUser     = User::load($current_user->id());
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('event_dispatcher'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_user_trigger_set_event';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['intro'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Use this form to trigger a %event.', [
        '%event' => 'SetUserInstitutionEvent'
      ]) . '</p>',
    ];

    $form['user'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('User entity'),
      '#description' => $this->t('Only your own account can be changed.'),
      '#default_value' => $this->currentUser,
      '#target_type' => 'user',
      '#selection_handler' => 'default',
      '#attributes' => [
        'readonly' => TRUE
      ],
    ];

    $form['hei'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Institution entities'),
      '#description' => $this->t('Multiple selection is allowed.'),
      '#tags' => TRUE,
      '#target_type' => 'hei',
      '#selection_handler' => 'default',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Trigger Set Event'),
      '#attributes' => [
        'class' => [
          'button--primary',
        ]
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->currentUser;
    $hei = [];

    if ($form_state->getValue('hei')) {
      foreach ($form_state->getValue('hei') as $idx => $array) {
        $hei[] = InstitutionEntity::load($array['target_id']);
      }
    }

    // Instantiate our event.
    $event = new SetUserInstitutionEvent($user, $hei);
    // Dispatch the event.
    $this->eventDispatcher
      ->dispatch($event, SetUserInstitutionEvent::EVENT_NAME);
  }

}
