<?php

namespace Drupal\ewp_institutions_user;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\user\Entity\User;
use Drupal\ewp_institutions\Entity\InstitutionEntity;

/**
 * EWP Institutions User form alter service.
 */
class InstitutionUserFormAlter {

  use StringTranslationTrait;

  const HANDLER = 'ewp_institutions_user';

  const ENTITY_TYPE = InstitutionUserBridge::ENTITY_TYPE;
  const BASE_FIELD = InstitutionUserBridge::BASE_FIELD;

  const NEGATE = InstitutionUserBridge::NEGATE;
  const SHOW_ALL = InstitutionUserBridge::SHOW_ALL;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   A proxied implementation of AccountInterface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    AccountProxy $current_user,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    TranslationInterface $string_translation,
  ) {
    $this->currentUser       = $current_user;
    $this->configFactory     = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Alter the user form element according to permissions.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function userFormAlter(&$form, FormStateInterface $form_state) {
    // If the base field is in the user form, changes may be needed,.
    if (\array_key_exists(self::BASE_FIELD, $form)) {
      $current_user_id = $this->currentUser->id();
      $form_object = $form_state->getformObject();
      /** @var \Drupal\user\ProfileForm $form_object */
      $form_user_id = $form_object->getEntity()->id();

      // Determine whether the current user is allowed to set the value.
      $can_set_any = $this->currentUser
        ->hasPermission('set any user institution');
      $can_set_own = $this->currentUser
        ->hasPermission('set own user institution');
      $own_form = ($current_user_id === $form_user_id);

      $allowed = ($can_set_any || ($own_form && $can_set_own));

      // If not allowed, the form element must be altered.
      if (!$allowed) {
        // Hide the form element.
        $form[self::BASE_FIELD]['#type'] = 'hidden';
        // If required, remove the requirement.
        $form[self::BASE_FIELD]['widget']['#required'] = FALSE;
        foreach ($form[self::BASE_FIELD]['widget'] as $key => $value) {
          if (\is_numeric($key)) {
            $value['target_id']['#required'] = FALSE;
            $form[self::BASE_FIELD]['widget'][$key] = $value;
          }
        }

        // Prepare a placeholder for the hidden form element.
        $markup_empty = $this->t('%warning', [
          '%warning' => $this->t('Institution is not set.'),
        ]);
        $markup_items = '';

        foreach ($form[self::BASE_FIELD]['widget'] as $key => $value) {
          if (\is_numeric($key)) {
            $default_value = $value['target_id']['#default_value'];

            if (!empty($default_value)) {
              // Add a link to the target entity.
              $link = $default_value->toLink()->toString();
              $item = (!empty($markup_items)) ? '<br />' . $link : $link;
              $markup_items .= $item;
            }
          }
        }

        $markup = (empty($markup_items)) ? $markup_empty : $markup_items;

        // Build the new form element.
        $new_element = [
          '#type' => 'item',
          '#title' => $form[self::BASE_FIELD]['widget']['#title'],
          '#markup' => $markup,
          '#weight' => $form[self::BASE_FIELD]['#weight'],
        ];

        $form[self::BASE_FIELD . '_placeholder'] = $new_element;
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
          // Last widget is always empty when storage is unlimited.
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
        if ($cardinality === 1 && !$excess) {
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

  /**
   * Alter Institution reference autocomplete form element.
   *
   * @param array $elements
   *   The form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $context
   *   The context array.
   */
  public function autocompleteAlter(array &$elements, FormStateInterface $form_state, array $context) {
    $target_type = $elements['widget'][0]['target_id']['#target_type'];
    $handler = $elements['widget'][0]['target_id']['#selection_handler'];

    if ($target_type === self::ENTITY_TYPE && $handler === self::HANDLER) {
      // Get the current user.
      $user = $this->entityTypeManager
        ->getStorage('user')
        ->load($this->currentUser->id());

      // Get the referenced Institutions from the current user.
      $user_hei = $user->get(self::BASE_FIELD)->getValue();

      // Set a default value.
      $this->setDefault($elements, $user_hei);

      // Handle empty value in filtered autocomplete widgets.
      $this->handleEmpty($elements, $user, $user_hei);
    }
  }

  /**
   * Set a default value for an Institution reference autocomplete form element.
   *
   * @param array $elements
   *   The form elements.
   * @param array $user_hei
   *   The Institutions associated with the User.
   */
  protected function setDefault(array &$elements, array $user_hei) {
    $default_parents = \in_array('default_value_input', $elements['#parents']);
    $user_has_hei = !empty($user_hei[0]);
    $default_widget = $elements['widget'][0]['target_id']['#default_value'];

    if (!$default_parents && $user_has_hei && empty($default_widget)) {
      // Set a default value.
      $hei = InstitutionEntity::load($user_hei[0]['target_id']);

      $elements['widget'][0]['target_id']['#default_value'] = $hei;
    }
  }

  /**
   * Handle empty value for an Institution reference autocomplete form element.
   *
   * @param array $elements
   *   The form elements.
   * @param \Drupal\user\Entity\User $user
   *   The User entity.
   * @param array $user_hei
   *   The Institutions related to the User.
   */
  protected function handleEmpty(array &$elements, User $user, array $user_hei) {
    $settings = $elements['widget'][0]['target_id']['#selection_settings'];

    $show_all = $settings[self::SHOW_ALL];
    $negate = $settings[self::NEGATE];

    $allowed = $user->hasPermission('select any institution');

    if (empty($user_hei) && !$allowed && !$show_all && !$negate) {
      // Set an error message.
      $message = $this->t('No Institution available to reference.');

      // Indicate how serious this is.
      $required = $elements['widget']['#required'];
      $level = ($required) ? $this->t('Error') : $this->t('Warning');
      $description = '<strong>' . $level . '</strong>: ' . $message;

      // Alter the element regardless of cardinality.
      $elements['#disabled'] = TRUE;
      $elements['widget']['#description'] = $description;
      $elements['widget'][0]['target_id']['#description'] = $description;
    }
  }

}
