<?php

namespace Drupal\ewp_institutions_user\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\ewp_institutions_user\Event\UserInstitutionChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EWP Institutions User bridge event subscriber.
 */
class UserInstitutionChangeEventSubscriber implements EventSubscriberInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      UserInstitutionChangeEvent::EVENT_NAME => ['onUserInstitutionChange'],
    ];
  }

  /**
   * Subscribe to the user institution change event dispatched.
   *
   * @param \Drupal\ewp_institutions_user\Event\UserInstitutionChangeEvent $event
   *   The event object.
   */
  public function onUserInstitutionChange(UserInstitutionChangeEvent $event) {
    dpm($event);
  }

}
