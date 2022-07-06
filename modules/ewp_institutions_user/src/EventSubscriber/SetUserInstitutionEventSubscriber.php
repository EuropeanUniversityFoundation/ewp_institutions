<?php

namespace Drupal\ewp_institutions_user\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ewp_institutions_user\Event\SetUserInstitutionEvent;
use Drupal\ewp_institutions_user\InstitutionUserBridge;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EWP Institutions Set User Institution event subscriber.
 */
class SetUserInstitutionEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The Institution User Bridge service.
   *
   * @var \Drupal\ewp_institutions_user\InstitutionUserBridge
   */
  protected $bridge;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\ewp_institutions_user\InstitutionUserBridge $bridge
   *   The Institution User Bridge service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    InstitutionUserBridge $bridge,
    MessengerInterface $messenger,
    TranslationInterface $string_translation
  ) {
    $this->bridge            = $bridge;
    $this->messenger         = $messenger;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SetUserInstitutionEvent::EVENT_NAME => ['onSetUserInstitution'],
    ];
  }

  /**
   * Subscribe to the user institution change event dispatched.
   *
   * @param \Drupal\ewp_institutions_user\Event\SetUserInstitutionEvent $event
   *   The event object.
   */
  public function onSetUserInstitution(SetUserInstitutionEvent $event) {
    if (empty($event->hei)) {
      $message = $this->t('Unsetting Institutions for user %user...', [
        '%user' => $event->user->label()
      ]);

      $this->messenger->addWarning($message);
    }
    else {
      $hei = [];

      foreach ($event->hei as $idx => $entity) {
        $hei[] = $entity->label();
      }

      $message = $this->t('Setting Institutions %hei for user %user...', [
        '%user' => $event->user->label(),
        '%hei' => \implode(', ', $hei)
      ]);

      $this->messenger->addStatus($message);
    }

    $this->bridge->setUserInstitution($event->user, $event->hei);
  }

}
