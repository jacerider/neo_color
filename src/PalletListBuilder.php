<?php

declare(strict_types = 1);

namespace Drupal\neo_color;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of pallets.
 */
final class PalletListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['active'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('There are no active @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
      '#access' => FALSE,
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
    $build['inactive'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#caption' => $this->t('Inactive Pallets'),
      '#rows' => [],
      '#empty' => $this->t('There are no inactive @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
      '#access' => FALSE,
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
    foreach ($this->load() as $entity) {
      /** @var \Drupal\neo_color\PalletInterface $entity */
      if ($row = $this->buildRow($entity)) {
        if ($entity->status()) {
          $build['active']['#rows'][$entity->id()] = $row;
          $build['active']['#access'] = TRUE;
        }
        else {
          $build['inactive']['#rows'][$entity->id()] = $row;
          $build['inactive']['#access'] = TRUE;
        }
      }
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['shades'] = $this->t('Shades');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\neo_color\PalletInterface $entity */
    $row['label']['data']['#markup'] = $entity->label() . ' <small>(' . $entity->id() . ')</small>';
    $row['shades'] = [
      'data' => [
        '#theme' => 'neo_pallet_swatches',
        '#neo_pallet' => $entity,
      ],
      'class' => 'td--min',
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['duplicate'] = [
      'title' => $this->t('Duplicate'),
      'weight' => 99,
      'url' => $this->ensureDestination($entity->toUrl('duplicate-form')),
    ];
    return $operations;
  }

}
