<?php

namespace Drupal\ewp_institutions_user\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\user\UserInterface;
use Drupal\ewp_institutions_user\InstitutionUserBridge;

/**
 * Event that is fired when a user's institution changes.
 */
class UserInstitutionChangeEvent extends Event {

  const EVENT_NAME = 'user_institution_change';

  /**
   * The user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  public $user;

  /**
   * Array of Institution entities.
   *
   * @var \Drupal\ewp_institutions\Entity\InstitutionEntity[]
   */
  public $hei;

  /**
   * Array of Institution IDs.
   *
   * @var array
   */
  public $hei_id = [];

  /**
   * Constructs the object.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   */
  public function __construct(UserInterface $user) {
    $this->user = $user;
    /** @disregard P1013 */
    $this->hei = $user->get(InstitutionUserBridge::BASE_FIELD)
      ->referencedEntities();

    foreach ($this->hei as $entity) {
      $this->hei_id[] = $entity->get(InstitutionUserBridge::UNIQUE_FIELD)
        ->getValue()[0]['value'];
    }
  }

}
