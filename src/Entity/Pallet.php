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
   * {@inheritdoc}
   */
  public function getShade($shade) {
    return $this->getShades()[(string) $shade] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawShadeHex($shade): string {
    if ((string) $shade === '0') {
      // The 0 shade is always white (matches getShades()).
      return '#ffffff';
    }
    return $this->shades[(string) $shade]['color'] ?? PalletInterface::DEFAULT_COLOR;
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
    return $palletId === $this->id() ? $this : Pallet::load($palletId);
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
      // Read the raw configured hex rather than $pallet->getShade()->getHex().
      // getShade() builds the full Shade ramp, which itself calls
      // getContentLightHex()/getContentDarkHex() to pick each shade's text
      // color — so a cross-pallet (or cyclic) content reference re-enters
      // getShades() before $shadeReferences is set and exhausts memory.
      $color = $pallet->getRawShadeHex($color);
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
      // Read the raw configured hex rather than $pallet->getShade()->getHex().
      // See getContentLightHex() for why routing through getShades() here
      // recurses on cross-pallet (or cyclic) content references.
      $color = $pallet->getRawShadeHex($color);
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
  public function getCssData($id = NULL, $dark = FALSE, $color = FALSE, $swap = FALSE, int $colorizeOffset = 100, bool $naturalSaturation = FALSE):array {
    $css = [];
    $id = $id ?? $this->id();
    $shades = $this->getTransformedShades($dark, $color && $id === 'base', $colorizeOffset, $naturalSaturation);
    $shadowHsl = $id === 'base' ? $this->getShadowAnchorHsl($shades) : NULL;
    // Luminance of the scheme surface (base-0). Shadows are clamped to stay
    // darker than this so a shadow never lightens the surface it falls on.
    $surfaceLum = $id === 'base' ? $this->rgbLuminance($shades[0]->getRgb()) : 0.0;
    // The bare `--color-$id` token (bg-primary, text-accent…) is always the
    // 500 brand shade. Colorized schemes now follow the mode like normal ones
    // (brand-tinted light/dark surface), so the brand tokens need no special
    // casing — bg-primary is the vivid brand on every scheme.
    $defaultShade = 500;
    foreach ($shades as $shadeId => $shade) {
      $rgb = implode(' ', $shade->getRgb());
      $rgbContent = implode(' ', $shade->getContentRgb());
      $css["--color-$id-$shadeId"] = $rgb;
      $css["--color-$id-$shadeId-content"] = $rgbContent;
      if ($shadeId === $defaultShade) {
        $css["--color-$id"] = $rgb;
        $css["--color-$id-content"] = $rgbContent;
      }
      if ($id === 'base') {
        $css["--color-shadow-$shadeId"] = $this->getShadowRgb($shade, $shadowHsl, $surfaceLum);
      }
    }
    return $css;
  }

  /**
   * Get the hue/saturation anchor for shadow colors.
   *
   * Shadows take their hue and saturation from the ramp's most chromatic
   * shade so they stay tinted with the base color. Darkening a shade's RGB
   * directly collapses to neutral gray on near-white/desaturated shades
   * (e.g. the light end of a colorized base ramp), which has no hue left to
   * preserve.
   *
   * @param \Drupal\neo_color\Shade[] $shades
   *   The base shade ramp.
   *
   * @return array
   *   The anchor shade's HSL values (h 0-360, s 0-100, l 0-100).
   */
  protected function getShadowAnchorHsl(array $shades): array {
    $anchor = NULL;
    $maxChroma = -1;
    foreach ($shades as $shade) {
      $rgb = $shade->getRgb();
      $chroma = max($rgb) - min($rgb);
      if ($chroma > $maxChroma) {
        $maxChroma = $chroma;
        $anchor = $shade;
      }
    }
    return $anchor->getHsl();
  }

  /**
   * Get the shadow color for a shade as a CSS RGB triplet.
   *
   * A shadow must always be darker than the surface it falls on — it can never
   * lighten anything. The color keeps the base ramp's brand hue/saturation (so
   * shadows are tinted, never a flat gray) at a darkened lightness, then is
   * clamped so its luminance stays below both the shade it belongs to and the
   * scheme surface (base-0). The clamp is what fixes light/colorized schemes,
   * where a shade's own lightness would otherwise leave the "shadow" lighter
   * than the surface and read as a glow.
   *
   * @param \Drupal\neo_color\Shade $shade
   *   The shade to compute a shadow for.
   * @param array $anchorHsl
   *   The anchor HSL values from getShadowAnchorHsl().
   * @param float $surfaceLum
   *   The luminance of the scheme surface (base-0).
   *
   * @return string
   *   The shadow color as a space-separated RGB triplet.
   */
  protected function getShadowRgb(Shade $shade, array $anchorHsl, float $surfaceLum): string {
    $hsl = $shade->getHsl();
    // Use the anchor's saturation only — never the shade's own. HSL saturation
    // is numerically inflated for near-white/near-black shades (a chroma of
    // 5/255 at L 0.99 computes as ~100% saturation), which painted a vivid
    // brand-hue swatch where a barely-tinted shadow was expected (e.g. a warm
    // base-50 producing a pure-orange shadow-50). The anchor is the ramp's
    // most chromatic shade, so its saturation already carries the tint.
    $sat = $anchorHsl['s'] / 100;
    $rgb = $this->hslToRgb((float) $anchorHsl['h'], $sat, ($hsl['l'] / 100) * 0.6);
    // Never lighter than the shade itself or the surface it falls on. Scale
    // toward black (preserves hue) until the luminance clears the darker of
    // the two by a clear margin so the shadow always reads as a shadow.
    // Iterate: a single (cap/lum)^(1/2.4) scale assumes pure power-law sRGB,
    // but the +0.055 linearization offset makes it undershoot — against
    // near-black surfaces the result could end up lighter than the surface.
    $cap = min($this->rgbLuminance($shade->getRgb()), $surfaceLum) * 0.65;
    $lum = $this->rgbLuminance($rgb);
    for ($i = 0; $i < 8 && $lum > $cap && $lum > 0; $i++) {
      $k = ($cap / $lum) ** (1 / 2.4);
      $rgb = [$rgb[0] * $k, $rgb[1] * $k, $rgb[2] * $k];
      $lum = $this->rgbLuminance($rgb);
    }
    return implode(' ', array_map(fn ($c) => (int) round($c), $rgb));
  }

  /**
   * Get the WCAG relative luminance of an RGB triplet.
   *
   * @param int[] $rgb
   *   The RGB values (0-255).
   *
   * @return float
   *   The relative luminance (0.0 - 1.0).
   */
  protected function rgbLuminance(array $rgb): float {
    $lin = static fn ($c) => ($c = $c / 255) <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    return 0.2126 * $lin($rgb[0]) + 0.7152 * $lin($rgb[1]) + 0.0722 * $lin($rgb[2]);
  }

  /**
   * Convert HSL to RGB.
   *
   * @param float $h
   *   The hue (0-360).
   * @param float $s
   *   The saturation (0-1).
   * @param float $l
   *   The lightness (0-1).
   *
   * @return int[]
   *   The RGB values (0-255).
   */
  protected function hslToRgb(float $h, float $s, float $l): array {
    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m = $l - $c / 2;
    if ($h < 60) {
      [$r, $g, $b] = [$c, $x, 0];
    }
    elseif ($h < 120) {
      [$r, $g, $b] = [$x, $c, 0];
    }
    elseif ($h < 180) {
      [$r, $g, $b] = [0, $c, $x];
    }
    elseif ($h < 240) {
      [$r, $g, $b] = [0, $x, $c];
    }
    elseif ($h < 300) {
      [$r, $g, $b] = [$x, 0, $c];
    }
    else {
      [$r, $g, $b] = [$c, 0, $x];
    }
    return [
      (int) round(($r + $m) * 255),
      (int) round(($g + $m) * 255),
      (int) round(($b + $m) * 255),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTransformedShades(bool $dark = FALSE, bool $scale = FALSE, int $colorizeOffset = 100, bool $naturalSaturation = FALSE): array {
    $shades = $this->getShades();
    if ($dark) {
      $shades = $this->reverseShades($shades);
    }
    if ($scale) {
      $shades = $this->scaleShades($shades, $dark, $colorizeOffset, $naturalSaturation);
    }
    // Normalize to integer keys (getShades() uses string keys, the transform
    // maps use integers) so callers can address shades numerically.
    $normalized = [];
    foreach ($shades as $shadeId => $shade) {
      $normalized[(int) $shadeId] = $shade;
    }
    return $normalized;
  }

  /**
   * Compress the shades.
   *
   * @param \Drupal\neo_color\Shade[] $shades
   *   The shades to compress.
   *
   * @return \Drupal\neo_color\Shade[]
   *   The compressed shades.
   */
  protected function compressShades(array $shades): array {
    $scaled = [];
    $lightHex = $this->getContentLightHex();
    $darkHex = $this->getContentDarkHex();
    $shadeMap = [
      0 => 0,
      50 => 0,
      100 => 0,
      200 => 0,
      300 => 0,
      400 => 0,
      500 => 0,
      600 => [100, 200, 0.35],
      700 => 200,
      800 => [200, 300, 0.5],
      900 => [300, 400, 0.5],
      950 => 400,
    ];
    foreach ($shadeMap as $targetShade => $sourceShades) {
      if (is_array($sourceShades)) {
        [$color1, $color2, $factor] = $sourceShades;
        $hex = $this->interpolateHexColors($shades[$color1]->getHex(), $shades[$color2]->getHex(), $factor);
      }
      else {
        $hex = $shades[$sourceShades]->getHex();
      }
      // Choose the content (text) color by contrast against the resulting
      // background rather than inheriting the source shade's content.
      $content = Shade::pickContent($hex, $lightHex, $darkHex);
      $scaled[$targetShade] = new Shade((string) $targetShade, $hex, $content['hex'], $content['dark']);
    }
    return $scaled;
  }

  /**
   * Scale the base shades for a colorized scheme.
   *
   * Colorize turns the base ramp into a vivid brand-tinted surface that still
   * follows light/dark mode exactly like a non-colorized scheme: a light brand
   * tint in light mode, a dark brand shade in dark mode. The shades are
   * pre-reversed for dark schemes, so each source shade's lightness already
   * encodes the mode — we keep that lightness (compressed away from pure
   * white/black so every shade still reads as the brand) and paint it with the
   * brand 500's hue and saturation. Text is then picked by contrast per shade,
   * just like a normal scheme, so dark mode gets light ink and light mode gets
   * dark ink — consistent with the buttons and with non-colorized schemes.
   *
   * The offset controls where the SURFACE end (shade 0) of the ramp anchors:
   * at 0 it is the brand 500 itself; at 100 it is the full light/dark tint
   * (0.92 / 0.10 lightness); ABOVE 100 it keeps pushing toward a neutral
   * surface, reaching pure white (light) / pure black (dark) at 200. Only the
   * surface end moves — the far (contrast) end stays pinned so cards, text and
   * buttons keep room to work. The saturation floor relaxes toward the brand's
   * true saturation as the offset shrinks toward 0 (so 0 reproduces the exact
   * 500 color), and stops climbing at 100 so the >100 range only lightens the
   * surface rather than re-saturating it.
   *
   * @param \Drupal\neo_color\Shade[] $shades
   *   The (already mode-reversed) source shades.
   * @param bool $dark
   *   Whether the scheme is dark (determines which end is the surface).
   * @param int $colorizeOffset
   *   How far the surface is tinted away from the brand 500 shade. 0-100 spans
   *   exact-500 → full tint; 100-200 spans full tint → pure white/black.
   * @param bool $naturalSaturation
   *   TRUE to take each shade's hue and saturation from the pallet's own
   *   lightness→chroma curve instead of painting the whole ramp with the 500
   *   anchor and its saturation floor. See getChromaCurve().
   *
   * @return \Drupal\neo_color\Shade[]
   *   The scaled shades.
   */
  protected function scaleShades(array $shades, bool $dark = FALSE, int $colorizeOffset = 100, bool $naturalSaturation = FALSE): array {
    $scaled = [];
    $lightHex = $this->getContentLightHex();
    $darkHex = $this->getContentDarkHex();
    $anchor = $shades[500]->getHsl();
    $hue = (float) $anchor['h'];
    $factor = max(0, $colorizeOffset) / 100;
    $brandL = $anchor['l'] / 100;
    $brandSat = $anchor['s'] / 100;
    // Natural saturation resolves hue/saturation per shade from the pallet's
    // own ramp rather than from the 500 anchor below.
    $curve = $naturalSaturation ? $this->getChromaCurve() : NULL;
    // Keep the brand's saturation, but never so washed that the surface stops
    // reading as the brand color. The floor fades out as the offset approaches
    // 0 so the anchored surface matches the brand's true saturation; it stops
    // at 100 (satFactor capped) so offsets above 100 only lighten/darken.
    $satFactor = min(1.0, $factor);
    $sat = min(1.0, $brandSat + $satFactor * (max($brandSat, 0.45) - $brandSat));
    // The surface (shade 0) lightness moves from the brand 500 (offset 0) to
    // the full tint extreme (offset 100), then on to pure white/black (offset
    // 200); the opposite end stays pinned. The 0-100 leg is unchanged, so
    // existing schemes render identically.
    if ($dark) {
      $surfaceL = $factor <= 1.0
        ? min(0.92, $brandL + $factor * (0.10 - $brandL))
        : 0.10 + ($factor - 1.0) * (0.0 - 0.10);
      $surfaceL = max(0.0, $surfaceL);
    }
    else {
      $surfaceL = $factor <= 1.0
        ? max(0.10, $brandL + $factor * (0.92 - $brandL))
        : 0.92 + ($factor - 1.0) * (1.0 - 0.92);
      $surfaceL = min(1.0, $surfaceL);
    }
    foreach ($shades as $shadeId => $shade) {
      $sourceL = $shade->getHsl()['l'] / 100;
      if ($dark) {
        // Source shade 0 is black (L 0) and maps to the surface anchor; the
        // ramp ascends to the light contrast end at 0.92.
        $targetL = $surfaceL + $sourceL * (0.92 - $surfaceL);
      }
      else {
        // Source shade 0 is white (L 1) and maps to the surface anchor; the
        // ramp descends to the dark contrast end at 0.10.
        $targetL = 0.10 + $sourceL * ($surfaceL - 0.10);
      }
      if ((int) $shadeId === 0 && $factor <= 0) {
        // Anchored surface: use the exact 500 hex, avoiding the integer
        // rounding of an HSL round-trip.
        $hex = $shades[500]->getHex();
      }
      else {
        [$shadeHue, $shadeSat] = $curve ? $this->sampleChroma($curve, $targetL) : [$hue, $sat];
        [$r, $g, $b] = $this->hslToRgb($shadeHue, $shadeSat, $targetL);
        $hex = sprintf('#%02x%02x%02x', $r, $g, $b);
      }
      // Content follows the mode on the surface side of the ramp: dark
      // schemes read with light ink, light schemes with dark ink. At low
      // offsets the surface is a brand mid-tone whose luminance no longer
      // signals the mode, and pure contrast picking diverges per brand (dark
      // ink on a bright amber, light ink on a deep red — inconsistent
      // siblings). The far (contrast) side keeps pure per-shade picking: a
      // dark scheme's light far end still needs dark ink. The 2.0 guard
      // protects schemes whose brand color can't carry the mode ink at all.
      $farL = $dark ? 0.92 : 0.10;
      $surfaceSide = abs($targetL - $surfaceL) <= abs($targetL - $farL);
      $preferredHex = $dark ? $lightHex : $darkHex;
      if ($surfaceSide && Shade::contrastRatio($hex, $preferredHex) >= 2.0) {
        $content = ['hex' => $preferredHex, 'dark' => !$dark];
      }
      else {
        $content = Shade::pickContent($hex, $lightHex, $darkHex);
      }
      $scaled[(int) $shadeId] = new Shade((string) $shadeId, $hex, $content['hex'], $content['dark']);
    }
    return $scaled;
  }

  /**
   * Build the pallet's lightness → chroma curve.
   *
   * Colorize normally paints the whole ramp with shade 500's hue and
   * saturation. That works for a brand pallet, whose saturation is flat across
   * the ramp, but misreads a pallet designed as a neutral with a saturated
   * dark end: base's light shades sit near 10% saturation while its 500 sits
   * at 38%, so anchoring to 500 turns a near-gray surface into a visible tint.
   *
   * The curve records each configured shade as a (lightness, hue, saturation)
   * point, sorted by lightness, so a colorized shade can look up the chroma
   * the pallet's author actually chose at that lightness. Hue is carried along
   * too, but is effectively constant within a pallet — saturation is the
   * variable this exists to preserve.
   *
   * The synthetic white shade 0 is included on purpose: it is what lets the
   * >100 colorize offsets desaturate smoothly into a pure white surface.
   *
   * @return array[]
   *   Points with 'l' (0-1), 'h' (degrees) and 's' (0-1) keys, ascending by
   *   lightness.
   */
  protected function getChromaCurve(): array {
    $points = [];
    foreach ($this->getShades() as $shade) {
      $hsl = $shade->getHsl();
      $points[] = [
        'l' => $hsl['l'] / 100,
        'h' => (float) $hsl['h'],
        's' => $hsl['s'] / 100,
      ];
    }
    usort($points, fn(array $a, array $b) => $a['l'] <=> $b['l']);
    return $points;
  }

  /**
   * Sample hue and saturation from a chroma curve at a target lightness.
   *
   * Linearly interpolates between the two shades bracketing the lightness.
   * Beyond either end of the ramp the nearest endpoint is held.
   *
   * @param array[] $points
   *   The curve from getChromaCurve().
   * @param float $lightness
   *   The target lightness (0-1).
   *
   * @return array
   *   A [hue, saturation] pair, hue in degrees and saturation 0-1.
   */
  protected function sampleChroma(array $points, float $lightness): array {
    $first = reset($points);
    if ($lightness <= $first['l']) {
      return [$first['h'], $first['s']];
    }
    $last = end($points);
    if ($lightness >= $last['l']) {
      return [$last['h'], $last['s']];
    }
    $count = count($points);
    for ($i = 0; $i < $count - 1; $i++) {
      $a = $points[$i];
      $b = $points[$i + 1];
      if ($lightness < $a['l'] || $lightness > $b['l']) {
        continue;
      }
      $span = $b['l'] - $a['l'];
      $ratio = $span > 0 ? ($lightness - $a['l']) / $span : 0.0;
      // Walk the shortest arc so a ramp straddling 0° (secondary runs 358° →
      // 0°) interpolates across the seam instead of sweeping the whole wheel.
      $delta = fmod($b['h'] - $a['h'] + 540, 360) - 180;
      return [
        $a['h'] + $ratio * $delta,
        $a['s'] + $ratio * ($b['s'] - $a['s']),
      ];
    }
    return [$last['h'], $last['s']];
  }

  /**
   * Reverse the shades.
   *
   * @param \Drupal\neo_color\Shade[] $shades
   *   The shades to reverse.
   *
   * @return \Drupal\neo_color\Shade[]
   *   The reversed shades.
   */
  protected function reverseShades(array $shades): array {
    return [
      0 => new Shade('0', '#000000', '#ffffff', TRUE),
      50 => $shades[950],
      100 => $shades[900],
      200 => $shades[800],
      300 => $shades[700],
      400 => $shades[600],
      500 => $shades[500],
      600 => $shades[400],
      700 => $shades[300],
      800 => $shades[200],
      900 => $shades[100],
      950 => $shades[50],
    ];
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
