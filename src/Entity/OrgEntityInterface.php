<?php

namespace Drupal\ewp_institutions\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Organization entities.
 *
 * @ingroup ewp_institutions
 */
interface OrgEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Organization name.
   *
   * @return string
   *   Name of the Organization.
   */
  public function getName();

  /**
   * Sets the Organization name.
   *
   * @param string $name
   *   The Organization name.
   *
   * @return \Drupal\ewp_institutions\Entity\OrgEntityInterface
   *   The called Organization entity.
   */
  public function setName($name);

  /**
   * Gets the Organization creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Organization.
   */
  public function getCreatedTime();

  /**
   * Sets the Organization creation timestamp.
   *
   * @param int $timestamp
   *   The Organization creation timestamp.
   *
   * @return \Drupal\ewp_institutions\Entity\OrgEntityInterface
   *   The called Organization entity.
   */
  public function setCreatedTime($timestamp);

}
