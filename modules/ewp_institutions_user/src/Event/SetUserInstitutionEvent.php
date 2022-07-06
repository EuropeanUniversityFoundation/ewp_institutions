<?php

namespace Drupal\ewp_institutions_user\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\user\UserInterface;
use Drupal\ewp_institutions_user\InstitutionUserBridge;

/**
 * Event that is fired when a user's institution must be set.
 */
class SetUserInstitutionEvent extends Event {

  const EVENT_NAME = 'set_user_institution';

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
   * Constructs the object.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param \Drupal\ewp_institutions\entity\InstitutionEntity[] $hei
   *   Array of Institution entities.
   */
  public function __construct(UserInterface $user, array $hei) {
    $this->user = $user;
    $this->hei = $hei;
  }

}
