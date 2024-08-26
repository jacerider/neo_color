<?php

declare(strict_types = 1);

namespace Drupal\neo_color\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\FormElementBase;

/**
 * Provides a render element to display a neo color.
 *
 * Usage Example:
 * @code
 * $build['neo_color'] = [
 *   '#type' => 'neo_color',
 * ];
 * @endcode
 */
#[RenderElement('neo_color')]
final class Color extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $class = static::class;
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processGroup'],
        [$class, 'processNeoColor'],
      ],
      '#value_callback' => [
        [$class, 'valueCallback'],
      ],
      // Can be raw, rgb or rgba.
      '#format' => 'raw',
      '#scheme' => '',
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
  public static function processNeoColor(&$element, FormStateInterface $form_state, &$complete_form): array {
    $defaultValue = $form_state->getValue($element['#parents']) ?? $element['#default_value'];
    $allowOpacity = FALSE;
    $defaultOpacity = '100';
    switch ($element['#format']) {
      case 'rgb':
        $defaultValue = str_replace('rgb(var(--color-', '', str_replace('))', '', $defaultValue));
        break;

      case 'rgba':
        $allowOpacity = TRUE;
        $parts = array_map('trim', explode('/', $defaultValue));
        if (count($parts) === 2) {
          $defaultValue = str_replace('rgb(var(--color-', '', str_replace(')', '', $parts[0]));
          $defaultOpacity = str_replace(')', '', $parts[1]);
        }
        else {
          $defaultValue = str_replace('rgb(var(--color-', '', str_replace('))', '', $defaultValue));
        }
        break;
    }
    $required = isset($element['#states']['required']) ? TRUE : $element['#required'];
    $defaultPallet = '';
    $defaultShade = '';

    $element['#tree'] = TRUE;
    $element['#element_validate'][] = [static::class, 'elementValidate'];

    if (is_string($defaultValue)) {
      $parts = explode('-', $defaultValue);
      if (!empty($parts[0]) && !empty($parts[1])) {
        $defaultPallet = $parts[0];
        $defaultShade = $parts[1] ?? '500';
      }
    }

    /** @var \Drupal\neo_color\PalletInterface[] $pallets */
    $pallets = \Drupal::entityTypeManager()->getStorage('neo_pallet')->loadByProperties([
      'status' => 1,
    ]);
    $palletOptions = [];
    if (!$required) {
      $palletOptions[''] = [
        '#type' => 'inline_template',
        '#template' => '<div class="h-6 px-2 mr-2 text-xs flex items-center justify-center rounded">{{ id }}</div>',
        '#context' => [
          'id' => $element['#empty_option'] ?? t('None'),
        ],
      ];
    }
    foreach ($pallets as $pallet) {
      $shade = $pallet->getShade(500);
      $palletOptions[$pallet->id()] = [
        '#type' => 'inline_template',
        '#template' => '<div class="neo-pallet-swatches--{{ id }}-swatch h-6 px-2 mr-2 text-xs flex items-center justify-center rounded" style="background-color:{{ shade.getHex }};color:{{ shade.getContentHex }};">{{ id }}</div>',
        '#context' => [
          'id' => $pallet->label(),
          'shade' => $shade,
        ],
      ];
    }
    $pallet = $defaultPallet && isset($pallets[$defaultPallet]) ? $pallets[$defaultPallet] : NULL;

    $element['pallet'] = [
      '#type' => 'radios',
      '#title' => t('Pallet'),
      '#style' => 'inline_elements',
      '#options' => $palletOptions,
      '#required' => !empty($element['#required']),
      '#default_value' => $defaultPallet,
      '#ajax' => [
        'callback' => [self::class, 'ajaxCallback'],
        'wrapper' => $element['#id'],
      ],
    ];

    if ($pallet) {
      $shadeOptions = [];
      foreach ($pallet->getShades() as $shade) {
        $shadeOptions[$shade->getId()] = [
          '#type' => 'inline_template',
          '#template' => '<div class="neo-pallet-swatches--{{ id }}-swatch w-8 h-6 mr-2 text-xs flex items-center justify-center rounded" style="background-color:{{ shade.getHex }};color:{{ shade.getContentHex }};">{{ id }}</div>',
          '#context' => [
            'id' => $shade->getId(),
            'shade' => $shade,
          ],
        ];
      }
      $element['shade'] = [
        '#type' => 'radios',
        '#title' => t('Shade'),
        '#style' => 'inline_elements',
        '#options' => $shadeOptions,
        '#required' => !empty($element['#required']),
        '#default_value' => $defaultShade,
      ];
      if ($allowOpacity) {
        $element['opacity'] = [
          '#type' => 'range',
          '#title' => t('Opacity'),
          '#min' => 0,
          '#max' => 1,
          '#step' => 0.05,
          '#default_value' => $defaultOpacity,
          '#attributes' => [
            'class' => ['w-full'],
          ],
        ];
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function ajaxCallback(array &$form, FormStateInterface $form_state): array {
    $parents = array_splice($form_state->getTriggeringElement()['#array_parents'], 0, -2);
    return NestedArray::getValue($form, $parents);
  }

  /**
   * {@inheritdoc}
   */
  public static function elementValidate($element, FormStateInterface $form_state, $form) {
    $value = $originalValue = $form_state->getValue($element['#parents']);
    if (!empty($value['pallet'])) {
      $value = $value['pallet'] . '-' . ($value['shade'] ?? '500');
      switch ($element['#format']) {
        case 'rgb':
          $value = 'rgb(var(--color-' . $value . '))';
          break;

        case 'rgba':
          $value = 'rgb(var(--color-' . $value . ') / ' . ($originalValue['opacity'] ?? 100) . ')';
          break;
      }
    }
    else {
      $value = '';
    }
    $form_state->setValueForElement($element, $value);
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      return NULL;
    }
    return $input['pallet'] . '-' . ($input['shade'] ?? '500');
  }

}
