<?php

declare(strict_types = 1);

namespace Drupal\neo_color;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a scheme entity type.
 */
interface SchemeInterface extends ConfigEntityInterface {

  /**
   * The pallet keys.
   *
   *  These pallets cannot be removed.
   *
   * @var string[]
   */
  const PALLETS = [
    'base',
    'primary',
    'secondary',
    'accent',
  ];

  /**
   * Get the selector.
   *
   * The selector is the class name that will be used to apply the scheme.
   *
   * @return string
   *   The selector.
   */
  public function getSelector():string;

  /**
   * Get the pallets.
   *
   * @return \Drupal\neo_color\PalletInterface[]
   *   The pallets.
   */
  public function getPallets():array;

  /**
   * Get inline css.
   *
   * @return string
   *   The css.
   */
  public function getCss():string;

  /**
   * Get an array of inline css.
   *
   * @return array
   *   The css.
   */
  public function getCssData():array;

  /**
   * Returns the weight of the icon package.
   *
   * @return int
   *   The icon package weight.
   */
  public function getWeight():int;

  /**
   * Sets the weight of the icon package.
   *
   * @param int $weight
   *   The weight to set.
   */
  public function setWeight(int $weight):self;

}
