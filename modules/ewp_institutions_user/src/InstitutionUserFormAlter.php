<?php

namespace Drupal\ewp_institutions_user;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\user\Entity\User;
use Drupal\ewp_institutions\Entity\InstitutionEntity;
use Drupal\ewp_institutions_user\InstitutionUserBridge;

/**
 * EWP Institutions User form alter service.
 */
class InstitutionUserFormAlter {

  use StringTranslationTrait;

  const ENTITY_TYPE = InstitutionUserBridge::ENTITY_TYPE;
  const BASE_FIELD = InstitutionUserBridge::BASE_FIELD;

  const NEGATE = InstitutionUserBridge::NEGATE;
  const SHOW_ALL = InstitutionUserBridge::SHOW_ALL;

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
   * Alter the user form element according to permissions.
   *
   * @param array $form
   * @param Drupal\Core\Form\FormStateInterface $form_state
   */
  public function userFormAlter(&$form, FormStateInterface $form_state) {
    if (!$this->currentUser->isAnonymous()) {
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
          $markup_empty = $this->t('%warning', [
            '%warning' => $this->t('Institution is not set.')
          ]);
          $markup_value = '';

          foreach ($form[self::BASE_FIELD]['widget'] as $key => $value) {
            if (\is_numeric($key)) {
              $default_value = $value['target_id']['#default_value'];

              if (!empty($default_value)) {
                // Add a link to the target entity.
                $link = $default_value->toLink()->toString();
                $markup_value .= '<p>' . $link . '</p>';
              }
            }
          }

          $markup = (empty($markup_value)) ? $markup_empty : $markup_value;

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
    else {
      // Hide the element in the registration form.
      $form[self::BASE_FIELD]['#type'] = 'hidden';
      // If required, remove the requirement it in the registration form.
      $form[self::BASE_FIELD]['widget']['#required'] = FALSE;
      foreach ($form[self::BASE_FIELD]['widget'] as $key => $value) {
        if (\is_numeric($key)) {
          $value['target_id']['#required'] = FALSE;
          $form[self::BASE_FIELD]['widget'][$key] = $value;
        }
      }
    }
  }

  /**
   * Alter Institution reference autocomplete form element.
   *
   * @param array $elements
   * @param Drupal\Core\Form\FormStateInterface $form_state
   * @param array $context
   */
  public function autocompleteAlter(array &$elements, FormStateInterface $form_state, array $context) {
    $target_type = $elements['widget'][0]['target_id']['#target_type'];
    $handler = $elements['widget'][0]['target_id']['#selection_handler'];
    $settings = $elements['widget'][0]['target_id']['#selection_settings'];

    if ($target_type === self::ENTITY_TYPE) {
      // Get the current user.
      $user = User::load($this->currentUser->id());
      // Get the referenced Institutions from the user account.
      $user_hei = $user->get(self::BASE_FIELD)->getValue();

      // Set a default value.
      $this->setDefault($elements, $user_hei);

      // Handle empty value in filtered autocomplete widgets.
      if ($handler === 'ewp_institutions_user') {
        $this->handleEmpty($elements, $user, $user_hei);
      }
    }
  }

  /**
   * Set a default value for an Institution reference autocomplete form element.
   *
   * @param array $elements
   * @param array $user_hei
   */
  protected function setDefault(array &$elements, array $user_hei) {
    if (
      ! \in_array('default_value_input', $elements['#parents']) &&
      ! empty($user_hei[0]) &&
      empty($elements['widget'][0]['target_id']['#default_value'])
    ) {
      // Set a default value.
      $hei = InstitutionEntity::load($user_hei[0]['target_id']);

      $elements['widget'][0]['target_id']['#default_value'] = $hei;
    }
  }

  /**
   * Handle empty value for an Institution reference autocomplete form element.
   *
   * @param array $elements
   * @param Drupal\user\Entity\User $user
   * @param array $user_hei
   */
  protected function handleEmpty(array &$elements, User $user, array $user_hei) {
    $settings = $elements['widget'][0]['target_id']['#selection_settings'];

    if (
      empty($user_hei) &&
      ! $user->hasPermission('select any institution') &&
      ! $settings[self::SHOW_ALL] &&
      ! $settings[self::NEGATE]
    ) {
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
