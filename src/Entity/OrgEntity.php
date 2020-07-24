<?php

namespace Drupal\ewp_institutions\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Organization entity.
 *
 * @ingroup ewp_institutions
 *
 * @ContentEntityType(
 *   id = "org",
 *   label = @Translation("Organization"),
 *   bundle_label = @Translation("Organization type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ewp_institutions\OrgEntityListBuilder",
 *     "views_data" = "Drupal\ewp_institutions\Entity\OrgEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\ewp_institutions\Form\OrgEntityForm",
 *       "add" = "Drupal\ewp_institutions\Form\OrgEntityForm",
 *       "edit" = "Drupal\ewp_institutions\Form\OrgEntityForm",
 *       "delete" = "Drupal\ewp_institutions\Form\OrgEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ewp_institutions\OrgEntityHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\ewp_institutions\OrgEntityAccessControlHandler",
 *   },
 *   base_table = "org",
 *   translatable = FALSE,
 *   permission_granularity = "bundle",
 *   admin_permission = "administer organization entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/ewp/org/{org}",
 *     "add-page" = "/ewp/org/add",
 *     "add-form" = "/ewp/org/add/{org_type}",
 *     "edit-form" = "/ewp/org/{org}/edit",
 *     "delete-form" = "/ewp/org/{org}/delete",
 *     "collection" = "/admin/ewp/org/list",
 *   },
 *   bundle_entity_type = "org_type",
 *   field_ui_base_route = "entity.org_type.edit_form"
 * )
 */
class OrgEntity extends ContentEntityBase implements OrgEntityInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Organization entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -20,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 20,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
