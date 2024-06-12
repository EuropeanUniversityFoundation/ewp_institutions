<?php

namespace Drupal\ewp_institutions_get;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * EWP Institutions GET form alter service.
 */
class InstitutionFormAlter {

  use StringTranslationTrait;

  const OBJ = 'widget';
  const PROP = 'target_id';
  const SEL = '#selection_settings';
  const AC = 'auto_create';
  const DESC = '#description';

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    RendererInterface $renderer,
    TranslationInterface $string_translation
  ) {
    $this->renderer          = $renderer;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Alter Institution reference autocomplete form element.
   *
   * @param array $elements
   *   The form elements.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $context
   *   The context array.
   */
  public function autocompleteAlter(array &$elements, FormStateInterface $form_state, array $context) {
    $target_type = $elements[self::OBJ][0][self::PROP]['#target_type'];
    $selection_settings = $elements[self::OBJ][0][self::PROP][self::SEL];
    $auto_create = (array_key_exists(self::AC, $selection_settings))
      ? $selection_settings[self::AC]
      : FALSE;

    if ($target_type === InstitutionManager::ENTITY_TYPE && $auto_create) {
      foreach ($elements as $key => $value) {
        if (is_numeric($key)) {
          // Unset the auto create ability via the normal form element.
          $elements[self::OBJ][$key][self::PROP][self::SEL][self::AC] = FALSE;
          unset($elements[self::OBJ][$key][self::PROP]['#autocreate']);
        }
      }

      // Create a link to launch a modal auto import form.
      $link = [
        '#type' => 'link',
        '#title' => $this->t('Click here to look up more Institutions'),
        '#url' => Url::fromRoute('entity.hei.auto_import_form', [], [
          'query' => ['modal' => ''],
        ]),
        '#attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => ['{"width":800}'],
        ],
        '#prefix' => '<strong>',
        '#suffix' => '</strong>',
      ];

      $markup = $this->renderer->render($link);

      // Place the link markup in the element according to cardinality.
      if ($elements[self::OBJ]['#cardinality_multiple']) {
        $break = (!empty($elements[self::OBJ][self::DESC])) ?
          '<br/>' : '';
        $elements[self::OBJ][self::DESC] .= $break . $markup;
      }
      else {
        $break = (!empty($elements[self::OBJ][0][self::PROP][self::DESC])) ?
          '<br/>' : '';
        $elements[self::OBJ][0][self::PROP][self::DESC] .= $break . $markup;
      }
    }
  }

}
