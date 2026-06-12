<?php

declare(strict_types=1);

namespace Drupal\neo_color\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Neo | Color routes.
 */
final class SchemePreviewController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(): array {
    $build = [];

    /** @var \Drupal\neo_color\SchemeInterface[] $schemes */
    $schemes = $this->entityTypeManager()->getStorage('neo_scheme')->loadByProperties(['status' => TRUE]);
    foreach ($schemes as $scheme) {
      $build['schemes'][] = [
        '#type' => 'fieldset',
        '#title' => $scheme->label(),
        '#attributes' => ['class' => ['mb-3']],
        'swatch' => [
          '#theme' => 'neo_scheme_swatch',
          '#neo_scheme' => $scheme,
          '#prefix' => '<div class="mb-3">',
          '#suffix' => '</div>',
        ],
        'preview' => [
          '#theme' => 'neo_scheme_preview',
          '#neo_scheme' => $scheme,
        ],
        '#weight' => $scheme->getWeight(),
      ];
    }

    return $build;
  }

}
