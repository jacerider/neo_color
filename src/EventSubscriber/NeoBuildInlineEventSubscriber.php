<?php

declare(strict_types = 1);

namespace Drupal\neo_color\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\neo_build\Event\NeoBuildInlineEvent;
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
    private readonly EntityTypeManagerInterface $entityTypeManager
  ) {

  }

  /**
   * Subscribe to the user login event dispatched.
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
      foreach (SchemeInterface::PALLETS as $palletId) {
        if (isset($pallets[$palletId])) {
          /** @var \Drupal\neo_color\PalletInterface $pallet */
          $pallet = $pallets[$palletId];
          foreach ($pallet->getCssData() as $key => $value) {
            $event->addCssValue($key, $value, '.scheme--reset');
          }
        }
      }
    }
    foreach ($schemes as $scheme) {
      foreach ($scheme->getCssData() as $key => $value) {
        $event->addCssValue($key, $value, '.' . $scheme->getSelector());
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
