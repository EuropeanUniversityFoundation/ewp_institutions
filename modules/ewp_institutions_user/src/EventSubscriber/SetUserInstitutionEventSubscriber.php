<?php

namespace Drupal\ewp_institutions_user\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\ewp_institutions_user\InstitutionUserBridge $bridge
   *   The Institution User Bridge service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    InstitutionUserBridge $bridge,
    LoggerChannelFactoryInterface $logger_factory,
    TranslationInterface $string_translation,
  ) {
    $this->bridge            = $bridge;
    $this->logger            = $logger_factory->get('ewp_institutions_user');
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
        '%user' => $event->user->label(),
      ]);

      $this->logger->notice($message);
    }
    else {
      $hei = [];

      foreach ($event->hei as $entity) {
        $hei[] = $entity->label();
      }

      $message = $this->t('Setting Institutions %hei for user %user...', [
        '%user' => $event->user->label(),
        '%hei' => \implode(', ', $hei),
      ]);

      $this->logger->notice($message);
    }

    $this->bridge->setUserInstitution($event->user, $event->hei, $event->save);
  }

}
