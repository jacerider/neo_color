<?php

declare(strict_types=1);

namespace Drupal\neo_color\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\neo_color\Element\Scheme;
use Drupal\neo_color\SchemeInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for Neo Color.
 */
final class NeoColorCommands extends DrushCommands {

  /**
   * List the enabled color schemes.
   *
   * Includes each scheme's role-to-pallet mapping and its resolved surface
   * and text colors, so the whole scheme landscape is visible in one call.
   */
  #[CLI\Command(name: 'neo:color:schemes', aliases: ['neoc-schemes'])]
  #[CLI\FieldLabels(labels: [
    'id' => 'ID',
    'label' => 'Label',
    'selector' => 'Selector',
    'dark' => 'Dark',
    'colorize' => 'Colorized',
    'base' => 'Base pallet',
    'primary' => 'Primary pallet',
    'secondary' => 'Secondary pallet',
    'accent' => 'Accent pallet',
    'surface' => 'Surface',
    'text' => 'Text',
  ])]
  #[CLI\DefaultFields(fields: [
    'id',
    'label',
    'dark',
    'colorize',
    'base',
    'primary',
    'secondary',
    'accent',
    'surface',
    'text',
  ])]
  #[CLI\Usage(name: 'drush neo:color:schemes', description: 'List every enabled color scheme with pallet roles and resolved surface/text.')]
  #[CLI\Usage(name: 'drush neo:color:schemes --format=json', description: 'Machine-readable scheme list.')]
  public function schemes(array $options = ['format' => 'table']): RowsOfFields {
    $rows = [];
    /** @var \Drupal\neo_color\SchemeInterface $scheme */
    foreach (Scheme::getSchemes() as $scheme) {
      $css = $scheme->getCssData();
      $rows[$scheme->id()] = [
        'id' => $scheme->id(),
        'label' => (string) $scheme->label(),
        'selector' => $scheme->getSelector(),
        'dark' => $scheme->get('dark') ? 'yes' : 'no',
        'colorize' => $scheme->get('colorize') ? 'yes' : 'no',
        'base' => $scheme->getBase(),
        'primary' => $scheme->getPrimary(),
        'secondary' => $scheme->getSecondary(),
        'accent' => $scheme->getAccent(),
        'surface' => $this->toHex($css['--color-base-0'] ?? ''),
        'text' => $this->toHex($css['--color-base-0-content'] ?? ''),
      ];
    }
    if (!$rows) {
      $this->io()->warning('No enabled schemes found.');
    }
    return new RowsOfFields($rows);
  }

  /**
   * List the color pallets.
   *
   * Shows each pallet's brand anchor (the raw 500 shade), its content
   * pairing, and which scheme role slots use it — answering "what color IS
   * primary on this site" without digging through config.
   */
  #[CLI\Command(name: 'neo:color:pallets', aliases: ['neoc-pallets'])]
  #[CLI\Option(name: 'all', description: 'Include disabled pallets.')]
  #[CLI\FieldLabels(labels: [
    'id' => 'ID',
    'label' => 'Label',
    'hex' => 'Hex (500)',
    'content' => 'Content pallet',
    'content_light' => 'Content light',
    'content_dark' => 'Content dark',
    'roles' => 'Used by scheme roles',
    'specific' => 'Specific',
    'status' => 'Enabled',
  ])]
  #[CLI\DefaultFields(fields: [
    'id',
    'label',
    'hex',
    'content',
    'roles',
    'status',
  ])]
  #[CLI\Usage(name: 'drush neo:color:pallets', description: 'List enabled pallets with their 500 hex and scheme role usage.')]
  #[CLI\Usage(name: 'drush neo:color:pallets --all --format=json', description: 'Machine-readable list including disabled pallets.')]
  public function pallets(array $options = ['format' => 'table', 'all' => FALSE]): RowsOfFields {
    // Tally which role slots reference each pallet across enabled schemes.
    $roles = [];
    /** @var \Drupal\neo_color\SchemeInterface $scheme */
    foreach (Scheme::getSchemes() as $scheme) {
      foreach (SchemeInterface::PALLETS as $slot) {
        $palletId = $scheme->get($slot);
        $roles[$palletId][$slot] = ($roles[$palletId][$slot] ?? 0) + 1;
      }
    }

    $rows = [];
    /** @var \Drupal\neo_color\PalletInterface[] $pallets */
    $pallets = \Drupal::entityTypeManager()->getStorage('neo_pallet')->loadMultiple();
    foreach ($pallets as $pallet) {
      if (empty($options['all']) && !$pallet->status()) {
        continue;
      }
      $usage = [];
      foreach ($roles[$pallet->id()] ?? [] as $slot => $count) {
        $usage[] = $slot . '(' . $count . ')';
      }
      $rows[$pallet->id()] = [
        'id' => $pallet->id(),
        'label' => (string) $pallet->label(),
        'hex' => $pallet->getRawShadeHex(500),
        'content' => $pallet->getContentPalletId(),
        'content_light' => $pallet->getContentLightHex(),
        'content_dark' => $pallet->getContentDarkHex(),
        'roles' => implode(', ', $usage),
        'specific' => $pallet->isSpecific() ? 'yes' : 'no',
        'status' => $pallet->status() ? 'yes' : 'no',
      ];
    }
    if (!$rows) {
      $this->io()->warning('No pallets found.');
    }
    return new RowsOfFields($rows);
  }

  /**
   * Show one scheme's resolved colors.
   *
   * The curated fields cover what components actually consume: the semantic
   * surface/text/border tokens, link colors, the contrast-picked button
   * colors per role, and the bare role tokens — all normalized to hex.
   * Pass --vars for the full raw CSS variable set (every ramp step).
   */
  #[CLI\Command(name: 'neo:color:scheme', aliases: ['neoc-scheme'])]
  #[CLI\Argument(name: 'id', description: 'The scheme id (see neo:color:schemes).')]
  #[CLI\Option(name: 'vars', description: 'Append every raw CSS variable the scheme emits (ramps included).')]
  #[CLI\FieldLabels(labels: [
    'id' => 'ID',
    'label' => 'Label',
    'selector' => 'Selector',
    'status' => 'Enabled',
    'dark' => 'Dark',
    'colorize' => 'Colorized',
    'pallet_base' => 'Base pallet',
    'pallet_primary' => 'Primary pallet',
    'pallet_secondary' => 'Secondary pallet',
    'pallet_accent' => 'Accent pallet',
    'surface' => 'Surface (bg-default)',
    'text' => 'Text (default)',
    'border' => 'Border (default)',
    'link' => 'Link',
    'link_hover' => 'Link hover',
    'shadow_500' => 'Shadow 500',
    'base' => 'Base token',
    'base_content' => 'Base content',
    'btn_bg' => 'Btn bg',
    'btn_content' => 'Btn content',
    'btn_bg_hover' => 'Btn bg hover',
    'btn_line' => 'Btn line',
    'primary' => 'Primary token',
    'primary_content' => 'Primary content',
    'primary_contrast' => 'Primary auto-contrast',
    'primary_btn_bg' => 'Primary btn bg',
    'primary_btn_content' => 'Primary btn content',
    'primary_btn_bg_hover' => 'Primary btn bg hover',
    'secondary' => 'Secondary token',
    'secondary_content' => 'Secondary content',
    'secondary_contrast' => 'Secondary auto-contrast',
    'secondary_btn_bg' => 'Secondary btn bg',
    'secondary_btn_content' => 'Secondary btn content',
    'secondary_btn_bg_hover' => 'Secondary btn bg hover',
    'accent' => 'Accent token',
    'accent_content' => 'Accent content',
    'accent_contrast' => 'Accent auto-contrast',
    'accent_btn_bg' => 'Accent btn bg',
    'accent_btn_content' => 'Accent btn content',
    'accent_btn_bg_hover' => 'Accent btn bg hover',
    'vars' => 'Raw CSS variables',
  ])]
  #[CLI\Usage(name: 'drush neo:color:scheme accent_dark', description: 'Curated resolved colors for the accent_dark scheme.')]
  #[CLI\Usage(name: 'drush neo:color:scheme default --vars --format=json', description: 'Everything the scheme emits, machine-readable (under a "vars" object).')]
  public function scheme(string $id, array $options = ['format' => 'table', 'vars' => FALSE]): PropertyList {
    /** @var \Drupal\neo_color\SchemeInterface|null $scheme */
    $scheme = \Drupal::entityTypeManager()->getStorage('neo_scheme')->load($id);
    if (!$scheme) {
      $available = array_keys(\Drupal::entityTypeManager()->getStorage('neo_scheme')->loadMultiple());
      throw new \Exception("Scheme '$id' does not exist. Available: " . implode(', ', $available) . '.');
    }
    $css = $scheme->getCssData();
    $hex = fn (string $key): string => $this->toHex($css[$key] ?? '');

    $data = [
      'id' => $scheme->id(),
      'label' => (string) $scheme->label(),
      'selector' => $scheme->getSelector(),
      'status' => (bool) $scheme->status(),
      'dark' => (bool) $scheme->get('dark'),
      'colorize' => (bool) $scheme->get('colorize'),
      'pallet_base' => $scheme->getBase(),
      'pallet_primary' => $scheme->getPrimary(),
      'pallet_secondary' => $scheme->getSecondary(),
      'pallet_accent' => $scheme->getAccent(),
      'surface' => $hex('--color-base-0'),
      'text' => $hex('--color-base-0-content'),
      'border' => $hex('--color-base-200'),
      'link' => $hex('--link-color'),
      'link_hover' => $hex('--link-color-hover'),
      'shadow_500' => $hex('--color-shadow-500'),
      'base' => $hex('--color-base'),
      'base_content' => $hex('--color-base-content'),
      'btn_bg' => $hex('--btn-bg-color'),
      'btn_content' => $hex('--btn-content-color'),
      'btn_bg_hover' => $hex('--btn-bg-color-hover'),
      'btn_line' => $hex('--btn-line-color'),
    ];
    foreach (['primary', 'secondary', 'accent'] as $role) {
      $data[$role] = $hex('--color-' . $role);
      $data[$role . '_content'] = $hex('--color-' . $role . '-content');
      $data[$role . '_contrast'] = (bool) $scheme->get($role . '_contrast');
      $data[$role . '_btn_bg'] = $hex('--btn-' . $role . '-bg-color');
      $data[$role . '_btn_content'] = $hex('--btn-' . $role . '-content-color');
      $data[$role . '_btn_bg_hover'] = $hex('--btn-' . $role . '-bg-color-hover');
    }

    if (!empty($options['vars'])) {
      // Nested rather than flattened: the ramp keys are data-dependent and so
      // cannot be declared as field labels, and PropertyList filters output to
      // its declared fields. One labeled 'vars' object passes every raw
      // variable through (JSON-oriented — best read with --format=json).
      $data['vars'] = $css;
    }

    return new PropertyList($data);
  }

  /**
   * Normalize a CSS variable value to hex.
   *
   * Scheme data mixes "R G B" triplets (ramp tokens) with hex strings (link
   * and button tokens); curated output presents both uniformly.
   *
   * @param string $value
   *   The raw CSS variable value.
   *
   * @return string
   *   The hex color, or the original value if not an RGB triplet.
   */
  private function toHex(string $value): string {
    if (preg_match('/^\s*(\d{1,3})\s+(\d{1,3})\s+(\d{1,3})\s*$/', $value, $matches)) {
      return sprintf('#%02x%02x%02x', (int) $matches[1], (int) $matches[2], (int) $matches[3]);
    }
    return $value;
  }

}
