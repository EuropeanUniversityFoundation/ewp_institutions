<?php

namespace Drupal\ewp_institutions_get\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Drupal\ewp_institutions_get\InstitutionManager;

/**
 * Institution entity controller.
 *
 * Provides additional title callbacks for Institution entities:
 * - An add from external source title callback.
 * - An import title callback.
 */
class InstitutionEntityController extends EntityController {

  /**
   * A router implementation which does not check access.
   *
   * @var \Symfony\Component\Routing\Matcher\UrlMatcherInterface
   */
  protected $accessUnawareRouter;

  /**
   * The Institution manager service.
   *
   * @var \Drupal\ewp_institutions_get\InstitutionManager
   */
  protected $institutionManager;

  /**
   * Constructs an InstitutionEntityController object.
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
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\Routing\Matcher\UrlMatcherInterface $access_unaware_router
   *   A router implementation which does not check access.
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
    RouteMatchInterface $route_match,
    UrlMatcherInterface $access_unaware_router,
    InstitutionManager $institution_manager
  ) {
    parent::__construct(
      $entity_type_manager,
      $entity_type_bundle_info,
      $entity_repository,
      $renderer,
      $string_translation,
      $url_generator,
      $route_match
    );
    $this->accessUnawareRouter = $access_unaware_router;
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
      $container->get('current_route_match'),
      $container->get('router.no_access_checks'),
      $container->get('ewp_institutions_get.manager')
    );
  }

  /**
   * Automatically imports an Institution.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Request object.
   * @param string $index_key
   *   The Index key to look up the Institution data.
   * @param string $hei_key
   *   The Institution key to import.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects to the relevant route.
   */
  public function autoImport(Request $request, $index_key, $hei_key): RedirectResponse {
    // Create a new Institution if none exists with the same key.
    $hei = $this->institutionManager
      ->getInstitution($hei_key, $index_key, TRUE);

    if (!empty($hei)) {
      foreach ($hei as $id => $value) {
        $params = [InstitutionManager::ENTITY_TYPE => $id];
      }

      $route = 'entity.' . InstitutionManager::ENTITY_TYPE . '.canonical';
      return $this->redirect($route, $params);
    }

    $referer = $request->headers->get('referer');
    $result = $this->accessUnawareRouter->match($referer);
    return $this->redirect($result['_route']);
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
      '@entity-type' => $entity_type->getSingularLabel(),
    ]);
  }

  /**
   * Provides a generic auto import title callback.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return string
   *   The title for the auto import form.
   */
  public function autoImportTitle($entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    return $this->t('Look up @entity-type', [
      '@entity-type' => $entity_type->getPluralLabel(),
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
      '@entity-type' => $entity_type->getSingularLabel(),
    ]);
  }

}
