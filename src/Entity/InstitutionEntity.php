<?php

namespace Drupal\ewp_institutions\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Institution entity.
 *
 * @ingroup ewp_institutions
 *
 * @ContentEntityType(
 *   id = "hei",
 *   label = @Translation("Institution"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ewp_institutions\InstitutionEntityListBuilder",
 *     "views_data" = "Drupal\ewp_institutions\Entity\InstitutionEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\ewp_institutions\Form\InstitutionEntityForm",
 *       "add" = "Drupal\ewp_institutions\Form\InstitutionEntityForm",
 *       "edit" = "Drupal\ewp_institutions\Form\InstitutionEntityForm",
 *       "delete" = "Drupal\ewp_institutions\Form\InstitutionEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ewp_institutions\InstitutionEntityHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\ewp_institutions\InstitutionEntityAccessControlHandler",
 *   },
 *   base_table = "hei",
 *   translatable = FALSE,
 *   admin_permission = "administer institution entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/ewp/hei/{hei}",
 *     "add-form" = "/ewp/hei/add",
 *     "edit-form" = "/ewp/hei/{hei}/edit",
 *     "delete-form" = "/ewp/hei/{hei}/delete",
 *     "collection" = "/admin/ewp/hei/list",
 *   },
 *   field_ui_base_route = "hei.settings",
 *   common_reference_target = TRUE,
 * )
 */
class InstitutionEntity extends ContentEntityBase implements InstitutionEntityInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('label', $name);
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

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('The internal label of the Institution entity.'))
      ->setSettings([
        'max_length' => 255,
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

    /** @disregard P1013 */
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
