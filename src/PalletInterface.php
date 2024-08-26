<?php

declare(strict_types = 1);

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
   *
   * @return array
   *   The css.
   */
  public function getCssData($id = NULL):array;

}
