---
name: neo-color-dev
description: Understand and modify the neo_color MODULE internals (PHP) — the neo_pallet/neo_scheme config entities, the per-scheme CSS-variable token emission, the contrast-pick engine (buttons/links/bare color tokens), the colorize/offset ramp math, and the two NeoBuild event subscribers. Use when editing files under web/modules/contrib/neo_color/src, adding or tuning a scheme token / scheme config key / contrast pick, or debugging why a color token (text-primary, bg-default, --link-color…) resolves the way it does inside a scheme. NOT for authoring components or using color utilities in twig (use neo-component), and NOT for the Vite/Tailwind asset pipeline (use neo-build).
allowed-tools: Read, Write, Edit, Glob, Grep, Bash
---

# Developing the neo_color module

This skill is for working on **neo_color's own code**. If you're building a
component or just *using* color utilities in twig, stop — that's **neo-component**.
If you're debugging the Vite/Tailwind build itself, that's **neo-build**.

## Mental model

Two config entities feed one CSS-variable system:

- **`neo_pallet`** — an 11-step brand ramp (shades `50 100 200 300 400 500 600
  700 800 900 950`). Each shade carries a color *and* a `content` color (the ink
  legible on it). Pallets are scheme-agnostic.
- **`neo_scheme`** — maps the four **roles** (`base`, `primary`, `secondary`,
  `accent` — `SchemeInterface::PALLETS`) each to a pallet id, plus flags `dark`
  (bool), `colorize` (bool), `colorize_offset` (int 0–200, default 100). Its
  selector is `scheme-{id-with-dashes}` (`getSelector()`).

A scheme emits a block of CSS custom properties scoped to `.scheme-{id}`. Wrapping
markup in that class remaps every `--color-*` token, so plain Tailwind color
utilities (`bg-base`, `text-primary`, `hover:text-secondary`) recolor to the
scheme. `base` is the **surface** family; `primary/secondary/accent` are accents.

`dark` and `colorize` change how the ramp is transformed before emission:
- **dark** → ramp is *pre-reversed* (shade 50 becomes the darkest), so "walk 500 →
  950" always means "increasing contrast away from the surface tone."
- **colorize** → only the **base** ramp is re-tinted (`Pallet::scaleShades`) to the
  brand hue, following the mode (light tint in light mode, dark shade in dark).
  `colorize_offset`: 0 = surface is the *exact* brand 500; 100 = full tint; 100–200
  = tint → pure white (light) / black (dark). Role slots (primary/secondary/accent)
  use their raw dark-transformed pallet ramp — they are **not** colorized.

## Where things live (`src/`)

- `Entity/Pallet.php` — the ramp. `getShades()`, `getTransformedShades($dark,
  $scale, $colorizeOffset)` (the post-reverse/scale ramp everything picks from),
  `scaleShades()` (colorized base tint), `getCssData()` (emits the raw `--color-*`
  vars), shadow anchoring (`getShadowAnchorHsl`/`getShadowRgb`),
  `getContentLightHex/DarkHex`.
- `Entity/Scheme.php` — the token assembler. **`getCssData()`** is the entry point:
  loops the four role pallets → `Pallet::getCssData()` + `getTransformedShades()`,
  then merges **`buildButtonCssVars()`** (contrast-aware btn/link/bare-color tokens).
  The pick engine (`pickButtonShades`, `pickLineColor`) lives here too.
- `Shade.php` — one shade. `getHex/getContentHex/getRgb/getContentRgb/getHsl`, and
  the WCAG statics `relativeLuminance()`, `contrastRatio()`, `pickContent()`.
- `EventSubscriber/NeoBuildEventSubscriber.php` (`onBuild`) — registers the Tailwind
  **theme colors** (via `addTailwindTheme` — a JS config, **not** `@theme` CSS; only
  white/black/current/inherit/transparent land in `@theme`), the gray/slate/zinc/
  neutral/stone → base aliases, the `scheme`/`dark`/`color` **variants**, and the
  `--tw-prose-*` overrides. Feeds the **build**.
- `EventSubscriber/NeoBuildInlineEventSubscriber.php` (`onInlineBuild`) — injects the
  per-scheme `.scheme-{id}{…}` variable blocks at **runtime**, and re-pins the
  semantic defaults (`--background-color-default`, `--text-color-default`,
  `--color-border-default`) per scheme scope *and* in `.scheme--reset`.
- `Form/{SchemeForm,PalletForm}.php`, `Element/{Scheme,Color}.php` — admin UI (scheme
  editor + preview, pallet editor, the scheme radio element). `Drush/Commands/
  NeoColorCommands.php` — `neo:color:schemes` (alias `neoc-schemes`) lists schemes.

## Token cheat-sheet (read before touching color)

Naming rules that trip everyone up:

| Pattern | Means | Example |
|---|---|---|
| `--color-{role}-{shade}` | exact ramp shade | `--color-primary-500` (raw brand) |
| `--color-{role}` (bare) | **shade 500** by convention | `bg-primary`, `text-accent` |
| `--color-…-content` | **ink legible *on that color as a background*** | `--color-base-500-content` = ink for a `bg-base-500` box |

The surface vs. the `base` family — the #1 confusion:

| You want… | Use | Token | Notes |
|---|---|---|---|
| the scheme **surface** fill | `bg-default` | `--background-color-default` = `--color-base-0` | base-**0**, not base-500 |
| **readable text on the surface** | `text-default` | `--text-color-default` = `--color-base-0-content` | auto-applied to unstyled text in a scheme |
| a mid surface step | `bg-base` | `--color-base` = base-**500** | a distinct tone, *not* the surface |

So `text-base-content` = `base-500-content` (ink for base-**500**), which is *not*
the surface ink and reads only by coincidence on dark surfaces — for "readable text
on the surface" use `text-default`. `text-base-0-content` renders identically to
`text-default` today; prefer `text-default` (it's the semantic token the scheme
auto-applies and the intended override point).

Contrast-aware tokens emitted by `buildButtonCssVars()` (all pick *away* from the
surface so they never collide with it):

| Token(s) | Purpose | Target |
|---|---|---|
| `--color-{primary,secondary,accent}` (+ `-content`) | make bare `text-/bg-/border-{role}` legible in every scheme | 4.5:1 |
| `--btn[-slot]-bg-color` / `-content-color` (+ `-hover`) | solid button fills + their ink | 4.0:1 |
| `--btn-line-color` | outline/text-button ink (text-grade) | 4.5:1 |
| `--link-color` / `--link-color-hover` | link ink + its hover step | 4.5:1 |
| `--color-shadow-{shade}` | brand-tinted, always-darker-than-surface shadow ramp | — |

Key rule: **`base` is excluded** from the bare-token contrast pick (bg-base is a
surface step, not a contrast element; `text-base` is a font size, not a color).
Numbered shades (`--color-primary-500`) always stay the raw brand — only the bare
token and its `-content` move. Where a role's 500 already clears the target, the
pick returns 500 and nothing changes.

## The contrast-pick engine

`pickButtonShades($shades, $surfaces, $tonal = FALSE, $contrastTarget = 4.0)`
returns `[$pick, $hover]`:
- Walks the *preferred side* from 500 away from the surface tone (dark-reversed
  ramps make this "toward higher shade ids"). Accepts the first shade ≥ target.
- Fallbacks in order: best shade that is ≥ 2.0 **against base-0 only** (the guard is
  base-0, *not* min(base-0, base-100) — at low colorize offsets base-100 sits
  between surface and far end and would crush the mode-correct side); then the other
  side; then the ramp argmax.
- Hover = walk onward until ≥ 1.2:1 delta from the pick; flip direction at ramp ends.

`pickLineColor(...)` is the 4.5:1 text-grade pick for outline/text buttons.
`$surfaces` is always `[base-0, base-100]`. All ratios go through
`Shade::contrastRatio()`; ink choices through `Shade::pickContent()`.

## Where to add X

- **A new per-scheme token** → emit it in `Scheme::buildButtonCssVars()` (has
  `$surfaces` + `$slotShades` + the pick helpers) or, if it's a raw ramp value, in
  `Pallet::getCssData()`. To expose it as a Tailwind utility, register the color in
  `NeoBuildEventSubscriber::onBuild()`.
- **A semantic default that must follow the scheme** (like `--text-color-default`)
  → add it to the per-scheme *and* `.scheme--reset` loops in
  `NeoBuildInlineEventSubscriber`.
- **A new scheme config key** (à la `colorize_offset`) → schema in
  `config/schema/`, property + accessor on the entity, thread it through
  `Scheme::getCssData()` → `Pallet::getCssData()`/`getTransformedShades()` (**both**,
  or buttons desync from surfaces), and add a widget in `SchemeForm` (range inputs
  need an explicit `'#ajax' => ['event' => 'change']`).

## Introspect at runtime instead of reading the ramp math

The fastest way to reason about a scheme is to dump what it emits:

```php
// _audit.php — place it inside the project root so drush can read it (a
// containerized/remote drush won't see host-only temp dirs).
$s = \Drupal::entityTypeManager()->getStorage('neo_scheme')->load('secondary_solid');
$d = $s->getCssData();                    // every emitted --color-* / --btn-* / --link-*
// contrast helper (or use \Drupal\neo_color\Shade::contrastRatio on hexes):
// ratio of --color-primary vs the surface --color-base-0, etc.
```
`drush php:script ./_audit.php` then `rm` it. This is how you verify a pick change
across *all* schemes (loop `loadByProperties(['status'=>1])`) before rebuilding.

## Dev workflow gotchas

- **Rebuild rules:**
  - Changed `getCssData()` values (inline vars) → `drush cr` — the inline subscriber
    re-injects; no build needed.
  - Changed `NeoBuildEventSubscriber` (theme colors / variants / prose) or utility
    CSS → `drush neo:build front` **and** `drush neo:build back`. Admin pages
    (scheme editor/preview) render in the **back** theme — forgetting `back` leaves
    stale color there.
- **Colors aren't in `@theme`.** `text-primary`/`bg-primary` come from the JS color
  config (`addTailwindTheme`), so grepping the built CSS `@theme` for `--color-base`
  finds nothing — and utilities are emitted **on demand** (only if the class appears
  literally in scanned source). A JS-injected test class won't generate; verify by
  reading the CSS *variables* instead.
- This skill's source is `neo_color/install/skills/neo-color-dev/SKILL.md`; the
  active copy is `.claude/skills/neo-color-dev/SKILL.md` (aggregated by
  `drush neo:build:install`). Edit the source, keep the two identical.
