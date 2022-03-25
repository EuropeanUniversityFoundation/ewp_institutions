<?php

namespace Drupal\ewp_institutions_user;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * EWP Institutions User bridge service.
 */
class InstitutionUserBridge {

  use StringTranslationTrait;

  const ENTITY_TYPE = 'hei';
  const BASE_FIELD = 'user_institution';

  /**
   * The current user.
   */
  protected $currentUser;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   A proxied implementation of AccountInterface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    AccountProxy $current_user,
    ConfigFactoryInterface $config_factory,
    TranslationInterface $string_translation
  ) {
    $this->currentUser        = $current_user;
    $this->configFactory      = $config_factory;
    $this->stringTranslation  = $string_translation;
  }

  /**
   * Attach an entity reference as a base field.
   *
   * @return array $fields[]
  */
  public function attachBaseField(): array {
    $fields[self::BASE_FIELD] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Institution'))
      ->setDescription($this->t('The Institution with which the User is associated.'))
      ->setSetting('target_type', self::ENTITY_TYPE)
      ->setSetting('handler', 'default:' . self::ENTITY_TYPE)
      // ->setSetting('handler_settings', [
      //   'auto_create' => FALSE,
      // ])
      // ->setCardinality(1)
      // ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Alters the form element according to permissions.
   *
   * @param array $form
   * @param Drupal\Core\Form\FormStateInterface $form_state
   */
  public function userFormAlter(&$form, FormStateInterface $form_state) {
    // If the base field is in the user form, changes may be needed,
    if (\array_key_exists(self::BASE_FIELD, $form)) {
      $current_user_id = $this->currentUser->id();
      $form_user_id = $form_state->getformObject()->getEntity()->id();

      // Determine whether the current user is allowed to set the value.
      $allowed = (
        $this->currentUser
          ->hasPermission('set any user institution', $this->currentUser) ||
        (
          $current_user_id == $form_user_id &&
          $this->currentUser
            ->hasPermission('set own user institution', $this->currentUser)
        )
      );

      // If not allowed, the form element must be replaced with links.
      if (! $allowed) {
        $markup = '';

        foreach ($form[self::BASE_FIELD]['widget'] as $key => $value) {
          if (\is_numeric($key)) {
            $default_value = $value['target_id']['#default_value'];

            if (!empty($default_value)) {
              // Add a link to the target entity.
              $markup .= '<p>' . $default_value->toLink()->toString() . '</p>';
            }
          }
        }

        // Build the new form element.
        $new_element = [
          '#type' => 'item',
          '#title' => $form[self::BASE_FIELD]['widget']['#title'],
          '#markup' => $markup,
        ];

        $form[self::BASE_FIELD] = $new_element;
      }
    }
  }

}
