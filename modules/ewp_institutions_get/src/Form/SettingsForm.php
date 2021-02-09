<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_get_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('ewp_institutions_get.settings');

    // Index endpoint field.
    $form['index_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Index endpoint'),
      '#default_value' => $config->get('ewp_institutions_get.index_endpoint'),
      '#description' => $this->t('External API endpoint that returns the main index.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ewp_institutions_get.settings');
    $config->set('ewp_institutions_get.index_endpoint', $form_state->getValue('index_endpoint'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ewp_institutions_get.settings',
    ];
  }

}
