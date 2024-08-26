<?php

declare(strict_types = 1);

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
      '#description' => $this->t('Alter the <em>base</em> pallet so that half of the shades will be set to a shade of <em>500</em>. The half that is altered is toggled by "Dark Mode". If <em>primary</em>, <em>secondary</em>, or <em>accent</em> pallets are set to the same value as the <em>base</em> pallet, they will use white/black as their color profile.'),
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
