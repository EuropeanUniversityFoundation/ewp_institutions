<?php

namespace Drupal\ewp_institutions_user\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\ewp_institutions_user\InstitutionUserBridge as Bridge;

/**
 * Plugin description.
 *
 * @EntityReferenceSelection(
 *   id = "ewp_institutions_user",
 *   label = @Translation("Filter by Institution in user account."),
 *   group = "ewp_institutions_user",
 *   entity_types = {"hei"},
 *   weight = 0
 * )
 */
class HeiSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    $default_configuration = [
      Bridge::NEGATE => FALSE,
      Bridge::SHOW_ALL => FALSE,
    ];

    return $default_configuration + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form[Bridge::NEGATE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Negate the condition'),
      '#description' => $this->t('Shows Institutions that are NOT referenced.'),
      '#default_value' => $this->configuration[Bridge::NEGATE],
      '#return_value' => TRUE,
      '#weight' => -5
    ];

    $form[Bridge::SHOW_ALL] = [
      '#type' => 'checkbox',
      '#title' => $this->t('If empty, show all'),
      '#default_value' => $this->configuration[Bridge::SHOW_ALL],
      '#return_value' => TRUE,
      '#weight' => -4
    ];

    unset($form['auto_create']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Get the current user.
    $user = User::load($this->currentUser->id());

    // Skip when the user has permission to select any Institution.
    if ($user->hasPermission('select any institution')) {
      return $query;
    }

    // Get the referenced Institutions from the user account.
    $user_hei = $user->get(Bridge::BASE_FIELD)->getValue();

    // Skip when the user has no Institution but "show all" option is enabled.
    if (empty($user_hei) && $this->configuration[Bridge::SHOW_ALL]) {
      return $query;
    }

    // Gather the Institution entity IDs.
    $hei_entity_id = [];
    foreach ($user_hei as $idx => $array) {
      $hei_entity_id[] = $array['target_id'];
    }

    $operator = ($this->configuration[Bridge::NEGATE]) ? 'NOT IN' : 'IN';

    $query->condition('id', $hei_entity_id, $operator);

    return $query;
  }

}
