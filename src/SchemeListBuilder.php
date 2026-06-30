<?php

declare(strict_types=1);

namespace Drupal\neo_color;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\neo_icon\IconTrait;

/**
 * Provides a listing of schemes.
 */
final class SchemeListBuilder extends DraggableListBuilder {

  use IconTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'neo_scheme_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['status'] = $this->t('Status');
    $header['selector'] = $this->t('Selector');
    $header['preview'] = $this->t('Preview');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\neo_color\SchemeInterface $entity */
    $row['label'] = $entity->label();
    $row['status']['#markup'] = $this->statusIcon($entity->status())->iconOnly();
    $row['selector'] = [
      'data' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => '.' . $entity->getSelector(),
      ],
      '#neo_size' => 'min',
      '#neo_style' => 'xs',
    ];
    $row['preview'] = [
      'data' => [
        'swatch' => [
          '#theme' => 'neo_scheme_swatch',
          '#neo_scheme' => $entity,
        ],
      ],
      '#neo_size' => 'min',
    ];
    if (!$entity->status()) {
      $row['preview']['data']['css'] = [
        '#type' => 'html_tag',
        '#tag' => 'style',
        '#value' => $entity->getCss(),
        '#attributes' => [
          'neo-scheme' => '',
        ],
      ];
    }
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   *
   * Builds the entity listing as renderable array for table.html.twig.
   *
   * @todo Add a link to add a new item to the #empty text.
   */
  public function render() {
    $build = parent::render();
    return $build;
  }

}
