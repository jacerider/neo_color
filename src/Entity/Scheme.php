<?php

declare(strict_types=1);

namespace Drupal\neo_color\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
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
 *     "colorize_offset",
 *     "colorize_natural",
 *     "base",
 *     "primary",
 *     "secondary",
 *     "accent",
 *     "primary_contrast",
 *     "secondary_contrast",
 *     "accent_contrast",
 *     "weight",
 *   },
 * )
 */
final class Scheme extends ConfigEntityBase implements SchemeInterface {

  /**
   * The contrast target a button fill must clear against the surfaces.
   *
   * Brand fidelity is a per-scheme decision, not an engine heuristic: a
   * scheme that wants a role's exact 500 color switches that role's
   * {role}_contrast flag off rather than this target being loosened.
   */
  public const BUTTON_CONTRAST_TARGET = 4.0;

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
   * How far the colorized surface is tinted away from the brand 500 shade.
   *
   * 0 keeps the surface at the exact 500 color; 100 is the full light/dark
   * tint. Only meaningful when colorize is enabled.
   *
   * @var int
   */
  protected $colorize_offset = 100;

  /**
   * Whether the colorized surface follows the base pallet's own saturation.
   *
   * Colorize normally paints the whole base ramp with shade 500's hue and
   * saturation, floored at 45% so the surface still reads as the brand. That
   * is right for a brand pallet, whose saturation is flat across the ramp, but
   * it overrides a pallet authored as a neutral with a saturated dark end —
   * base's light shades sit near 10% saturation against a 38% 500, so the
   * anchor turns an intended near-gray surface into a visible tint.
   *
   * TRUE samples each shade's hue and saturation from the pallet's own
   * lightness→chroma curve instead, keeping the ramp true to the colors the
   * pallet actually defines. Brand pallets are unaffected: with a flat
   * saturation curve the sample and the anchor agree. Only meaningful when
   * colorize is enabled.
   *
   * @var bool
   */
  protected $colorize_natural = FALSE;

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
   * Whether the primary role auto-contrasts against the scheme surface.
   *
   * TRUE (default) lets the pick engine nudge the role's bare token and
   * button fill to a legible shade; FALSE pins them to the raw 500.
   */
  protected bool $primary_contrast = TRUE;

  /**
   * Whether the secondary role auto-contrasts against the scheme surface.
   *
   * @see $primary_contrast
   */
  protected bool $secondary_contrast = TRUE;

  /**
   * Whether the accent role auto-contrasts against the scheme surface.
   *
   * @see $primary_contrast
   */
  protected bool $accent_contrast = TRUE;

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
  public function getPrimary():string {
    return $this->primary;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecondary():string {
    return $this->secondary;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccent():string {
    return $this->accent;
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
    $isColor = $this->get('colorize');
    $offset = (int) ($this->get('colorize_offset') ?? 100);
    $natural = (bool) $this->get('colorize_natural');
    $slotShades = [];
    foreach ($pallets as $id => $pallet) {
      $isBase = $id === 'base';
      $swap = !$isBase && $isColor && $pallets['base']->id() === $pallet->id();
      $palletCss = $pallet->getCssData($id, $isDark, $isColor, $swap, $offset, $natural);
      foreach ($palletCss as $key => $value) {
        $css[$key] = $value;
      }
      $slotShades[$id] = $pallet->getTransformedShades($isDark, $isColor && $isBase, $offset, $natural);
    }
    // Buttons always pick by contrast against the surface (non-tonal). The
    // colorized surface now follows the mode (light/dark) like a normal scheme,
    // so this gives dark buttons on light schemes and light buttons on dark
    // schemes for colorized and non-colorized alike — consistent everywhere.
    $contrast = [];
    foreach (['primary', 'secondary', 'accent'] as $slot) {
      $contrast[$slot] = (bool) $this->get($slot . '_contrast');
    }
    foreach (static::buildButtonCssVars($slotShades, $pallets, FALSE, $contrast) as $key => $value) {
      $css[$key] = $value;
    }
    return $css;
  }

  /**
   * Build contrast-aware button CSS variables for a set of scheme slots.
   *
   * The neo_theme button utilities resolve their colors through the
   * `--btn[-slot]-bg-color` / `--btn[-slot]-content-color` (+ `-hover`) hooks.
   * Anchoring those to a fixed shade (500) breaks down inside schemes: in dark
   * schemes the 500 midpoint never moves, and in colorized schemes the slot
   * color can be tonally identical to the surface itself. Instead, pick the
   * shade nearest 500 that clears a 4:1 contrast ratio against the scheme's
   * surfaces (base-0 and base-100) — keeping the hue while guaranteeing that
   * solid buttons stand off the surface and outline/text buttons stay
   * legible. Schemes that want a role's exact brand color opt out per role
   * via the {role}_contrast flags rather than a looser global target.
   *
   * @param array $slotShades
   *   Transformed shade ramps keyed by slot (base, primary, secondary, accent).
   * @param \Drupal\neo_color\PalletInterface[] $pallets
   *   The slot pallets, used for their content color candidates.
   * @param bool $tonal
   *   TRUE for colorized schemes: buttons follow the scheme's tone (dark
   *   buttons on dark schemes, light on light) instead of opposing it, so
   *   sibling schemes read predictably. The base slot is exempt — its scaled
   *   ramp's far end already matches the scheme tone, so the default walk is
   *   tonal for it by construction.
   * @param array $contrast
   *   Auto-contrast flags keyed by slot; a missing slot defaults to TRUE.
   *   FALSE pins the slot's bare --color-{slot} token and button fill to the
   *   raw 500 shade. Links and outline/text buttons stay contrast-picked
   *   regardless — they render as text, where pinning could ship unreadable
   *   pages.
   *
   * @return array
   *   CSS variable key/value pairs.
   */
  public static function buildButtonCssVars(array $slotShades, array $pallets, bool $tonal = FALSE, array $contrast = []): array {
    $css = [];
    if (!isset($slotShades['base'])) {
      return $css;
    }
    $surfaces = [
      $slotShades['base'][0]->getHex(),
      $slotShades['base'][100]->getHex(),
    ];
    foreach ($slotShades as $slot => $shades) {
      // A pinned slot (auto-contrast off) passes a 0.0 target: the walk
      // accepts 500 immediately while the hover step still runs, so hover
      // feedback survives pinning.
      $target = ($contrast[$slot] ?? TRUE) ? self::BUTTON_CONTRAST_TARGET : 0.0;
      [$pick, $hover] = static::pickButtonShades($shades, $surfaces, $tonal && $slot !== 'base', $target);
      $lightHex = $pallets[$slot]->getContentLightHex();
      $darkHex = $pallets[$slot]->getContentDarkHex();
      $prefix = $slot === 'base' ? '--btn' : "--btn-$slot";
      $css["$prefix-bg-color"] = $pick->getHex();
      $css["$prefix-content-color"] = Shade::pickContent($pick->getHex(), $lightHex, $darkHex)['hex'];
      $css["$prefix-bg-color-hover"] = $hover->getHex();
      $css["$prefix-content-color-hover"] = Shade::pickContent($hover->getHex(), $lightHex, $darkHex)['hex'];
      if ($slot === 'base') {
        // The plain outline/text buttons use the picked color as *text*, which
        // needs a stronger (4.5:1) ratio than a button background. The base-0
        // content already follows the scheme's dark setting, so it serves as
        // the direction-consistent fallback when the ramp can't deliver.
        $preferredHex = $slotShades['base'][0]->getContentHex();
        $css['--btn-line-color'] = static::pickLineColor($shades, $surfaces, $lightHex, $darkHex, $preferredHex, $tonal);
      }
    }
    // Re-pin the bare --color-{role} tokens (text-primary, bg-secondary,
    // border-accent, and their hover: variants) so plain color utilities stay
    // legible when a scheme is wrapped around them. These default to the role's
    // 500 brand shade; on schemes where 500 has little or no contrast against
    // the surface — colorized schemes at a low offset, or any scheme mapping a
    // role to the same pallet as base — that shade *is* the surface, so
    // text-primary turns invisible and bg-primary vanishes into the background.
    // Walk to the nearest-to-500 shade that clears text-grade contrast (4.5:1,
    // the strictest use, so both text and fill stay legible) and move its
    // content pair with it so bg-{role} + text-{role}-content keeps working. In
    // ordinary schemes 500 already clears the target, so the token is left
    // unchanged. Base is intentionally excluded: bg-base is a subtle surface
    // step, not a contrast element, and text-base is a font size, not a color.
    // A scheme can opt a role out via its {role}_contrast flag, pinning the
    // pair to the raw 500 — a designer decision made against the scheme
    // form's live preview.
    foreach (['primary', 'secondary', 'accent'] as $slot) {
      if (!isset($slotShades[$slot])) {
        continue;
      }
      $pick = $slotShades[$slot][500];
      if ($contrast[$slot] ?? TRUE) {
        [$pick] = static::pickButtonShades($slotShades[$slot], $surfaces, FALSE, 4.5);
      }
      $css["--color-$slot"] = implode(' ', $pick->getRgb());
      $css["--color-$slot-content"] = implode(' ', $pick->getContentRgb());
    }
    // Links are colored *text* on the scheme surface, so they get a
    // text-grade (4.5:1) pick from the primary slot ramp rather than the bare
    // --color-primary token (the 500 brand shade), which can sit on — or be —
    // the surface itself on colorized schemes. The hover color is the next
    // perceptibly different step along the same walk, replacing the fixed
    // primary-600/800 pairing that ignored the surface entirely.
    if (isset($slotShades['primary'])) {
      [$pick, $hover] = static::pickButtonShades($slotShades['primary'], $surfaces, FALSE, 4.5);
      $css['--link-color'] = $pick->getHex();
      $css['--link-color-hover'] = $hover->getHex();
    }
    return $css;
  }

  /**
   * Pick a text-grade (4.5:1) line color for outline/text buttons.
   *
   * Non-tonal (default): prefers ramp shades on the text-direction side of
   * the surface (dark on light schemes, light on dark) that clear 4.5:1, then
   * the scheme-direction content color (matching text-default) when it is at
   * least visible, then any ramp shade, and finally whichever content color
   * reads best.
   *
   * Tonal (colorized): walks the base ramp upward — its far end matches the
   * scheme tone by construction — accepting the first shade clearing 4.5:1 or
   * the best upward shade that is at least visible, so the line color stays
   * in the same tonal family as the scheme's buttons.
   *
   * @param \Drupal\neo_color\Shade[] $shades
   *   The base shade ramp, keyed by integer shade id.
   * @param string[] $surfaces
   *   The surface hex colors to contrast against.
   * @param string $lightHex
   *   The pallet's light content hex.
   * @param string $darkHex
   *   The pallet's dark content hex.
   * @param string $preferredHex
   *   The scheme-direction content hex (dark on light schemes, light on dark
   *   schemes).
   * @param bool $tonal
   *   TRUE for colorized schemes.
   *
   * @return string
   *   The picked hex color.
   */
  protected static function pickLineColor(array $shades, array $surfaces, string $lightHex, string $darkHex, string $preferredHex, bool $tonal = FALSE): string {
    $minContrast = function (string $hex) use ($surfaces): float {
      $min = NULL;
      foreach ($surfaces as $surface) {
        $ratio = Shade::contrastRatio($hex, $surface);
        $min = $min === NULL ? $ratio : min($min, $ratio);
      }
      return $min ?? 0.0;
    };
    if ($tonal) {
      $bestHex = NULL;
      $bestRatio = 0.0;
      foreach ([500, 600, 700, 800, 900, 950] as $shadeId) {
        $hex = $shades[$shadeId]->getHex();
        $ratio = $minContrast($hex);
        if ($ratio >= 4.5) {
          return $hex;
        }
        if ($ratio > $bestRatio) {
          $bestRatio = $ratio;
          $bestHex = $hex;
        }
      }
      if ($bestHex !== NULL && $bestRatio >= 2.0) {
        return $bestHex;
      }
      return $minContrast($darkHex) >= $minContrast($lightHex) ? $darkHex : $lightHex;
    }
    // Only ramp shades on the text-direction side of the surface qualify
    // (lighter than it on dark schemes, darker on light ones), so the line
    // color always lands on the same side as the scheme's text.
    $preferLight = Shade::relativeLuminance($preferredHex) > 0.5;
    $surfaceLums = array_map([Shade::class, 'relativeLuminance'], $surfaces);
    $onPreferredSide = function (string $hex) use ($preferLight, $surfaceLums): bool {
      $lum = Shade::relativeLuminance($hex);
      return $preferLight ? $lum > max($surfaceLums) : $lum < min($surfaceLums);
    };
    foreach ([500, 600, 400, 700, 300, 800, 200, 900, 100, 950, 50] as $shadeId) {
      $hex = $shades[$shadeId]->getHex();
      if ($onPreferredSide($hex) && $minContrast($hex) >= 4.5) {
        return $hex;
      }
    }
    // Guard against the primary surface (base-0) only — at low colorize
    // offsets base-100 sits between the surface and the far end, and the
    // minimum over both would crush the mode-correct ink and flip the line
    // color against the mode per brand color.
    if (Shade::contrastRatio($preferredHex, $surfaces[0]) >= 2.0) {
      return $preferredHex;
    }
    foreach ([500, 600, 400, 700, 300, 800, 200, 900, 100, 950, 50] as $shadeId) {
      if ($minContrast($shades[$shadeId]->getHex()) >= 4.5) {
        return $shades[$shadeId]->getHex();
      }
    }
    return $minContrast($darkHex) >= $minContrast($lightHex) ? $darkHex : $lightHex;
  }

  /**
   * Pick the button and button-hover shades for a ramp against surfaces.
   *
   * Walks the preferred side of the ramp from 500 outward and returns the
   * first shade clearing the contrast target against every surface, or the best
   * preferred-side shade when it is at least visible (2:1). Only when the
   * preferred side is invisible does it consider the other side, and finally
   * the overall best.
   *
   * The preferred side is upward (600..950) by default — away from the
   * scheme's tone, since dark scheme ramps are pre-reversed. Tonal mode
   * (colorized scheme slots) prefers downward (400..50) instead, so buttons
   * follow the scheme's tone: dark buttons on dark schemes, light buttons on
   * light schemes. Either way the choice is structural, so sibling schemes
   * with different brand colors read identically. The hover shade steps
   * further in whichever direction has more contrast, so hover always moves
   * away from the surface tone.
   *
   * @param \Drupal\neo_color\Shade[] $shades
   *   The shade ramp, keyed by integer shade id.
   * @param string[] $surfaces
   *   The surface hex colors to contrast against.
   * @param bool $tonal
   *   TRUE to prefer the scheme-tone side of the ramp (colorized slots).
   * @param float $contrastTarget
   *   The contrast ratio a shade must clear to be picked. The default
   *   BUTTON_CONTRAST_TARGET (4.0) is fill-grade for solid buttons; use 4.5
   *   for text-grade picks such as link colors, or 0.0 to pin the pick to
   *   500 (auto-contrast off).
   *
   * @return \Drupal\neo_color\Shade[]
   *   A two-element array: the picked shade and the hover shade.
   */
  protected static function pickButtonShades(array $shades, array $surfaces, bool $tonal = FALSE, float $contrastTarget = self::BUTTON_CONTRAST_TARGET): array {
    $ramp = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
    $minContrast = function (int $shadeId) use ($shades, $surfaces): float {
      $min = NULL;
      foreach ($surfaces as $surface) {
        $ratio = Shade::contrastRatio($shades[$shadeId]->getHex(), $surface);
        $min = $min === NULL ? $ratio : min($min, $ratio);
      }
      return $min ?? 0.0;
    };
    $preferredSide = $tonal ? [500, 400, 300, 200, 100, 50] : [500, 600, 700, 800, 900, 950];
    $otherSide = $tonal ? [600, 700, 800, 900, 950] : [400, 300, 200, 100, 50];
    $pickId = NULL;
    $bestId = 500;
    $bestRatio = 0.0;
    foreach ($preferredSide as $shadeId) {
      $ratio = $minContrast($shadeId);
      if ($ratio > $bestRatio) {
        $bestRatio = $ratio;
        $bestId = $shadeId;
      }
      if ($pickId === NULL && $ratio >= $contrastTarget) {
        $pickId = $shadeId;
      }
    }
    if ($pickId === NULL && Shade::contrastRatio($shades[$bestId]->getHex(), $surfaces[0]) >= 2.0) {
      // Mirror the text-default guard: stay in the mode's direction whenever
      // the result is visible, even if it falls short of the target. The
      // guard is measured against the primary surface (base-0) only: at low
      // colorize offsets base-100 sits between the surface and the far end,
      // and judging by the minimum over both would crush the mode-correct
      // side and flip buttons/links against the mode per brand color.
      $pickId = $bestId;
    }
    if ($pickId === NULL) {
      foreach ($otherSide as $shadeId) {
        $ratio = $minContrast($shadeId);
        if ($ratio > $bestRatio) {
          $bestRatio = $ratio;
          $bestId = $shadeId;
        }
        if ($pickId === NULL && $ratio >= $contrastTarget) {
          $pickId = $shadeId;
        }
      }
    }
    $pickId = $pickId ?? $bestId;
    // Hover: walk away from the surface tone (the direction with more
    // contrast) until the hover shade differs perceptibly (>= 1.2:1) from the
    // picked shade. When the pick sits near a ramp end and that direction
    // can't deliver the delta, fall back to the other direction so hover
    // feedback never collapses.
    $index = array_search($pickId, $ramp);
    $walk = function (int $direction) use ($ramp, $shades, $pickId, $index): array {
      $hoverId = NULL;
      $ratio = 0.0;
      for ($i = $index + $direction; isset($ramp[$i]); $i += $direction) {
        $hoverId = $ramp[$i];
        $ratio = Shade::contrastRatio($shades[$pickId]->getHex(), $shades[$hoverId]->getHex());
        if ($ratio >= 1.2) {
          break;
        }
      }
      return [$hoverId, $ratio];
    };
    $up = $ramp[$index + 1] ?? NULL;
    $down = $ramp[$index - 1] ?? NULL;
    $direction = 1;
    if ($up === NULL) {
      $direction = -1;
    }
    elseif ($down !== NULL && $minContrast($down) > $minContrast($up)) {
      $direction = -1;
    }
    [$hoverId, $ratio] = $walk($direction);
    if ($ratio < 1.2) {
      [$otherId, $otherRatio] = $walk(-$direction);
      if ($otherRatio > $ratio) {
        $hoverId = $otherId;
      }
    }
    return [$shades[$pickId], $shades[$hoverId ?? $pickId]];
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
