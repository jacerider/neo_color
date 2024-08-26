<?php

declare(strict_types = 1);

namespace Drupal\neo_color\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\FormElementBase;

/**
 * Provides a render element to display a neo scheme.
 *
 * Usage Example:
 * @code
 * $build['neo_scheme'] = [
 *   '#type' => 'neo_scheme',
 * ];
 * @endcode
 */
#[RenderElement('neo_scheme')]
final class Scheme extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $class = static::class;
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processGroup'],
        [$class, 'processNeoScheme'],
      ],
      '#value_callback' => [
        [$class, 'valueCallback'],
      ],
      // Can be raw, class.
      '#format' => 'raw',
      '#theme_wrappers' => ['fieldset'],
    ];
  }

  /**
   * Neo color element pre render callback.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The modified element.
   */
  public static function processNeoScheme(&$element, FormStateInterface $form_state, &$complete_form): array {
    $defaultValue = $element['#default_value'];
    $required = isset($element['#states']['required']) ? TRUE : $element['#required'];

    /** @var \Drupal\neo_color\SchemeInterface[] $schemes */
    $schemes = \Drupal::entityTypeManager()->getStorage('neo_scheme')->loadByProperties([
      'status' => 1,
    ]);
    switch ($element['#format']) {
      case 'class':
        foreach ($schemes as $scheme) {
          if ($scheme->getSelector() === $defaultValue) {
            $defaultValue = $scheme->id();
            break;
          }
        }
        break;
    }
    uasort($schemes, ['Drupal\neo_color\Entity\Scheme', 'sort']);
    $schemeOptions = [];
    if (!$required) {
      $schemeOptions[''] = [
        '#type' => 'inline_template',
        '#template' => '
        <div class="neo-schemes-swatches p-2 w-20 border border-base-200 rounded text-center bg-base-50 text-base-content-50">
          <div class="text-lg leading-none">{{ id }}</div>
          <div class="flex items-center justify-center mt-2 gap-2">
            <div class="h-2 w-4 bg-primary-500 rounded"></div>
            <div class="h-2 w-4 bg-secondary-500 rounded"></div>
            <div class="h-2 w-4 bg-accent-500 rounded"></div>
          </div>
        </div>
        ',
        '#context' => [
          'id' => $element['#empty_option'] ?? t('None'),
        ],
      ];
    }
    foreach ($schemes as $scheme) {
      $schemeOptions[$scheme->id()] = [
        '#theme' => 'neo_scheme_swatch',
        '#neo_scheme' => $scheme,
        '#tooltip' => $scheme->label(),
      ];
    }

    $element['#element_validate'][] = [static::class, 'elementValidate'];
    $element['scheme'] = [
      '#type' => 'radios',
      '#title' => t('Pallet'),
      '#style' => 'inline_elements',
      '#options' => $schemeOptions,
      '#required' => !empty($element['#required']),
      '#default_value' => $defaultValue,
    ];
    if (!empty($element['#ajax'])) {
      $element['scheme']['#ajax'] = $element['#ajax'];
      unset($element['#ajax']);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function elementValidate($element, FormStateInterface $form_state, $form) {
    $value = $form_state->getValue($element['#parents']);
    $value = $value['scheme'] ?? '';
    if ($value) {
      /** @var \Drupal\neo_color\SchemeInterface[] $schemes */
      $schemes = \Drupal::entityTypeManager()->getStorage('neo_scheme')->loadByProperties([
        'status' => 1,
      ]);
      switch ($element['#format']) {
        case 'class':
          if (isset($schemes[$value])) {
            $value = $schemes[$value]->getSelector();
          }
          break;
      }
    }
    $form_state->setValueForElement($element, $value);
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if (!$input || $input === FALSE) {
      return NULL;
    }
    return $input['scheme'];
  }

}
