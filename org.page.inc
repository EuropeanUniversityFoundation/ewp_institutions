<?php

/**
 * @file
 * Contains org.page.inc.
 *
 * Page callback for Organization entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Organization templates.
 *
 * Default template: org.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_org(array &$variables) {
  // Fetch OrgEntity Entity Object.
  $org = $variables['elements']['#org'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
