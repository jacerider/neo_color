<?php

declare(strict_types=1);

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
  public function getShades(): array {
    if (!isset($this->shadeReferences)) {
      $shades = $this->shades ?? [];
      // The 0 shade is always white.
      $shades[0] = [
        'color' => '#ffffff',
        'dark' => TRUE,
      ];
      $darkHex = $this->getContentDarkHex();
      $lightHex = $this->getContentLightHex();
      $nums = PalletInterface::SHADES;
      array_unshift($nums, 0);
      foreach ($nums as $shade) {
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
   * Get the colored shades.
   */
  public function getColoredShades(bool $dark = FALSE): array {
    $shades = $this->shades ?? [];
    // The 0 shade is always white.
    $shades[0] = [
      'color' => '#ffffff',
      'dark' => TRUE,
    ];

    $shadeReferences = [];
    $newShades = ['0' => $shades['500']];
    $shadeMap = $dark ? [
      '50' => ['500', '600'],
      '100' => '600',
      '200' => ['600', '700'],
      '300' => '700',
      '400' => ['700', '800'],
      '500' => '50',
      '600' => ['50', '100'],
      '700' => '100',
      '800' => ['100', '200'],
      '900' => '200',
      '950' => ['200', '300'],
    ] : [
      '50' => ['500', '400'],
      '100' => '400',
      '200' => ['400', '300'],
      '300' => '300',
      '400' => ['300', '200'],
      '500' => '200',
      '600' => ['200', '100'],
      '700' => '100',
      '800' => ['100', '50'],
      '900' => '50',
      '950' => ['50', '0'],
    ];
    foreach ($shadeMap as $targetShade => $sourceShades) {
      if (is_array($sourceShades)) {
        [$color1, $color2] = $sourceShades;
        $newShades[$targetShade] = [
          'color' => $this->interpolateHexColors($shades[$color1]['color'], $shades[$color2]['color']),
          'dark' => $shades[$color1]['dark'],
        ];
      }
      else {
        $newShades[$targetShade] = $shades[$sourceShades];
      }
    }
    $darkHex = $this->getContentDarkHex();
    $lightHex = $this->getContentLightHex();
    foreach ($newShades as $shadeId => $shade) {
      $dark = !empty($shade['dark']);
      $shadeReferences[$shadeId] = new Shade((string) $shadeId, $shade['color'], $dark ? $darkHex : $lightHex, $dark);
    }
    return $shadeReferences;
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
    $color = $this->content['light'] ?? '#ffffff';
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
  public function getCssData($id = NULL, $dark = FALSE, $color = FALSE, $swap = FALSE):array {
    $css = [];
    $id = $id ?? $this->id();
    $shades = $this->getShades();
    foreach ($shades as $shadeId => $shade) {
      if ($dark) {
        $pos = array_search($shadeId, PalletInterface::SHADES);
        $shade = $shades[array_reverse(PalletInterface::SHADES)[$pos]];
      }
      $rgb = implode(' ', $swap ? ($dark ? $shades[0]->getRgb() : $shades[950]->getRgb()) : $shade->getRgb());
      $rgbContent = implode(' ', $swap ? ($dark ? $shades[0]->getContentRgb() : $shades[950]->getContentRgb()) : $shade->getContentRgb());
      $css["--color-$id-$shadeId"] = $rgb;
      $css["--color-$id-content-$shadeId"] = $rgbContent;
      if ($shadeId == 500) {
        $css["--color-$id"] = $rgb;
        $css["--color-$id-content"] = $rgbContent;
      }
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
   * Interpolate between two hex colors.
   *
   * @param string $color1
   *   The first color in hex format.
   * @param string $color2
   *   The second color in hex format.
   * @param float $factor
   *   The interpolation factor (0.0 to 1.0).
   *
   * @return string
   *   The interpolated color in hex format.
   */
  protected function interpolateHexColors($color1, $color2, $factor = 0.5) {
    // Remove # if present.
    $color1 = ltrim($color1, '#');
    $color2 = ltrim($color2, '#');

    // Convert hex to RGB.
    $r1 = hexdec(substr($color1, 0, 2));
    $g1 = hexdec(substr($color1, 2, 2));
    $b1 = hexdec(substr($color1, 4, 2));

    $r2 = hexdec(substr($color2, 0, 2));
    $g2 = hexdec(substr($color2, 2, 2));
    $b2 = hexdec(substr($color2, 4, 2));

    // Interpolate each channel.
    $r = round($r1 + ($r2 - $r1) * $factor);
    $g = round($g1 + ($g2 - $g1) * $factor);
    $b = round($b1 + ($b2 - $b1) * $factor);

    // Convert back to hex and format.
    return sprintf('#%02x%02x%02x', $r, $g, $b);
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
