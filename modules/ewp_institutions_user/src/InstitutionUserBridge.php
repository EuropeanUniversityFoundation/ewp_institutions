<?php

namespace Drupal\ewp_institutions_user;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\ewp_institutions\Entity\InstitutionEntity;
use Drupal\ewp_institutions_user\Event\UserInstitutionChangeEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * EWP Institutions User bridge service.
 */
class InstitutionUserBridge {

  use StringTranslationTrait;

  const ENTITY_TYPE = 'hei';
  const UNIQUE_FIELD = 'hei_id';
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
   * Event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   A proxied implementation of AccountInterface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    AccountProxy $current_user,
    ConfigFactoryInterface $config_factory,
    EntityFieldManagerInterface $entity_field_manager,
    EventDispatcherInterface $event_dispatcher,
    TranslationInterface $string_translation
  ) {
    $this->currentUser        = $current_user;
    $this->configFactory      = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
    $this->eventDispatcher    = $event_dispatcher;
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
   * Check for changes in the user entity to dispatch an event.
   *
   * @param \Drupal\user\UserInterface $user
   */
  public function checkInstitutionChange(UserInterface $user) {
    $old_value = (empty($user->original)) ? NULL : $user->original
      ->get(self::BASE_FIELD)->getValue();
    $new_value = $user
      ->get(self::BASE_FIELD)->getValue();

    if ($old_value !== $new_value) {
      // Instantiate our event.
      $event = new UserInstitutionChangeEvent($user);
      // Dispatch the event.
      $this->eventDispatcher
        ->dispatch($event, UserInstitutionChangeEvent::EVENT_NAME);
    }
  }

  /**
   * Set entity reference to Institutions on a user entity.
   *
   * @param \Drupal\user\UserInterface $user
   * @param \Drupal\ewp_institutions\entity\InstitutionEntity[] $hei
   */
  public function setUserInstitution(UserInterface $user, array $hei) {
    $target_id = [];
    $base_field = self::BASE_FIELD;

    foreach ($hei as $idx => $entity) {
      $target_id[] = ['target_id' => $entity->id()];
    }

    $user->$base_field = $target_id;
    $user->save();
  }

}
