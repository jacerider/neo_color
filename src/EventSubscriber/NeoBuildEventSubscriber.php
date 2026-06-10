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
    foreach ($pallets as $pallet) {
      $id = $pallet->id();
      $theme['colors'][$id]['DEFAULT'] = "rgb(var(--color-$id))";
      $theme['colors'][$id . '-content']['DEFAULT'] = "rgb(var(--color-$id-content))";
      foreach ($pallet->getShades() as $shadeId => $shade) {
        $theme['colors'][$id][$shadeId] = "rgb(var(--color-$id-$shadeId))";
        $theme['colors'][$id][$shadeId . '-content'] = "rgb(var(--color-$id-$shadeId-content))";
        if ($id === 'base') {
          $theme['colors']['shadow'][$shadeId] = "rgb(var(--color-shadow-$shadeId))";
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
