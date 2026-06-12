<?php

declare(strict_types=1);

namespace Drupal\neo_color;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a pallet entity type.
 */
interface PalletInterface extends ConfigEntityInterface {

  /**
   * The default color.
   */
  const DEFAULT_COLOR = '#75ACFF';

  /**
   * The shades.
   *
   * @var int[]
   */
  const SHADES = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

  /**
   * The protected pallets.
   *
   *  These pallets cannot be removed.
   *
   * @var string[]
   */
  const PROTECTED = [
    'base',
    'primary',
    'secondary',
    'accent',
    'info',
    'alert',
    'warning',
    'success',
    'slate',
    'gray',
    'zinc',
    'neutral',
    'stone',
    'red',
    'orange',
    'amber',
    'yellow',
    'lime',
    'green',
    'emerald',
    'teal',
    'cyan',
    'sky',
    'blue',
    'indigo',
    'violet',
    'purple',
    'fuchsia',
    'pink',
    'rose',
  ];

  /**
   * The locked pallets.
   *
   * These pallets cannot be disabled.
   *
   * @var string[]
   */
  const LOCKED = [
    'base',
    'primary',
    'secondary',
    'accent',
    'info',
    'alert',
    'warning',
    'success',
  ];

  /**
   * Check if colors are specified per shade.
   *
   * @return bool
   *   TRUE if the pallet is specific.
   */
  public function isSpecific(): bool;

  /**
   * Get the shades.
   *
   * @return \Drupal\neo_color\Shade[]
   *   The shades.
   */
  public function getShades();

  /**
   * Get the shades.
   *
   * @param bool $dark
   *   If TRUE, the shades will be reversed. 0 will be 950, 950 will be 0.
   *
   * @return \Drupal\neo_color\Shade[]
   *   The shades.
   */
  public function getColoredShades(bool $dark = FALSE): array;

  /**
   * Get the shades as they render within a scheme context.
   *
   * @param bool $dark
   *   If TRUE, the shades are reversed (dark scheme).
   * @param bool $scale
   *   If TRUE, the shades are scaled around the 500 shade (colorized base).
   * @param int $colorizeOffset
   *   How far the colorized surface is tinted away from the brand 500 shade
   *   (0-100). 0 anchors the surface at the exact 500 color; 100 is the full
   *   light/dark tint. Only used when $scale is TRUE.
   *
   * @return \Drupal\neo_color\Shade[]
   *   The transformed shades, keyed by integer shade id (0-950).
   */
  public function getTransformedShades(bool $dark = FALSE, bool $scale = FALSE, int $colorizeOffset = 100): array;

  /**
   * Get a shade.
   *
   * @return \Drupal\neo_color\Shade|null
   *   The shade.
   */
  public function getShade($shade);

  /**
   * Get the content pallet id.
   *
   * @return string
   *   The content pallet id.
   */
  public function getContentPalletId():string;

  /**
   * Get the content pallet.
   *
   * @return \Drupal\neo_color\PalletInterface|null
   *   The content pallet.
   */
  public function getContentPallet(): PalletInterface|null;

  /**
   * Get the content light value.
   */
  public function getContentLight():string;

  /**
   * Get the content light hex.
   */
  public function getContentLightHex():string;

  /**
   * Get the content dark value.
   */
  public function getContentDark():string;

  /**
   * Get the content dark hex.
   */
  public function getContentDarkHex():string;

  /**
   * Get inline css.
   *
   * @param string|null $id
   *   An optional override of the pallet id.
   *
   * @return string
   *   The css.
   */
  public function getCss($id = NULL):string;

  /**
   * Get an array of inline css.
   *
   * @param string|null $id
   *   An optional override of the pallet id.
   * @param bool $dark
   *   If TRUE, the shade colors will be reversed. 0 will be 950, 950 will be 0.
   * @param bool $color
   *   If TRUE, the shades will be shifted to start with 500 and end with 50 or
   *   950 depending on $dark.
   * @param bool $swap
   *   If TRUE, the base color will be swapped with the content color. The base
   *   colors will set from 0 to 950 unless $dark is TRUE which will then set
   *   from 950 to 0.
   * @param int $colorizeOffset
   *   How far the colorized surface is tinted away from the brand 500 shade
   *   (0-100). 0 anchors the surface at the exact 500 color; 100 is the full
   *   light/dark tint. Only used when $color is TRUE.
   *
   * @return array
   *   The css.
   */
  public function getCssData($id = NULL, $dark = FALSE, $color = FALSE, $swap = FALSE, int $colorizeOffset = 100):array;

}
