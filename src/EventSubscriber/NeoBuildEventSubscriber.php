<?php

declare(strict_types = 1);

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
    private readonly EntityTypeManagerInterface $entityTypeManager
  ) {

  }

  /**
   * Subscribe to the user login event dispatched.
   *
   * @param \Drupal\neo_build\Event\NeoBuildEvent $event
   *   The neo build event.
   */
  public function onBuild(NeoBuildEvent $event) {
    $config = $event->getConfig();
    /** @var \Drupal\neo_color\PalletInterface[] $pallets */
    $pallets = $this->entityTypeManager->getStorage('neo_pallet')->loadByProperties([
      'status' => 1,
    ]);
    $config['tailwind']['theme']['extend']['borderColor']['DEFAULT'] = 'rgb(var(--color-base-300) / <alpha-value>)';
    foreach ($pallets as $pallet) {
      $id = $pallet->id();
      $config['tailwind']['theme']['colors'][$id]['DEFAULT'] = "rgb(var(--color-$id) / <alpha-value>)";
      $config['tailwind']['theme']['colors'][$id . '-content']['DEFAULT'] = "rgb(var(--color-$id-content) / <alpha-value>)";
      foreach ($pallet->getShades() as $shadeId => $shade) {
        $config['tailwind']['theme']['colors'][$id][$shadeId] = "rgb(var(--color-$id-$shadeId) / <alpha-value>)";
        $config['tailwind']['theme']['colors'][$id . '-content'][$shadeId] = "rgb(var(--color-$id-content-$shadeId) / <alpha-value>)";
      }
    }
    // Text base should use the base content color.
    $config['tailwind']['utilities']['.text-base']['color'] = 'colors.base-content.50';

    /** @var \Drupal\neo_color\SchemeInterface[] $schemes */
    $schemes = $this->entityTypeManager->getStorage('neo_scheme')->loadByProperties([
      'status' => 1,
    ]);
    foreach ($schemes as $scheme) {
      $selector = $scheme->getSelector();
      $config['tailwind']['variants'][str_replace('scheme-', '', $selector)] = [
        '.' . $selector . ' &',
        '&.' . $selector,
      ];
      if ($scheme->get('dark')) {
        $config['tailwind']['variants']['dark'][] = '.' . $selector . ' &';
        $config['tailwind']['variants']['dark'][] = '&.' . $selector;
      }
      if ($scheme->get('colorize')) {
        $config['tailwind']['variants']['color'][] = '.' . $selector . ' &';
        $config['tailwind']['variants']['color'][] = '&.' . $selector;
      }
    }

    $event->setConfig($config);
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
