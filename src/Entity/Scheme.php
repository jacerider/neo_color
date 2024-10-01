<?php

declare(strict_types = 1);

namespace Drupal\neo_color\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\neo_color\PalletInterface;
use Drupal\neo_color\SchemeInterface;
use Drupal\neo_color\Shade;

/**
 * Defines the scheme entity type.
 *
 * @ConfigEntityType(
 *   id = "neo_scheme",
 *   label = @Translation("Scheme"),
 *   label_collection = @Translation("Schemes"),
 *   label_singular = @Translation("scheme"),
 *   label_plural = @Translation("schemes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count scheme",
 *     plural = "@count schemes",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\neo_color\SchemeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\neo_color\Form\SchemeForm",
 *       "edit" = "Drupal\neo_color\Form\SchemeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   config_prefix = "neo_scheme",
 *   admin_permission = "administer neo_scheme",
 *   links = {
 *     "collection" = "/admin/config/neo/scheme",
 *     "add-form" = "/admin/config/neo/scheme/add",
 *     "edit-form" = "/admin/config/neo/scheme/{neo_scheme}",
 *     "delete-form" = "/admin/config/neo/scheme/{neo_scheme}/delete",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "dark",
 *     "colorize",
 *     "base",
 *     "primary",
 *     "secondary",
 *     "accent",
 *     "weight",
 *   },
 * )
 */
final class Scheme extends ConfigEntityBase implements SchemeInterface {

  /**
   * The scheme ID.
   */
  protected string $id;

  /**
   * The scheme label.
   */
  protected string $label;

  /**
   * The scheme is dark.
   */
  protected bool $dark = FALSE;

  /**
   * The base pallet.
   */
  protected string $base = 'base';

  /**
   * The primary pallet.
   */
  protected string $primary = 'primary';

  /**
   * The secondary pallet.
   */
  protected string $secondary = 'secondary';

  /**
   * The accent pallet.
   */
  protected string $accent = 'accent';

  /**
   * The scheme weight.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getSelector():string {
    $id = $this->isNew() ? 'new-' . $this->uuid() : $this->id();
    return 'scheme-' . str_replace('_', '-', $id);
  }

  /**
   * {@inheritdoc}
   */
  public function getBase():string {
    return $this->base;
  }

  /**
   * {@inheritdoc}
   */
  public function getCss():string {
    $css = [];
    foreach ($this->getCssData() as $key => $value) {
      $css[$key] = "$key: $value;";
    }
    return '.' . $this->getSelector() . '{' . implode('', $css) . '}';
  }

  /**
   * {@inheritdoc}
   */
  public function getCssData():array {
    $css = [];
    $pallets = $this->getPallets();
    $isDark = $this->get('dark');
    foreach ($pallets as $id => $pallet) {
      $palletCss = $pallet->getCssData($id, $isDark);
      $originalPalletCss = $palletCss;
      if ($this->get('colorize')) {
        if ($id === 'base') {
          $offsetShades = array_combine(array_slice(PalletInterface::SHADES, 0, 6), array_reverse($this->generateGradients($pallet->getShade(500)->getHex(), $pallet->getShade(400)->getHex(), 6)))
            + array_combine(array_slice(PalletInterface::SHADES, 6), array_reverse($this->generateGradients($pallet->getShade(700)->getHex(), $pallet->getShade(500)->getHex(), 5)));
          if ($isDark) {
            $offsetShades = array_combine(PalletInterface::SHADES, array_reverse($offsetShades));
          }
          foreach ($offsetShades as $shade => $hex) {
            $contentShade = $shade === '500' ? '500' : ((int) $shade < 500 ? '700' : '400');
            $offsetShade = new Shade((string) $shade, $hex, $pallet->getShade($contentShade)->getContentHex(), $isDark);
            $palletCss['--color-' . $id . '-' . $shade] = implode(' ', $offsetShade->getRgb());
            $palletCss['--color-' . $id . '-content-' . $shade] = implode(' ', $offsetShade->getContentRgb());
            [$r, $g, $b] = sscanf($palletCss['--color-' . $id . '-' . $shade], '%d %d %d');
            $r = round(max(0, $r * 0.65));
            $g = round(max(0, $g * 0.65));
            $b = round(max(0, $b * 0.65));
            $palletCss['--color-shadow-' . $shade] = "$r $g $b";
          }
          $palletCss['--color-' . $id . '-0'] = $palletCss['--color-' . $id . '-500'];
          $palletCss['--color-' . $id . '-content-0'] = $palletCss['--color-' . $id . '-content-500'];
        }
        elseif ($pallets['base']->id() === $pallet->id()) {
          // This happens when a scheme pallet is set to use the same pallet as
          // the base pallet.
          $swap = [
            50 => 400,
            100 => 300,
            200 => 200,
            300 => 100,
            400 => 50,
            600 => 50,
            700 => 100,
            800 => 200,
            900 => 300,
            950 => 400,
          ];
          foreach ($swap as $from => $to) {
            $palletCss['--color-' . $id . '-' . $from] = $originalPalletCss['--color-' . $id . '-' . $to];
            $palletCss['--color-' . $id . '-content-' . $from] = $originalPalletCss['--color-' . $id . '-content-' . $to];
          }
          $shade500 = new Shade('500', $isDark ? $pallet->getContentDarkHex() : $pallet->getContentLightHex(), $isDark ? $pallet->getContentLightHex() : $pallet->getContentDarkHex(), $isDark);
          $palletCss['--color-' . $id . '-500'] = implode(' ', $shade500->getRgb());
          $palletCss['--color-' . $id . '-content-500'] = implode(' ', $shade500->getContentRgb());
          $palletCss['--color-' . $id] = $originalPalletCss['--color-' . $id . '-500'];
          $palletCss['--color-' . $id . '-content'] = $originalPalletCss['--color-' . $id . '-content-500'];
        }
      }
      foreach ($palletCss as $key => $value) {
        $css[$key] = $value;
      }
    }
    // When dark mode, invert the shades.
    if ($isDark) {
      // $css['--color-white'] = '0 0 0';
      // $css['--color-white-content'] = '255 255 255';
      // $css['--color-black'] = '255 255 255';
      // $css['--color-black-content'] = '255 255 255';
    }
    return $css;
  }

  /**
   * {@inheritdoc}
   */
  public function getPallets():array {
    $pallets = [];
    foreach (self::PALLETS as $key) {
      $pallets[$key] = \Drupal::entityTypeManager()->getStorage('neo_pallet')->load($this->get($key));
    }
    return $pallets;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight(int $weight):self {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight():int {
    return $this->weight;
  }

  /**
   * Sorts by weight.
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    /** @var \Drupal\neo_icon\Entity\IconLibrary $a */
    /** @var \Drupal\neo_icon\Entity\IconLibrary $b */
    // Separate enabled from disabled.
    $status = (int) $b->status() - (int) $a->status();
    if ($status !== 0) {
      return $status;
    }
    return $a->getWeight() - $b->getWeight();
  }

  /**
   * Generate gradients.
   *
   * @param string $colorBegin
   *   The beginning color.
   * @param string $colorEnd
   *   The ending color.
   * @param int $steps
   *   The number of steps.
   *
   * @return array
   *   The gradient.
   */
  protected function generateGradients($colorBegin = 0x000000, $colorEnd = 0xffffff, $steps = 10) {
    $colorBegin = hexdec(str_replace('#', '', (string) $colorBegin));
    $colorEnd = hexdec(str_replace('#', '', (string) $colorEnd));

    $colorBegin = (($colorBegin >= 0x000000) && ($colorBegin <= 0xffffff)) ? $colorBegin : 0x000000;
    $colorEnd = (($colorEnd >= 0x000000) && ($colorEnd <= 0xffffff)) ? $colorEnd : 0xffffff;
    $steps = (($steps > 0) && ($steps < 256)) ? $steps : 16;

    $theR0 = ($colorBegin & 0xff0000) >> 16;
    $theG0 = ($colorBegin & 0x00ff00) >> 8;
    $theB0 = ($colorBegin & 0x0000ff) >> 0;

    $theR1 = ($colorEnd & 0xff0000) >> 16;
    $theG1 = ($colorEnd & 0x00ff00) >> 8;
    $theB1 = ($colorEnd & 0x0000ff) >> 0;

    $result = [];

    for ($i = 1; $i <= $steps; $i++) {
      $theR = $this->generateGradientsInterpolate($theR0, $theR1, $i, $steps);
      $theG = $this->generateGradientsInterpolate($theG0, $theG1, $i, $steps);
      $theB = $this->generateGradientsInterpolate($theB0, $theB1, $i, $steps);

      $theVal = ((($theR << 8) | $theG) << 8) | $theB;
      $result[] = strtolower(sprintf("#%06X", $theVal));
    }
    return $result;
  }

  /**
   * Generate gradient interpolation.
   *
   * @return int
   *   The gradient interpolation.
   */
  protected function generateGradientsInterpolate($pBegin, $pEnd, $pStep, $pMax): int {
    if ($pBegin < $pEnd) {
      return (int) (($pEnd - $pBegin) * ($pStep / $pMax)) + $pBegin;
    }
    else {
      return (int) (($pBegin - $pEnd) * (1 - ($pStep / $pMax))) + $pEnd;
    }
  }

}
