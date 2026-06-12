<?php

declare(strict_types=1);

namespace Drupal\neo_color\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\neo_build\Event\NeoBuildInlineEvent;
use Drupal\neo_color\Entity\Scheme;
use Drupal\neo_color\SchemeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Act on build events.
 *
 * @package Drupal\custom_events\EventSubscriber
 */
class NeoBuildInlineEventSubscriber implements EventSubscriberInterface {

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
   * We inject the CSS variables directly into the DOM so that we do not need
   * to wait for the build to complete before the CSS is applied.
   *
   * @param \Drupal\neo_build\Event\NeoBuildInlineEvent $event
   *   The neo build dev event.
   */
  public function onInlineBuild(NeoBuildInlineEvent $event) {
    /** @var \Drupal\neo_color\PalletInterface[] $pallets */
    $pallets = $this->entityTypeManager->getStorage('neo_pallet')->loadByProperties([
      'status' => 1,
    ]);
    foreach ($pallets as $pallet) {
      foreach ($pallet->getCssData() as $key => $value) {
        $event->addCssValue($key, $value);
      }
    }
    $event->addCacheTags(['config:neo_pallet_list']);

    /** @var \Drupal\neo_color\SchemeInterface[] $schemes */
    $schemes = $this->entityTypeManager->getStorage('neo_scheme')->loadByProperties([
      'status' => 1,
    ]);
    if (!empty($schemes)) {
      $resetSlotShades = [];
      $resetPallets = [];
      foreach (SchemeInterface::PALLETS as $palletId) {
        if (isset($pallets[$palletId])) {
          /** @var \Drupal\neo_color\PalletInterface $pallet */
          $pallet = $pallets[$palletId];
          foreach ($pallet->getCssData() as $key => $value) {
            // Revert the semantic defaults to root too, so bg-/text-/border-
            // default inside a reset scope drop back to the base palette.
            if ($palletId === 'base') {
              if ($key === '--color-base-0') {
                $event->addCssValue('--background-color-default', 'rgb(' . $value . ')', '.scheme--reset');
              }
              if ($key === '--color-base-0-content') {
                $event->addCssValue('--text-color-default', 'rgb(' . $value . ')', '.scheme--reset');
              }
              if ($key === '--color-base-200') {
                $event->addCssValue('--color-border-default', 'rgb(' . $value . ')', '.scheme--reset');
              }
            }
            $event->addCssValue($key, $value, '.scheme--reset');
          }
          $resetSlotShades[$palletId] = $pallet->getTransformedShades();
          $resetPallets[$palletId] = $pallet;
        }
      }
      // Reset the contrast-aware button variables too, so a reset scope inside
      // a scheme does not inherit the outer scheme's button colors.
      foreach (Scheme::buildButtonCssVars($resetSlotShades, $resetPallets) as $key => $value) {
        $event->addCssValue($key, $value, '.scheme--reset');
      }
      foreach ($schemes as $scheme) {
        foreach ($scheme->getCssData() as $key => $value) {
          // Re-pin the semantic default tokens to the scheme's own surface
          // values. Their @theme definitions are `rgb(var(--color-base-*))`
          // resolved against :root, so without this override they keep the
          // root color even inside a scheme. `--color-border-default` is the
          // var the preflight `*` border-color rule reads, so this makes the
          // DEFAULT border of every element follow the scheme (no
          // .border-default class needed) — alongside bg-/text-default.
          if ($key === '--color-base-0') {
            $event->addCssValue('--background-color-default', 'rgb(' . $value . ')', '.' . $scheme->getSelector());
          }
          if ($key === '--color-base-0-content') {
            $event->addCssValue('--text-color-default', 'rgb(' . $value . ')', '.' . $scheme->getSelector());
          }
          if ($key === '--color-base-200') {
            $event->addCssValue('--color-border-default', 'rgb(' . $value . ')', '.' . $scheme->getSelector());
          }
          $event->addCssValue($key, $value, '.' . $scheme->getSelector());
        }
      }
    }
    $event->addCacheTags(['config:neo_scheme_list']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      NeoBuildInlineEvent::EVENT_NAME => 'onInlineBuild',
    ];
  }

}
