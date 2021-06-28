<?php

namespace Drupal\ewp_institutions_get\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ewp_institutions_get\InstitutionManager;

/**
 * Provides additional title callbacks for Institution entities:
 * - An add from external source title callback.
 * - An import title callback.
 */
class InstitutionEntityController extends EntityController {

  /**
   * The Institution manager service.
   *
   * @var \Drupal\ewp_institutions_get\InstitutionManager
   */
  protected $institutionManager;

  /**
   * Constructs an InstitutionEntityController object
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   * @param \Drupal\ewp_institutions_get\InstitutionManager $institution_manager
   *   The Institution manager service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityRepositoryInterface $entity_repository,
    RendererInterface $renderer,
    TranslationInterface $string_translation,
    UrlGeneratorInterface $url_generator,
    InstitutionManager $institution_manager
  ) {
    parent::__construct(
      $entity_type_manager,
      $entity_type_bundle_info,
      $entity_repository,
      $renderer,
      $string_translation,
      $url_generator
    );
    $this->institutionManager = $institution_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('renderer'),
      $container->get('string_translation'),
      $container->get('url_generator'),
      $container->get('ewp_institutions_get.manager')
    );
  }

  /**
   * Automatically imports an Institution.
   *
   * @param string $index_key
   *   The Index key to look up the Institution data.
   * @param string $hei_key
   *   The Institution key to import.
   *
   * @return array
   *   Array with entity id as key and entity data as value.
   */
  public function autoImport($index_key, $hei_key) {
    // Create a new Institution if none exists with the same key
    $hei = $this->institutionManager->getInstitution($hei_key, $index_key);
    dpm($hei);

    return [];
  }

  /**
   * Provides a generic add from external source title callback.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The title for the entity import page.
   */
  public function addExternalTitle($entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    return $this->t('Add @entity-type from external source', [
      '@entity-type' => $entity_type->getSingularLabel()
    ]);
  }

  /**
   * Provides a generic import title callback.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The title for the entity import page.
   */
  public function importTitle($entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    return $this->t('Import @entity-type', [
      '@entity-type' => $entity_type->getSingularLabel()
    ]);
  }

}
