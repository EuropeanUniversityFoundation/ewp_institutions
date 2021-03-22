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
    // Set new permission for the Institution add form.
    if ($route = $collection->get('entity.hei.add_form')) {
      $route->setRequirements([
        '_permission' => 'bypass import institution entities',
      ]);
    }
  }

}
