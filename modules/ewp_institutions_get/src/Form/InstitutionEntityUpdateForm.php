<?php

namespace Drupal\ewp_institutions_get\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ewp_institutions\Form\InstitutionEntityForm;

/**
 * Alternative for Institution Edit form.
 *
 * @ingroup ewp_institutions
 */
class InstitutionEntityUpdateForm extends InstitutionEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\ewp_institutions\Entity\InstitutionEntity $entity */
    $form = parent::buildForm($form, $form_state);

    dpm($form);

    return $form;
  }

}
