<?php

namespace Drupal\ewp_institutions_user;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\Entity\BaseFieldOverride;
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
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   A proxied implementation of AccountInterface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    AccountProxy $current_user,
    ConfigFactoryInterface $config_factory,
    EntityFieldManagerInterface $entity_field_manager,
    TranslationInterface $string_translation
  ) {
    $this->currentUser        = $current_user;
    $this->configFactory      = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
    $this->stringTranslation  = $string_translation;
  }

  /**
   * Attach an entity reference as a base field.
   *
   * @return array $fields[]
  */
  public function attachBaseField(): array {
    $desc = $this->t('The Institution with which the User is associated.');

    $fields[self::BASE_FIELD] = BaseFieldDefinition::create('entity_reference')
      ->setLabel($this->t('Institution'))
      ->setDescription($desc)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', self::ENTITY_TYPE)
      ->setSetting('handler', 'default:' . self::ENTITY_TYPE)
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
   * Update a base field from config.
  */
  public function updateBaseField() {
    // Get the module config.
    $config = $this->configFactory->get('ewp_institutions_user.settings');

    // Get the existing field config.
    $fields = $this->entityFieldManager->getFieldDefinitions('user', 'user');

    // Define the BaseFieldOverride.
    if ($fields[self::BASE_FIELD] instanceof BaseFieldOverride) {
      $override = $fields[self::BASE_FIELD];
    }
    else {
      $override = BaseFieldOverride::createFromBaseFieldDefinition(
        $fields[self::BASE_FIELD], 'user'
      );
    }

    // Update required if needed.
    $current_required = $override->get('required');

    if ($current_required !== $config->get('required')) {
      $override->setRequired($config->get('required'));
    }

    // Update auto create if needed.
    $current_handler_settings = $override->getSettings()['handler_settings'];

    if (\array_key_exists('auto_create', $current_handler_settings)) {
      $current_auto_create = $current_handler_settings['auto_create'];
    } else {
      $current_auto_create = FALSE;
    }

    if ($current_auto_create !== $config->get('auto_create')) {
      $override->setSetting('handler_settings', [
        'auto_create' => $config->get('auto_create')
      ]);
    }

    $override->save();
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

      // Otherwise cardinality from config must be enforced.
      else {
        // Get the module config.
        $config = $this->configFactory->get('ewp_institutions_user.settings');
        $cardinality = $config->get('cardinality');

        $widget = $form[self::BASE_FIELD]['widget'];
        $widget['#cardinality'] = $cardinality;

        // Handle limited cardinality.
        $excess = FALSE;

        if ($cardinality > 0) {
          // Last widget is always empty when storage cardinality is unlimited.
          $empty = $widget[$widget['#max_delta']];

          // Number of widgets is less than or equal to cardinality.
          if ($widget['#max_delta'] < $cardinality) {
            for ($d = $widget['#max_delta']; $d < $cardinality; $d++) {
              $widget[$d] = $empty;
              $widget[$d]['target_id']['#delta'] = $d;
              $widget[$d]['target_id']['#weight'] = $d;
            }

            $widget['#max_delta'] = $cardinality - 1;
          }
          else {
            // Number of widgets exceeds cardinality, delete last.
            unset($widget[$widget['#max_delta']]);

            if ($widget['#max_delta'] > $cardinality) {
              $excess = TRUE;
              for ($d = $cardinality; $d < $widget['#max_delta']; $d++) {
                $widget[$d]['target_id']['#attributes']['class'][] = 'error';
              }
              // Populated widgets must still be shown.
              $widget['#max_delta'] = $widget['#max_delta'] - 1;

              // Display a warning before the description.
              $text = $this->t('WARNING: Too many values!');
              $warning = '<p><strong>' . $text . '</strong></p>';
              $widget['#description'] = $warning . $widget['#description'];
            }
          }

          // Remove unnecessary parts.
          unset($widget['add_more']);
        }

        // Handle single value.
        if ($cardinality === 1 && ! $excess) {
          $widget['#cardinality_multiple'] = FALSE;

          // Copy title and description to the individual widget.
          $widget[0]['target_id']['#title'] = $widget['#title'];
          $widget[0]['target_id']['#title_display'] = 'before';
          $widget[0]['target_id']['#description'] = $widget['#description'];

          // Remove unnecessary parts.
          unset($widget['#prefix']);
          unset($widget['#suffix']);
          unset($widget[0]['_weight']);
        }

        $form[self::BASE_FIELD]['widget'] = $widget;
      }
    }
  }

}
