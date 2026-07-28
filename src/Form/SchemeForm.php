<?php

declare(strict_types=1);

namespace Drupal\neo_color\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\neo_color\Entity\Scheme;

/**
 * Scheme form.
 */
final class SchemeForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\neo_color\SchemeInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {

    $form = parent::form($form, $form_state);

    /** @var \Drupal\neo_color\PalletInterface[] $pallets */
    $pallets = $this->entityTypeManager->getStorage('neo_pallet')->loadByProperties([
      'status' => 1,
    ]);
    $options = [];
    foreach ($pallets as $pallet) {
      $options[$pallet->id()] = $pallet->label();
    }

    $form['#id'] = 'neo-scheme';
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [Scheme::class, 'load'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    $form['preview'] = [
      '#theme' => 'neo_scheme_preview',
      '#neo_scheme' => $this->entity,
      '#prefix' => '<div class="card">',
      '#suffix' => '</div>',
    ];

    $form['dark'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dark Mode'),
      '#default_value' => $this->entity->get('dark'),
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'neo-scheme',
      ],
    ];

    $form['colorize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Colorize'),
      '#default_value' => $this->entity->get('colorize'),
      '#description' => $this->t('Rebuild the <em>base</em> pallet as a brand-tinted surface using the base pallet\'s <em>500</em> color. The surface still follows "Dark Mode": a light brand tint when off, a dark brand shade when on.'),
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'neo-scheme',
      ],
    ];

    $form['colorize_offset'] = [
      '#type' => 'range',
      '#title' => $this->t('Colorize offset'),
      '#min' => 0,
      '#max' => 200,
      '#step' => 5,
      '#default_value' => (int) ($this->entity->get('colorize_offset') ?? 100),
      '#description' => $this->t("How far the surface is tinted away from the base pallet's <em>500</em> color. <em>0</em> keeps the surface at the exact <em>500</em> color; <em>100</em> is the full light/dark tint; <em>200</em> pushes all the way to a pure white (light) / black (dark) surface. At low values, default borders and base buttons intentionally converge toward the surface color."),
      '#states' => [
        'visible' => [
          ':input[name="colorize"]' => ['checked' => TRUE],
        ],
      ],
      // Core's ajax pre-render has no case for 'range', so the event must be
      // set explicitly or no ajax behavior is bound at all. 'change' fires on
      // slider release, which is the right granularity for a preview rebuild.
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'neo-scheme',
        'event' => 'change',
      ],
    ];

    $form['colorize_natural'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Natural saturation'),
      '#default_value' => (bool) $this->entity->get('colorize_natural'),
      '#description' => $this->t("Take each shade's saturation from the base pallet's own ramp instead of from its <em>500</em> color. Leave this off for a brand pallet, whose saturation is even across the ramp. Turn it on when the base pallet is a neutral with a saturated dark end — its light shades are meant to read as near-gray, and the <em>500</em> anchor would tint them."),
      '#states' => [
        'visible' => [
          ':input[name="colorize"]' => ['checked' => TRUE],
        ],
      ],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'neo-scheme',
      ],
    ];

    foreach ([
      'base' => $this->t('Base Pallet'),
      'primary' => $this->t('Primary Pallet'),
      'secondary' => $this->t('Secondary Pallet'),
      'accent' => $this->t('Accent Pallet'),
    ] as $key => $label) {
      $form[$key] = [
        '#type' => 'select',
        '#title' => $label,
        '#options' => $options,
        '#required' => TRUE,
        '#default_value' => $this->entity->get($key),
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'wrapper' => 'neo-scheme',
        ],
        '#field_suffix' => [
          '#theme' => 'neo_pallet_swatches',
          '#neo_pallet' => $pallets[$this->entity->get($key)],
        ],
      ];
      if ($key !== 'base') {
        $form[$key . '_contrast'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Auto-contrast'),
          '#default_value' => (bool) $this->entity->get($key . '_contrast'),
          '#description' => $this->t("Nudge this color to a legible shade wherever the pallet's <em>500</em> lacks contrast against this scheme's surface. Disable to always use the exact <em>500</em> color for buttons and bare color utilities (<em>bg/text/border</em>) — links and outline buttons stay contrast-protected either way."),
          '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'neo-scheme',
          ],
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $this->messenger()->addStatus(
      match($result) {
        \SAVED_NEW => $this->t('Created new example %label.', $message_args),
        \SAVED_UPDATED => $this->t('Updated example %label.', $message_args),
      }
    );
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
