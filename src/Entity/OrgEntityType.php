<?php

namespace Drupal\ewp_institutions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Organization type entity.
 *
 * @ConfigEntityType(
 *   id = "org_type",
 *   label = @Translation("Organization type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ewp_institutions\OrgEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ewp_institutions\Form\OrgEntityTypeForm",
 *       "edit" = "Drupal\ewp_institutions\Form\OrgEntityTypeForm",
 *       "delete" = "Drupal\ewp_institutions\Form\OrgEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ewp_institutions\OrgEntityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "org_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "org",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/ewp/org_type/{org_type}",
 *     "add-form" = "/admin/ewp/org_type/add",
 *     "edit-form" = "/admin/ewp/org_type/{org_type}/edit",
 *     "delete-form" = "/admin/ewp/org_type/{org_type}/delete",
 *     "collection" = "/admin/ewp/org_type"
 *   }
 * )
 */
class OrgEntityType extends ConfigEntityBundleBase implements OrgEntityTypeInterface {

  /**
   * The Organization type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Organization type label.
   *
   * @var string
   */
  protected $label;

}
