<?php

namespace Drupal\ewp_institutions_get\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this controller class.
    $instance = parent::create($container);

    $instance->accessUnawareRouter = $container->get('router.no_access_checks');
    $instance->institutionManager = $container->get('ewp_institutions_get.manager');

    return $instance;
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
