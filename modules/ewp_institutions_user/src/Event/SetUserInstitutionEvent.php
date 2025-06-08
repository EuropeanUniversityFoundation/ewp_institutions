<?php

namespace Drupal\ewp_institutions_user\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\user\UserInterface;

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
   * Whether the user entity should be saved after setting the value.
   *
   * @var bool
   */
  public $save;

  /**
   * Constructs the object.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param \Drupal\ewp_institutions\entity\InstitutionEntity[] $hei
   *   Array of Institution entities.
   * @param bool $save
   *   Whether the user entity should be saved after setting the value.
   */
  public function __construct(UserInterface $user, array $hei, $save = TRUE) {
    $this->user = $user;
    $this->hei = $hei;
    $this->save = $save;
  }

}
