INTRODUCTION
------------

Provide color pallets and shades for use within the Neo themes.

REQUIREMENTS
------------

This module requires jacerider/neo_build and Drupal core.

INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit
https://www.drupal.org/node/1897420 for further information.

## Pallet Shades

Each pallet has the following shades:

50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950

These shades can be used like:

```html
<div class="bg-primary-500 text-primary-content-500" />
```

## The "0" base shade

The base pallet has an additional shade of "0". This will equate to absolute
white and should be used instead of using something like bg-white.

```html
<div class="bg-base-0" />
```

SCHEMES
-------

Color schemes allow you to override base/primary/secondary/accent pallet
with a different pallet.

## Dark Mode

If dark mode is enabled for a pallet, the pallet shades will be reversed.

## Colorize Mode

If colorize mode is enabled for a pallet, the base color pallet shades will be
compressed to emphaside the 500 shade.

## Tailwind Scheme Variants

Variants are provided for each scheme as well as 'dark' and 'colorized' schemes.

```html
<div class="scheme-primary-solid-dark">
  <div class="bg-primary-500 primary-solid-dark:bg-secondary-500">
    Will set background to secondary color when .scheme-primary-solid-dark is
    on a parent or self.
  </div>
  <div class="bg-primary-500 dark:bg-secondary-500">
    Will set the background to secondary color when any "dark" scheme
    class exists on a parent or self.
  </div>
  <div class="bg-primary-500 color:bg-secondary-500">
    Will set the background to secondary color when any "colorized" scheme
    class exists on a parent or self.
  </div>
</div>
```

## Shade that transends scheme override

The color without a shade can be used to force the default color regardless of
the scheme.

```html
<div class="scheme-primary-solid">
  <div class="bg-primary-500">Will inherit from the scheme.</div>
  <div class="bg-primary">Will ignore the scheme and pull from root.</div>
</div>
```

## MAINTAINERS

Current maintainers for Drupal 10:

- Cyle Carlson (jacerider) - https://www.drupal.org/u/jacerider
