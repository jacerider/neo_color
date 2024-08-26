<?php

declare(strict_types = 1);

namespace Drupal\neo_color\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\neo_color\PalletInterface;
use Drupal\neo_color\Shade;

/**
 * Defines the pallet entity type.
 *
 * @ConfigEntityType(
 *   id = "neo_pallet",
 *   label = @Translation("Pallet"),
 *   label_collection = @Translation("Pallets"),
 *   label_singular = @Translation("pallet"),
 *   label_plural = @Translation("pallets"),
 *   label_count = @PluralTranslation(
 *     singular = "@count pallet",
 *     plural = "@count pallets",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\neo_color\PalletListBuilder",
 *     "access" = "Drupal\neo_color\PalletAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\neo_color\Form\PalletForm",
 *       "edit" = "Drupal\neo_color\Form\PalletForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "duplicate" = "Drupal\neo_color\Form\PalletForm",
 *     },
 *   },
 *   config_prefix = "neo_pallet",
 *   admin_permission = "administer neo_pallet",
 *   links = {
 *     "collection" = "/admin/config/neo/pallet",
 *     "add-form" = "/admin/config/neo/pallet/add",
 *     "edit-form" = "/admin/config/neo/pallet/{neo_pallet}",
 *     "duplicate-form" = "/admin/config/neo/pallet/{neo_pallet}/duplicate",
 *     "delete-form" = "/admin/config/neo/pallet/{neo_pallet}/delete",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "specific",
 *     "shades",
 *     "content",
 *   },
 * )
 */
final class Pallet extends ConfigEntityBase implements PalletInterface {

  /**
   * The pallet ID.
   */
  protected string|null $id;

  /**
   * The pallet label.
   */
  protected string $label;

  /**
   * Specific colors for shades.
   */
  protected bool $specific;

  /**
   * The pallet shades.
   */
  protected array $shades;

  /**
   * The pallet shade references.
   *
   * @param \Drupal\neo_color\Shade[]
   */
  protected array $shadeReferences;

  /**
   * The pallet content.
   */
  protected array $content = [];

  /**
   * {@inheritdoc}
   */
  public function isSpecific(): bool {
    return !empty($this->specific);
  }

  /**
   * {@inheritdoc}
   */
  public function getShades() {
    if (!isset($this->shadeReferences)) {
      $shades = $this->shades ?? [];
      $darkHex = $this->getContentDarkHex();
      $lightHex = $this->getContentLightHex();
      foreach (PalletInterface::SHADES as $shade) {
        $shade = (string) $shade;
        $color = $shades[$shade]['color'] ?? PalletInterface::DEFAULT_COLOR;
        $dark = !empty($shades[$shade]['dark']) ?? TRUE;
        $content = $dark ? $darkHex : $lightHex;
        $this->shadeReferences[$shade] = new Shade((string) $shade, $color, $content, $dark);
      }
    }
    return $this->shadeReferences;
  }

  /**
   * {@inheritdoc}
   */
  public function getShade($shade) {
    return $this->getShades()[(string) $shade] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentPalletId():string {
    return $this->content['pallet'] ?? 'base';
  }

  /**
   * {@inheritdoc}
   */
  public function getContentPallet():PalletInterface|null {
    $palletId = $this->getContentPalletId();
    if (!$palletId) {
      return NULL;
    }
    return $this->getContentPalletId() === $this->id() ? $this : Pallet::load($this->getContentPalletId());
  }

  /**
   * {@inheritdoc}
   */
  public function getContentLight():string {
    $color = $this->content['light'] ?? '#000000';
    if (substr($color, 0, 1) === '#' && $this->getContentPalletId()) {
      $color = '50';
    }
    return $color;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentLightHex():string {
    $color = $this->getContentLight();
    if ($pallet = $this->getContentPallet()) {
      if ($pallet->id() === $this->id()) {
        $color = $this->shades[$color]['color'];
      }
      else {
        $color = $pallet->getShade($color)->getHex();
      }
    }
    return $color;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentDark():string {
    $color = $this->content['dark'] ?? '#000000';
    if (substr($color, 0, 1) === '#' && $this->getContentPalletId()) {
      $color = '950';
    }
    return $color;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentDarkHex():string {
    $color = $this->getContentDark();
    if ($pallet = $this->getContentPallet()) {
      if ($pallet->id() === $this->id()) {
        $color = $this->shades[$color]['color'];
      }
      else {
        $color = $pallet->getShade($color)->getHex();
      }
    }
    return $color;
  }

  /**
   * {@inheritdoc}
   */
  public function getCss($id = NULL):string {
    $css = [];
    foreach ($this->getCssData($id) as $key => $value) {
      $css[$key] = "$key: $value;";
    }
    return ':root{' . implode('', $css) . '}';
  }

  /**
   * {@inheritdoc}
   */
  public function getCssData($id = NULL, $invert = FALSE):array {
    $css = [];
    $id = $id ?? $this->id();
    $shades = $this->getShades();
    foreach ($shades as $shadeId => $shade) {
      if ($invert) {
        $pos = array_search($shadeId, PalletInterface::SHADES);
        $shade = $shades[array_reverse(PalletInterface::SHADES)[$pos]];
      }
      $rgb = implode(' ', $shade->getRgb());
      $rgbContent = implode(' ', $shade->getContentRgb());
      if ($shadeId == 500) {
        $css["--color-$id"] = $rgb;
        $css["--color-$id-content"] = $rgbContent;
      }
      $css["--color-$id-$shadeId"] = $rgb;
      $css["--color-$id-content-$shadeId"] = $rgbContent;
      if ($id === 'base') {
        [$r, $g, $b] = sscanf($rgb, '%d %d %d');
        $r = round(max(0, $r * 0.65));
        $g = round(max(0, $g * 0.65));
        $b = round(max(0, $b * 0.65));
        $css["--color-shadow-$shadeId"] = "$r $g $b";
      }
    }
    return $css;
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    $order = PalletInterface::PROTECTED;
    $aPlace = array_search($a->id(), $order);
    $bPlace = array_search($b->id(), $order);
    if ($aPlace === FALSE) {
      $aPlace = 1000;
    }
    if ($bPlace === FALSE) {
      $bPlace = 1000;
    }
    if ($aPlace === $bPlace) {
      return strnatcasecmp($a->label(), $b->label());
    }
    return $aPlace <=> $bPlace;
  }

}
