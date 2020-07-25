<?php

namespace Drupal\ewp_institutions\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Institution entities.
 *
 * @ingroup ewp_institutions
 */
interface InstitutionEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Institution name.
   *
   * @return string
   *   Name of the Institution.
   */
  public function getName();

  /**
   * Sets the Institution name.
   *
   * @param string $name
   *   The Institution name.
   *
   * @return \Drupal\ewp_institutions\Entity\InstitutionEntityInterface
   *   The called Institution entity.
   */
  public function setName($name);

  /**
   * Gets the Institution creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Institution.
   */
  public function getCreatedTime();

  /**
   * Sets the Institution creation timestamp.
   *
   * @param int $timestamp
   *   The Institution creation timestamp.
   *
   * @return \Drupal\ewp_institutions\Entity\InstitutionEntityInterface
   *   The called Institution entity.
   */
  public function setCreatedTime($timestamp);

}
