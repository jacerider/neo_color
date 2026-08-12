<?php

declare(strict_types=1);

namespace Drupal\neo_color\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\neo_build\Event\NeoBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Act on build events.
 *
 * @package Drupal\custom_events\EventSubscriber
 */
class NeoBuildEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new NeoBuildEventSubscriber object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {

  }

  /**
   * Subscribe to the Neo build event dispatched.
   *
   * @param \Drupal\neo_build\Event\NeoBuildEvent $event
   *   The neo build event.
   */
  public function onBuild(NeoBuildEvent $event) {
    $collection = $event->getCollection();
    /** @var \Drupal\neo_color\PalletInterface[] $pallets */
    $pallets = $this->entityTypeManager->getStorage('neo_pallet')->loadByProperties([
      'status' => 1,
    ]);

    $theme = [];
    // Remove all default tailwind colors.
    $collection->addTailwindThemeItem('--color-*', 'initial', 'before');
    $collection->addTailwindThemeItem('--color-inherit', 'inherit');
    $collection->addTailwindThemeItem('--color-current', 'currentColor');
    $collection->addTailwindThemeItem('--color-transparent', 'transparent');
    $collection->addTailwindThemeItem('--color-white', 'rgb(var(--color-base-0))');
    $collection->addTailwindThemeItem('--color-white-content', 'rgb(var(--color-base-950))');
    $collection->addTailwindThemeItem('--color-black', 'rgb(var(--color-base-950))');
    $collection->addTailwindThemeItem('--color-black-content', 'rgb(var(--color-base-0))');
    // Explicit link tokens: the `link` / `link-hover` theme colors (used as
    // text-/bg-/border- utilities, with a hover: prefix on the latter) color
    // any element like a scheme link. The vars are the text-grade (4.5:1)
    // contrast picks every scheme emits — the same pair the base-layer bare
    // `a` rule reads — and they stay picked even on schemes that pin a role's
    // {role}_contrast off, which pins bare text-primary to the raw 500. The
    // fallbacks reproduce the classic primary-600/800 pairing outside any
    // scheme scope, mirroring neo_base's base.css.
    $collection->addTailwindThemeItem('--color-link', 'var(--link-color, rgb(var(--color-primary-600)))');
    $collection->addTailwindThemeItem('--color-link-hover', 'var(--link-color-hover, rgb(var(--color-primary-800)))');
    foreach ($pallets as $pallet) {
      $id = $pallet->id();
      $theme['colors'][$id]['DEFAULT'] = "rgb(var(--color-$id))";
      $theme['colors'][$id . '-content']['DEFAULT'] = "rgb(var(--color-$id-content))";
      // The adaptive hover step for the bare token: `hover:text-{id}-hover`
      // replaces the non-adaptive `hover:text-{id}-600`. Ramp default is 600
      // (Pallet::getCssData()); schemes re-pin primary/secondary/accent to a
      // contrast-picked step (Scheme::buildButtonCssVars()).
      $theme['colors'][$id . '-hover']['DEFAULT'] = "rgb(var(--color-$id-hover))";
      $theme['colors'][$id . '-hover-content']['DEFAULT'] = "rgb(var(--color-$id-hover-content))";
      foreach ($pallet->getShades() as $shadeId => $shade) {
        $theme['colors'][$id][$shadeId] = "rgb(var(--color-$id-$shadeId))";
        $theme['colors'][$id][$shadeId . '-content'] = "rgb(var(--color-$id-$shadeId-content))";
        if ($id === 'base') {
          $theme['colors']['shadow'][$shadeId] = "rgb(var(--color-shadow-$shadeId))";
        }
      }
    }

    // Fall back the Tailwind "gray" family scales to "base" when their
    // pallet is not enabled, so components copied from the internet (which use
    // gray/slate/zinc/neutral/stone) render against the standardized base
    // palette. base is scheme-scoped, so these aliases are scheme-reactive too.
    // An enabled pallet of the same name keeps its own colors (isset guard).
    if (isset($theme['colors']['base'])) {
      foreach (['gray', 'slate', 'zinc', 'neutral', 'stone'] as $alias) {
        if (!isset($pallets[$alias])) {
          $theme['colors'][$alias] = $theme['colors']['base'];
          $theme['colors'][$alias . '-content'] = $theme['colors']['base-content'];
        }
      }
    }

    $collection->addTailwindTheme($theme);

    /** @var \Drupal\neo_color\SchemeInterface[] $schemes */
    $schemes = $this->entityTypeManager->getStorage('neo_scheme')->loadByProperties([
      'status' => 1,
    ]);

    $variants = [
      'scheme' => [
        // Selector where class starts with "scheme-*".
        '[class^="scheme-"] &',
        '[class*=" scheme-"] &',
        // Selector where class contains "scheme-*".
        '&[class^="scheme-"]',
        '&[class*=" scheme-"]',
      ],
    ];
    foreach ($schemes as $scheme) {
      $selector = $scheme->getSelector();
      $key = str_replace('scheme-', '', $selector);
      $variants[$key][] = '.' . $selector . ' &';
      $variants[$key][] = '&.' . $selector;
      $isDark = $scheme->get('dark');
      $isColor = $scheme->get('colorize');
      if ($isDark) {
        $variants['dark'][] = '.' . $selector . ' &';
        $variants['dark'][] = '&.' . $selector;
      }
      if ($isColor) {
        $variants['color'][] = '.' . $selector . ' &';
        $variants['color'][] = '&.' . $selector;
      }
    }
    $collection->addTailwindVariants($variants);

    // Make Tailwind Typography's `prose` follow the active color scheme.
    //
    // @tailwindcss/typography hardcodes slate `--tw-prose-*` values (in the
    // `utilities` layer), so prose ignores the scheme entirely — e.g. dark
    // slate body text rendered on a dark/solid scheme background. The base
    // palette can't fix this: its scale runs surface -> black and does not
    // invert on dark schemes, so the only reliably-readable foreground is
    // `--text-color-default` (the scheme's base-0-content pairing).
    //
    // Scope the override to scheme containers so unscoped prose keeps the
    // plugin's tuned defaults (no regression, and `--text-color-default` is
    // only defined under a scheme). `!important` is required because this rule
    // lives in the `components` layer while the values it replaces live in the
    // higher-priority `utilities` layer — importance crosses layer boundaries,
    // specificity alone cannot.
    $text = 'var(--text-color-default)';
    $border = 'var(--color-border-default)';
    $muted = fn (int $pct) => "color-mix(in oklab, var(--text-color-default) {$pct}%, transparent)";
    $prose = [
      '--tw-prose-body' => $text . ' !important',
      '--tw-prose-headings' => $text . ' !important',
      '--tw-prose-lead' => $muted(80) . ' !important',
      '--tw-prose-links' => 'var(--link-color, var(--text-color-default)) !important',
      '--tw-prose-bold' => $text . ' !important',
      '--tw-prose-counters' => $muted(70) . ' !important',
      '--tw-prose-bullets' => $muted(45) . ' !important',
      '--tw-prose-hr' => $border . ' !important',
      '--tw-prose-quotes' => $text . ' !important',
      '--tw-prose-quote-borders' => $border . ' !important',
      '--tw-prose-captions' => $muted(70) . ' !important',
      '--tw-prose-code' => $text . ' !important',
      '--tw-prose-th-borders' => $border . ' !important',
      '--tw-prose-td-borders' => $border . ' !important',
    ];
    // Match a scheme on an ancestor (descendant prose) or on the prose element
    // itself, mirroring the scheme-class matching used for variants above.
    $proseSelector = implode(', ', [
      '[class^="scheme-"] .prose',
      '[class*=" scheme-"] .prose',
      '[class^="scheme-"].prose',
      '[class*=" scheme-"].prose',
    ]);
    $collection->addTailwindComponents([$proseSelector => $prose]);

    // Button colors inside schemes are handled by the contrast-aware
    // `--btn[-slot]-*` variables emitted per scheme scope
    // (see Scheme::buildButtonCssVars()), so no selector overrides are needed
    // here — the neo_theme button utilities already resolve through those
    // hooks with fallbacks.
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      NeoBuildEvent::EVENT_NAME => 'onBuild',
    ];
  }

}
