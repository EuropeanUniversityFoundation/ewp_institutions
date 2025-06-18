<?php

namespace Drupal\ewp_institutions_user\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ewp_institutions_user\Event\UserInstitutionChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EWP Institutions User Institution Change event subscriber.
 */
class UserInstitutionChangeEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

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
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    MessengerInterface $messenger,
    TranslationInterface $string_translation,
  ) {
    $this->messenger         = $messenger;
    $this->stringTranslation = $string_translation;
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
    if (empty($event->hei_id)) {
      $message = $this->t('User %user is not associated with an Institution.', [
        '%user' => $event->user->label(),
      ]);

      $this->messenger->addWarning($message);
    }
    else {
      $hei = [];

      foreach ($event->hei as $entity) {
        $hei[] = $entity->label();
      }

      $message = $this->t('User %user is associated with %hei.', [
        '%user' => $event->user->label(),
        '%hei' => \implode(', ', $hei),
      ]);

      $this->messenger->addStatus($message);
    }
  }

}
