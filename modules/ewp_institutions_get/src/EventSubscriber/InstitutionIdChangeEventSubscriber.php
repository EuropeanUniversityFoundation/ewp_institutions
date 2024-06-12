<?php

namespace Drupal\ewp_institutions_get\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ewp_institutions_get\Event\InstitutionIdChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Institution ID Change event subscriber.
 */
class InstitutionIdChangeEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    RendererInterface $renderer,
    TranslationInterface $string_translation
  ) {
    $this->logger            = $logger_factory->get('ewp_institutions_get');
    $this->renderer          = $renderer;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      InstitutionIdChangeEvent::EVENT_NAME => ['onInstitutionIdChange'],
    ];
  }

  /**
   * Subscribe to the institution ID change event dispatched.
   *
   * @param \Drupal\ewp_institutions_get\Event\InstitutionIdChangeEvent $event
   *   The event object.
   */
  public function onInstitutionIdChange(InstitutionIdChangeEvent $event) {
    if (!empty($event->previous)) {
      $renderable = $event->hei->toLink()->toRenderable();

      $message = $this->t('@hei ID changed from %previous to %current.', [
        '@hei' => $this->renderer->render($renderable),
        '%previous' => $event->previous,
        '%current' => $event->current,
      ]);

      $this->logger->notice($message);
    }
  }

}
