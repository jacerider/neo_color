<?php

declare(strict_types = 1);

namespace Drupal\neo_color;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of schemes.
 */
final class SchemeListBuilder extends DraggableListBuilder {

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
    $row['selector'] = [
      'data' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => '.' . $entity->getSelector(),
      ],
      '#wrapper_attributes' => ['class' => ['td--min']],
    ];
    $row['preview'] = [
      'data' => [
        '#theme' => 'neo_scheme_swatch',
        '#neo_scheme' => $entity,
      ],
      '#wrapper_attributes' => ['class' => ['td--min']],
    ];
    return $row + parent::buildRow($entity);
  }

}
