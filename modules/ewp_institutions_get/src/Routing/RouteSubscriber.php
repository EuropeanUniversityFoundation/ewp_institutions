<?php

namespace Drupal\ewp_institutions_get\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Define new path and permission for the Institution add form.
    if ($route = $collection->get('entity.hei.add_form')) {
      // $route->setDefaults([
      //   '_entity_form' => 'hei.import',
      //   'entity_type_id' => 'hei',
      //   '_title_callback' => 'Drupal\Core\Entity\Controller\EntityController::addTitle'
      // ]);
      $route->setPath('/ewp/hei/add/new');
      $route->setRequirements([
        '_permission' => 'bypass import institution entities',
      ]);
    }
  }

}
