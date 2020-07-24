<?php

namespace Drupal\ewp_institutions\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OrgEntityTypeForm.
 */
class OrgEntityTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $org_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $org_type->label(),
      '#description' => $this->t("Label for the Organization type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $org_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ewp_institutions\Entity\OrgEntityType::load',
      ],
      '#disabled' => !$org_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $org_type = $this->entity;
    $status = $org_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Organization type.', [
          '%label' => $org_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Organization type.', [
          '%label' => $org_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($org_type->toUrl('collection'));
  }

}
