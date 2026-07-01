<?php

declare(strict_types=1);

namespace Drupal\neo_color\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\neo_color\Element\Scheme;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for Neo Color.
 */
final class NeoColorCommands extends DrushCommands {

  /**
   * List the enabled color schemes.
   */
  #[CLI\Command(name: 'neo:color:schemes', aliases: ['neoc-schemes'])]
  #[CLI\FieldLabels(labels: [
    'id' => 'ID',
    'label' => 'Label',
    'selector' => 'Selector',
    'dark' => 'Dark',
    'colorize' => 'Colorized',
  ])]
  #[CLI\DefaultFields(fields: ['id', 'label', 'selector', 'dark', 'colorize'])]
  #[CLI\Usage(name: 'drush neo:color:schemes', description: 'List every enabled color scheme.')]
  public function schemes(array $options = ['format' => 'table']): RowsOfFields {
    $rows = [];
    /** @var \Drupal\neo_color\SchemeInterface $scheme */
    foreach (Scheme::getSchemes() as $scheme) {
      $rows[$scheme->id()] = [
        'id' => $scheme->id(),
        'label' => (string) $scheme->label(),
        'selector' => $scheme->getSelector(),
        'dark' => $scheme->get('dark') ? 'yes' : 'no',
        'colorize' => $scheme->get('colorize') ? 'yes' : 'no',
      ];
    }
    if (!$rows) {
      $this->io()->warning('No enabled schemes found.');
    }
    return new RowsOfFields($rows);
  }

}
