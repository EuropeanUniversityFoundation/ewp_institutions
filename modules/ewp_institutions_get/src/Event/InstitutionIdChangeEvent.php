<?php

namespace Drupal\ewp_institutions_get\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\ewp_institutions\Entity\InstitutionEntity;
use Drupal\ewp_institutions_get\InstitutionManager;

/**
 * Event that is fired when an institution's ID changes.
 */
class InstitutionIdChangeEvent extends Event {

  const EVENT_NAME = 'hei_id_change';

  /**
   * The Institution entity.
   *
   * @var \Drupal\ewp_institutions\Entity\InstitutionEntity
   */
  public $hei;

  /**
   * The previous value.
   *
   * @var string
   */
  public $previous;

  /**
   * The current value.
   *
   * @var string
   */
  public $current;

  /**
   * Constructs the object.
   *
   * @param \Drupal\ewp_institutions\Entity\InstitutionEntity $hei
   *   The Institution entity.
   */
  public function __construct(InstitutionEntity $hei) {
    $this->hei = $hei;

    $this->previous = $this->hei->original
      ->get(InstitutionManager::UNIQUE_FIELD)
      ->getValue()[0]['value'];

    $this->current = $this->hei
      ->get(InstitutionManager::UNIQUE_FIELD)
      ->getValue()[0]['value'];
  }

}
