<?php

namespace Drupal\ewp_institutions_user\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;
use Drupal\ewp_institutions_user\InstitutionUserBridge;

/**
 * Event that is fired when a user's institution changes.
 */
class UserInstitutionChangeEvent extends Event {

  const EVENT_NAME = 'user_institution_change';

  /**
   * The user entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  public $user;

  /**
   * Array of Institution entities.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
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
   * @param \Drupal\Core\Entity\EntityInterface $user
   *   The user entity.
   */
  public function __construct(EntityInterface $user) {
    $this->user = $user;
    $this->hei = $user->get(InstitutionUserBridge::BASE_FIELD)
      ->referencedEntities();
    foreach ($this->hei as $idx => $entity) {
      $this->hei_id[] = $entity->get(InstitutionUserBridge::UNIQUE_FIELD)
        ->getValue()[0]['value'];
    }
  }

}
