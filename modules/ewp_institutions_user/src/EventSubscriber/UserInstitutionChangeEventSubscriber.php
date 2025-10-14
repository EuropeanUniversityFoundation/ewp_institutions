<?php

namespace Drupal\ewp_institutions_user\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    TranslationInterface $string_translation,
  ) {
    $this->logger            = $logger_factory->get('ewp_institutions_user');
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
    if (empty($event->heiId)) {
      $message = $this->t('User %user is not associated with an Institution.', [
        '%user' => $event->user->label(),
      ]);

      $this->logger->notice($message);
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

      $this->logger->notice($message);
    }
  }

}
