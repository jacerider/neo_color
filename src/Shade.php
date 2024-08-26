<?php

declare(strict_types = 1);

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
    return [round($h), round($s * 100), round($l * 100)];
  }

}
