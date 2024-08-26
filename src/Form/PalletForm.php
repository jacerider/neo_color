<?php

declare(strict_types = 1);

namespace Drupal\neo_color\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\neo_build\Build;
use Drupal\neo_color\Entity\Pallet;
use Drupal\neo_color\PalletInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pallet form.
 */
final class PalletForm extends EntityForm implements ContainerInjectionInterface {

  /**
   * The build service.
   *
   * @var \Drupal\neo_build\Build
   */
  protected Build $build;

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\neo_color\PalletInterface
   */
  protected $entity;

  /**
   * Constructs a new ContactFormEditForm.
   *
   * @param \Drupal\neo_build\Build $build
   *   The build service.
   */
  public function __construct(Build $build) {
    $this->build = $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('neo_build')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    if ($this->operation == 'duplicate') {
      $form['#title'] = $this->t('<em>Duplicate</em> @label', ['@label' => $this->entity->label()]);
      $this->entity = $this->entity->createDuplicate();
      $this->entity->set('label', '');
    }

    $form = parent::form($form, $form_state);

    $specific = $this->entity->isSpecific();
    $contentPallet = $this->entity->getContentPallet();

    $form['#id'] = 'neo-pallet';
    $form['#attributes']['data-neo-content-light'] = $this->entity->getContentLightHex();
    $form['#attributes']['data-neo-content-dark'] = $this->entity->getContentDarkHex();
    if ($this->entity->isNew()) {
      $form['#attributes']['data-neo-pallet-init'] = 1;
    }

    if (!$this->entity->isNew()) {
      $form['#attributes']['data-neo-pallet'] = $this->entity->id();
    }
    $form['#attached']['library'][] = 'neo_color/color';

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
        'exists' => [Pallet::class, 'load'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    if ($this->entity->isNew() || !in_array($this->entity->id(), PalletInterface::LOCKED)) {
      $isDevMode = $this->build->isDevMode();
      $form['status'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => $this->entity->status(),
        '#description' => $isDevMode ? '' : $this->t('The status of a pallet can only be changed when in development mode.'),
        '#disabled' => !$isDevMode,
      ];
    }

    $form['preview'] = [
      '#theme' => 'neo_pallet_preview',
      '#neo_pallet' => $this->entity,
    ];

    $form['specific'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Choose specific colors for shades'),
      '#default_value' => $specific,
      '#attributes' => [
        'class' => [
          'neo-color--specific',
        ],
      ],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'neo-pallet',
      ],
    ];

    $form['color'] = [
      '#type' => 'coloris',
      '#title' => $this->t('Color'),
      '#description' => $this->t('The color that will be used for the 500 shade. This color will be used to generate the other shades.'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->getShade(500)->getHex(),
      '#required' => $specific,
      '#access' => !$specific,
      '#alpha' => FALSE,
      '#attributes' => ['class' => ['neo-pallet-color']],
      '#on_change' => 'Drupal.behaviors.neoColor.onColorChange',
    ];

    $form['content'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    /** @var \Drupal\neo_color\PalletInterface[] $pallets */
    $pallets = $this->entityTypeManager->getStorage('neo_pallet')->loadByProperties([
      'status' => 1,
    ]);
    $options = [];
    foreach ($pallets as $pallet) {
      $options[$pallet->id()] = $pallet->label();
    }
    $form['content']['pallet'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Color'),
      '#description' => $this->t('The shade to use for determining the light/dark content colors.'),
      '#options' => $options,
      '#default_value' => $contentPallet ? $contentPallet->id() : NULL,
      '#empty_option' => $this->t('- Custom -'),
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'neo-pallet',
      ],
    ];
    $form['content']['colors'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form--inline']],
    ];
    if ($contentPallet) {
      $form['content']['colors']['dark'] = [
        '#type' => 'select',
        '#title' => $this->t('Dark Content Shade'),
        '#description' => $this->t('This shade will be used for content on light backgrounds.'),
        '#options' => array_combine(PalletInterface::SHADES, PalletInterface::SHADES),
        '#default_value' => $this->entity->getContentDark(),
        '#parents' => ['content', 'dark'],
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'wrapper' => 'neo-pallet',
        ],
      ];
      $form['content']['colors']['light'] = [
        '#type' => 'select',
        '#title' => $this->t('Light Content Shade'),
        '#description' => $this->t('This shade will be used for content on dark backgrounds.'),
        '#options' => array_combine(PalletInterface::SHADES, PalletInterface::SHADES),
        '#default_value' => $this->entity->getContentLight(),
        '#parents' => ['content', 'light'],
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'wrapper' => 'neo-pallet',
        ],
      ];
      $form['content']['preview'] = [
        '#theme' => 'neo_pallet_swatches',
        '#neo_pallet' => $contentPallet,
      ];
    }
    else {
      $form['content']['colors']['#element_validate'][] = '::validateContentCustomColors';
      $form['content']['colors']['dark'] = [
        '#type' => 'coloris',
        '#title' => $this->t('Content Dark'),
        '#default_value' => $this->entity->getContentDark(),
        '#parents' => ['content', 'dark_custom'],
        '#attributes' => [
          'data-neo-content-color' => 'dark',
        ],
        '#on_change' => 'Drupal.behaviors.neoColor.onColorContentChange',
      ];
      $form['content']['colors']['light'] = [
        '#type' => 'coloris',
        '#title' => $this->t('Content Light'),
        '#default_value' => $this->entity->getContentLight(),
        '#parents' => ['content', 'light_custom'],
        '#attributes' => [
          'data-neo-content-color' => 'light',
        ],
        '#on_change' => 'Drupal.behaviors.neoColor.onColorContentChange',
      ];
    }

    $form['shades'] = [
      '#type' => $specific ? 'fieldset' : 'container',
      '#tree' => TRUE,
    ];

    foreach (PalletInterface::SHADES as $shadeId) {
      $shade = $this->entity->getShade($shadeId);
      if ($specific) {
        $form['shades'][$shadeId] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['form--inline']],
        ];
      }
      $form['shades'][$shadeId]['color'] = [
        '#type' => $specific ? 'coloris' : 'hidden',
        '#title' => $this->t('Shade @shade', ['@shade' => $shadeId]),
        '#maxlength' => 255,
        '#default_value' => $shade->getHex(),
        '#required' => TRUE,
        '#attributes' => [
          'data-neo-shade-color' => $shadeId,
          'class' => [
            'neo-color--' . $shadeId . '-color',
          ],
        ],
        '#field_prefix' => [
          '#type' => 'inline_template',
          '#template' => '
            <div class="neo-pallet-preview--{{ id }}-swatch neo-pallet-preview--{{ id }}-bg w-8 h-8 mr-2 flex items-center justify-center" style="background-color:{{ shade.getHex }};color:{{ shade.getContentHex }};">
              <div class="text-lg neo-pallet-preview--{{ id }}-content"><span>A</span><span>a</span></div>
            </div>
          ',
          '#context' => [
            'id' => $shade->getId(),
            'shade' => $shade,
          ],
        ],
        '#on_change' => 'Drupal.behaviors.neoColor.onColorChange',
      ];
      $form['shades'][$shadeId]['dark'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use dark content for @shade', ['@shade' => $shadeId]),
        '#default_value' => $shade->isDark(),
        '#attributes' => [
          'class' => [
            'neo-color--' . $shadeId . '-dark',
          ],
        ],
        '#wrapper_attributes' => [
          'class' => [
            $specific ? '' : 'hidden',
          ],
        ],
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'wrapper' => 'neo-pallet',
        ],
      ];
    }

    return $form;
  }

  /**
   * Validate custom content colors.
   */
  public function validateContentCustomColors(array $element, FormStateInterface $form_state): void {
    $values = $form_state->getValue(['content']);
    $values['dark'] = $values['dark_custom'];
    $values['light'] = $values['light_custom'];
    unset($values['dark_custom'], $values['light_custom']);
    $form_state->setValue(['content'], $values);
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
