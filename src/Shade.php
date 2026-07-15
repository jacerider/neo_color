<?php

declare(strict_types=1);

namespace Drupal\neo_color;

/**
 * A Shade object.
 */
class Shade {

  /**
   * The ID.
   *
   * @var string
   */
  private string $id;

  /**
   * The color.
   *
   * @var string
   */
  private string $color;

  /**
   * Is the content color dark.
   *
   * @var bool
   */
  private bool $dark;

  /**
   * The content.
   *
   * @var string
   */
  private string $content;

  /**
   * Constructs a new Shade object.
   */
  public function __construct(string $id, string $color, string $content, bool $dark) {
    $this->id = $id;
    $this->color = $color;
    $this->content = $content;
    $this->dark = $dark;
  }

  /**
   * Get the ID.
   *
   * @return string
   *   The ID.
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Is the content color dark.
   *
   * @return bool
   *   TRUE if the content color is dark.
   */
  public function isDark(): bool {
    return $this->dark;
  }

  /**
   * Get the color as HEX.
   *
   * @return string
   *   The value.
   */
  public function getHex() {
    return $this->color;
  }

  /**
   * Get the content color as HEX.
   *
   * @return string
   *   The value.
   */
  public function getContentHex() {
    return $this->content;
  }

  /**
   * Get the color as RGB.
   *
   * @return array
   *   The RGB values.
   */
  public function getRgb() {
    return $this->toRgb($this->getHex());
  }

  /**
   * Get the content color as RGB.
   *
   * @return array
   *   The RGB values.
   */
  public function getContentRgb() {
    return $this->toRgb($this->getContentHex());
  }

  /**
   * Get the color as HSL.
   *
   * @return array
   *   The HSL values.
   */
  public function getHsl() {
    return $this->toHsl($this->getRgb());
  }

  /**
   * Get the content color as HSL.
   *
   * @return array
   *   The HSL values.
   */
  public function getContentHsl() {
    return $this->toHsl($this->getContentRgb());
  }

  /**
   * Convert to RGB.
   *
   * @param string $hex
   *   The hex value.
   *
   * @return array
   *   The RGB values.
   */
  protected function toRgb(string $hex) {
    [$r, $g, $b] = sscanf($hex, "#%02x%02x%02x");
    return [$r, $g, $b];
  }

  /**
   * {@inheritdoc}
   */
  protected function toHsl($rgb) {
    $r = $rgb[0] / 255;
    $g = $rgb[1] / 255;
    $b = $rgb[2] / 255;
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $h = 0;
    $s = 0;
    $l = ($max + $min) / 2;
    $d = $max - $min;
    if ($d == 0) {
      $h = $s = 0;
    }
    else {
      $s = $d / (1 - abs(2 * $l - 1));
      switch ($max) {
        case $r:
          $h = 60 * fmod((($g - $b) / $d), 6);
          if ($b > $g) {
            $h += 360;
          }
          break;

        case $g:
          $h = 60 * (($b - $r) / $d + 2);
          break;

        case $b:
          $h = 60 * (($r - $g) / $d + 4);
          break;
      }
    }
    return ['h' => round($h), 's' => round($s * 100), 'l' => round($l * 100)];
  }

  /**
   * Get the WCAG relative luminance of a hex color.
   *
   * @param string $hex
   *   The hex color (e.g. "#7a7849").
   *
   * @return float
   *   The relative luminance (0.0 - 1.0).
   */
  public static function relativeLuminance(string $hex): float {
    [$r, $g, $b] = sscanf($hex, "#%02x%02x%02x");
    $linear = static function (int $c): float {
      $c = $c / 255;
      return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
    };
    return 0.2126 * $linear($r) + 0.7152 * $linear($g) + 0.0722 * $linear($b);
  }

  /**
   * Get the WCAG contrast ratio between two hex colors.
   *
   * @param string $hexA
   *   The first hex color.
   * @param string $hexB
   *   The second hex color.
   *
   * @return float
   *   The contrast ratio (1.0 - 21.0).
   */
  public static function contrastRatio(string $hexA, string $hexB): float {
    $a = self::relativeLuminance($hexA);
    $b = self::relativeLuminance($hexB);
    return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
  }

  /**
   * Pick the readable content color for a background by WCAG contrast.
   *
   * Returns whichever of the light/dark candidates has the higher contrast
   * ratio against the background, so generated (interpolated) surfaces always
   * get legible text rather than inheriting a source shade's content color.
   *
   * @param string $bgHex
   *   The background hex color.
   * @param string $lightHex
   *   The light content candidate (used on dark backgrounds).
   * @param string $darkHex
   *   The dark content candidate (used on light backgrounds).
   *
   * @return array
   *   An array with 'hex' (the chosen content hex) and 'dark' (TRUE when the
   *   dark candidate was chosen, mirroring Shade::$dark).
   */
  public static function pickContent(string $bgHex, string $lightHex, string $darkHex): array {
    $bgLum = self::relativeLuminance($bgHex);
    $ratio = static function (float $a, float $b): float {
      return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    };
    $lightRatio = $ratio($bgLum, self::relativeLuminance($lightHex));
    $darkRatio = $ratio($bgLum, self::relativeLuminance($darkHex));
    // Bias toward light text, scaled by the fill's chroma (colorfulness).
    // WCAG relative luminance under-weights saturated warm hues: a vivid
    // brand red like #ee3124 scores as "dark" (luminance ~0.205, just past
    // the ~0.179 black/white crossover), so raw contrast slightly prefers
    // dark ink even though light ink is conventional and perceptually
    // correct on it. The more saturated the fill, the stronger the lean to
    // light; a near-neutral surface keeps the ~10% baseline and follows raw
    // contrast, so genuinely light surfaces still get dark ink.
    [$r, $g, $b] = sscanf($bgHex, "#%02x%02x%02x");
    $chroma = (max($r, $g, $b) - min($r, $g, $b)) / 255;
    $bias = 1.1 + 0.45 * $chroma;
    return $darkRatio > $lightRatio * $bias
      ? ['hex' => $darkHex, 'dark' => TRUE]
      : ['hex' => $lightHex, 'dark' => FALSE];
  }

}
