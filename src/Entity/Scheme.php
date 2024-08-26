<?php

declare(strict_types = 1);

namespace Drupal\neo_color\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\neo_color\PalletInterface;
use Drupal\neo_color\SchemeInterface;

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
    foreach ($pallets as $id => $pallet) {
      $isDark = $this->get('dark');
      $palletCss = $pallet->getCssData($id, $isDark);
      // When dark mode, invert the shades.
      if ($isDark) {
        $palletCss['--color-white'] = '0 0 0';
        $palletCss['--color-black'] = '255 255 255';
      }
      if ($this->get('colorize')) {
        if ($id === 'base') {
          $swap = [
            600 => 500,
            700 => 500,
            800 => 500,
            900 => 500,
            950 => 500,
          ];
          foreach ($swap as $from => $to) {
            $palletCss['--color-' . $id . '-' . $from] = $palletCss['--color-' . $id . '-' . $to];
            $palletCss['--color-' . $id . '-content-' . $from] = $palletCss['--color-' . $id . '-content-' . $to];
            $palletCss['--color-shadow-' . $from] = $palletCss['--color-shadow-' . $to];
          }
        }
        elseif ($pallets['base']->id() === $pallet->id()) {
          foreach (PalletInterface::SHADES as $shade) {
            $palletCss['--color-' . $id . '-' . $shade] = $palletCss['--color-' . $id . '-50'];
            $palletCss['--color-' . $id . '-content-' . $shade] = $palletCss['--color-' . $id . '-content-50'];
          }
        }
      }
      foreach ($palletCss as $key => $value) {
        $css[$key] = $value;
      }
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

}
