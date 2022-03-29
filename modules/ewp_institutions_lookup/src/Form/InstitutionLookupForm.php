<?php

namespace Drupal\ewp_institutions_lookup\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ewp_institutions_lookup\InstitutionLookupManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InstitutionLookupForm extends FormBase {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Institution lookup service.
   *
   * @var \Drupal\ewp_institutions_lookup\InstitutionLookupManager
   */
  protected $lookupManager;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\ewp_institutions_lookup\InstitutionLookupManager $lookup_manager
   *   Institution lookup service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    InstitutionLookupManager $lookup_manager
  ) {
    $this->configFactory = $config_factory;
    $this->lookupManager = $lookup_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('ewp_institutions_lookup.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ewp_institutions_lookup_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Default settings.
    $config = $this->config('ewp_institutions_lookup.settings');

    $result = $this->lookupManager->lookup('ua.pt');

    dpm($result);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return parent::submitForm($form, $form_state);
  }
}
