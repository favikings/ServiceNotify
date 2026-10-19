# ServiceNotify UI Design System

## 1. Design direction

The approved visual direction is **Evergreen Operations**: a calm, dark-first operations interface inspired by premium native mobile apps. It should feel focused, dependable, and quick to scan rather than like a generic administration dashboard.

The defining visual signature is the **expiry rail**: service cards use a slim, status-colored vertical rail together with an explicit countdown such as “12 days left.” Color reinforces urgency but never communicates status by itself.

Core principles:

- Mobile is the primary design target; desktop is a deliberate adaptation, not a stretched phone layout.
- Use deep evergreen surfaces instead of pure black.
- Use elevation through tonal contrast, restrained borders, and minimal shadow.
- Reserve bright green for primary actions, active states, and healthy outcomes.
- Make expiry dates, countdowns, and urgent actions the strongest information on each screen.
- Keep controls comfortable for touch and layouts efficient with a mouse and keyboard.
- Prefer restrained, functional motion over decorative animation.
- Avoid generic purple gradients, glassmorphism, excessive shadows, and dense admin-dashboard chrome.

The MVP ships with the dark theme defined here. A light theme is not required until it is designed and approved as a complete semantic theme.

## 2. Color system

Use semantic token names in templates and styles. Do not scatter raw hex values throughout components.

### 2.1 Core surfaces

| Token | Hex | Purpose |
| --- | --- | --- |
| `canvas` | `#0D1410` | App background and PWA theme/background color |
| `surface` | `#151E18` | Navigation and standard cards |
| `surface-raised` | `#1C2820` | Forms, modals, drawers, and selected cards |
| `surface-hover` | `#243229` | Hovered or pressed interactive surface |
| `border` | `#304037` | Visible control borders and strong dividers |
| `border-soft` | `#233129` | Quiet card separation and internal dividers |

### 2.2 Brand and text

| Token | Hex | Purpose |
| --- | --- | --- |
| `primary` | `#72E58C` | Primary actions and active navigation |
| `primary-hover` | `#8BEDA0` | Pointer hover state |
| `primary-pressed` | `#51C970` | Active/pressed state |
| `on-primary` | `#07110A` | Text and icons on primary backgrounds |
| `text-primary` | `#F4F7F4` | Headings and primary information |
| `text-secondary` | `#AAB6AE` | Supporting text |
| `text-muted` | `#76837B` | Metadata, labels, and placeholders |
| `text-disabled` | `#566159` | Disabled content only |

### 2.3 Status colors

| Token | Hex | Meaning |
| --- | --- | --- |
| `status-healthy` | `#72E58C` | More than 30 days remaining |
| `status-upcoming` | `#67B7FF` | General upcoming or informational state |
| `status-due-soon` | `#F4B860` | 8–30 days remaining |
| `status-urgent` | `#FF8A65` | 1–7 days remaining |
| `status-overdue` | `#FF626D` | Expiry date has passed |
| `status-info` | `#A78BFA` | Neutral system information not tied to expiry health |

Expiry-day behavior must be decided with the notification rules. Until then, do not silently classify “expires today” as either urgent or overdue.

### 2.4 CSS token baseline

```css
:root {
  color-scheme: dark;

  --color-canvas: #0d1410;
  --color-surface: #151e18;
  --color-surface-raised: #1c2820;
  --color-surface-hover: #243229;
  --color-border: #304037;
  --color-border-soft: #233129;

  --color-primary: #72e58c;
  --color-primary-hover: #8beda0;
  --color-primary-pressed: #51c970;
  --color-on-primary: #07110a;

  --color-text-primary: #f4f7f4;
  --color-text-secondary: #aab6ae;
  --color-text-muted: #76837b;
  --color-text-disabled: #566159;

  --color-status-healthy: #72e58c;
  --color-status-upcoming: #67b7ff;
  --color-status-due-soon: #f4b860;
  --color-status-urgent: #ff8a65;
  --color-status-overdue: #ff626d;
  --color-status-info: #a78bfa;
}
```

Validate contrast in the rendered component context. Do not place green primary text on pale/green backgrounds or muted text on hover surfaces without checking contrast. Status components require an icon or written label in addition to color.

## 3. Typography

### 3.1 Families

- Primary interface family: **Onest**.
- Data/accent family: **IBM Plex Mono**.
- Sans fallback: `system-ui, sans-serif`.
- Monospace fallback: `ui-monospace, monospace`.

Self-host optimized WOFF2 files when font assets are added so the installed PWA does not depend on a live font CDN. Include fonts in the service-worker asset strategy. Do not synthesize extra weights that have not been loaded.

Use Onest for navigation, headings, labels, controls, and prose. Use IBM Plex Mono sparingly for expiry dates, costs, identifiers, and notification timestamps. Apply tabular numerals to numeric data even when it uses Onest.

### 3.2 Type scale

Values are `font-size / line-height`.

| Role | Mobile | Desktop | Weight | Usage |
| --- | --- | --- | --- | --- |
| Display | `32px / 38px` | `40px / 46px` | 500 | A single important total or countdown |
| Page title | `24px / 30px` | `30px / 36px` | 600 | One per page |
| Section title | `20px / 26px` | `22px / 28px` | 600 | Major content groups |
| Card title | `16px / 22px` | `17px / 24px` | 600 | Service/client card title |
| Body | `15px / 22px` | `16px / 24px` | 400 | Default readable content |
| Label | `13px / 18px` | `13px / 18px` | 500 | Fields, filters, metadata |
| Caption | `12px / 16px` | `12px / 16px` | 400 | Secondary timestamps and help |
| Button | `14px / 20px` | `14px / 20px` | 600 | Buttons and compact navigation |

Do not render essential information below 12px. Avoid long all-caps text. Use sentence case for buttons, tabs, labels, and headings.

## 4. Spacing and layout tokens

The spacing system uses a 4px foundation.

| Token | Value | Typical use |
| --- | --- | --- |
| `space-1` | `4px` | Optical/icon adjustment |
| `space-2` | `8px` | Tight internal gap |
| `space-3` | `12px` | Label-to-control and compact gaps |
| `space-4` | `16px` | Mobile card padding and page gutter |
| `space-5` | `20px` | Form/card gap |
| `space-6` | `24px` | Tablet gutter and grouped content |
| `space-8` | `32px` | Desktop gutter and section gap |
| `space-10` | `40px` | Major separation |
| `space-12` | `48px` | Large desktop section gap |
| `space-16` | `64px` | Page-level rhythm |
| `space-20` | `80px` | Exceptional wide-screen spacing |

Page gutters:

- Mobile: 16px.
- Tablet: 24px.
- Desktop: 32px.

Component density:

- Standard mobile card padding: 16px.
- Standard desktop card padding: 20px or 24px depending on information density.
- Default field-to-field gap: 16px.
- Default section-to-section gap: 32px mobile and 40–48px desktop.

## 5. Shape, borders, and elevation

| Token | Value | Usage |
| --- | --- | --- |
| `radius-sm` | `10px` | Badges, small controls |
| `radius-md` | `14px` | Inputs and buttons |
| `radius-lg` | `18px` | Standard cards |
| `radius-xl` | `24px` | Sheets, dialogs, and major panels |
| `radius-pill` | `999px` | Chips, filters, status pills |

- Use 1px borders with `border-soft` for passive separation and `border` for controls/focus-adjacent boundaries.
- Do not outline every container. Prefer spacing and surface tone first.
- Shadows should be broad, dark, and subtle. Avoid bright drop shadows or layered “floating card” effects.
- A quiet evergreen ambient glow may appear behind the dashboard summary only; it must not reduce text contrast or become a generic full-page gradient.

## 6. Responsive application shell

### 6.1 Breakpoints

| Range | Width | Primary behavior |
| --- | --- | --- |
| Mobile | `< 640px` | Bottom navigation, single column, touch-first |
| Tablet | `640–1023px` | Wider cards and selective two-column layouts |
| Desktop | `>= 1024px` | Persistent sidebar and multi-column workspace |
| Wide desktop | `>= 1440px` | Expanded grids inside a controlled content width |

Breakpoints describe behavior, not device names. Components must also tolerate intermediate widths and browser zoom.

### 6.2 Mobile shell

- Treat mobile as the primary product experience.
- Use a sticky 60px top bar plus `env(safe-area-inset-top)` where supported.
- Use a fixed 72px bottom navigation plus `env(safe-area-inset-bottom)`.
- Bottom destinations: Dashboard, Clients, Services, and Reports.
- Place a visually dominant add action centrally in or directly above the bottom navigation. It opens a clear choice between Add client and Add service.
- Keep primary screen content in a single column.
- Use full-screen routes for complex forms and bottom sheets for short choices, filters, and confirmations.
- Never force desktop tables into horizontal scrolling for primary workflows. Convert rows to cards or structured lists.
- Preserve visible menus/buttons for all important actions; swipe gestures may enhance but never replace them.

### 6.3 Desktop shell

- At 1024px and above, replace bottom navigation with a persistent 248–272px sidebar.
- Use a top command bar for search, notification access, and account controls.
- Keep the main content fluid with a maximum width near 1440px.
- Dashboard summary metrics may use up to four columns when space permits.
- Forms and detail views may use two columns, but keep field reading order logical in the DOM.
- Use structured rows/tables for dense lists where they improve comparison.
- Quick edits may open in a right-side panel; complex edits remain dedicated pages.
- Desktop must preserve the mobile hierarchy, status language, and component identity.

## 7. Core component rules

### 7.1 Touch and control sizes

- Minimum interactive target: 48px by 48px on mobile.
- Standard mobile input/button height: 52px.
- Compact desktop input/button height: 44px minimum.
- Icon-only controls require an accessible name and visible focus state.
- Do not place destructive actions immediately beside primary actions without separation.

### 7.2 Buttons

- Primary: green background, `on-primary` content, one dominant primary action per section.
- Secondary: raised surface with a visible border and primary text.
- Tertiary: transparent surface, secondary text, and a clear hover/pressed state.
- Destructive: use overdue red intentionally and require confirmation for destructive record deletion.
- Loading state preserves button width, disables repeat submission, and communicates progress in text or assistive output.

### 7.3 Inputs and forms

- Labels remain visible above fields; placeholders are examples, not labels.
- Focus states use a clear two-part treatment: border change plus an outer focus ring.
- Errors appear below the relevant field and in a form-level summary when submission fails.
- Use native input semantics where possible, including email, tel, number, and date.
- Keep Save/Cancel actions reachable above the mobile bottom-navigation safe zone.

### 7.4 Service cards and expiry rail

A service card prioritizes:

1. Service/client name.
2. Explicit expiry state such as “12 days left” or “Overdue by 3 days.”
3. Exact expiry date.
4. Service type and provider.
5. Reminder/channel state.

The 3–4px left expiry rail uses the matching semantic status color. The written countdown remains required. Do not overload cards with every database field; secondary details belong on the service detail screen.

### 7.5 Status badges

- Pair a short written label with an icon or dot.
- Never use `primary` green to mean both a selectable action and an ambiguous status in the same local context.
- Keep overdue and destructive meanings distinct through wording and iconography, even if both use the red family.

### 7.6 Navigation and icons

- Active navigation uses primary color plus a shape/fill or label-weight change, not color alone.
- Use a single coherent rounded-line SVG icon family when an icon source is selected.
- Default icon stroke should feel optically consistent around 1.75–2px.
- Do not mix filled, outlined, emoji, and platform-specific icons in the same navigation system.
- The exact icon library remains an implementation choice requiring approval if it introduces a dependency.

## 8. Motion and feedback

| Interaction | Duration |
| --- | --- |
| Button, hover, and press | `140–180ms` |
| Card/filter state change | `180–220ms` |
| Bottom sheet or side panel | `240–300ms` |

- Use a soft ease-out curve for entrances and a slightly faster exit.
- Provide immediate pressed feedback on touch devices.
- Use skeletons only when content genuinely takes time to load; avoid fake delays.
- Do not continuously pulse, flash, or animate urgency colors.
- Honor `prefers-reduced-motion: reduce` by removing nonessential transforms and transitions.

## 9. Accessibility and usability requirements

- Target WCAG 2.2 AA contrast and interaction behavior.
- Support keyboard operation and a visible `:focus-visible` state for every interactive element.
- Maintain logical heading structure and DOM reading order across breakpoints.
- Do not rely on hover, gesture, position, or color as the only way to discover meaning or actions.
- Error, success, and loading announcements must be available to assistive technologies.
- Respect text resizing and browser zoom; do not lock the viewport scale.
- Keep important text at 12px or larger and default body content at 15px mobile/16px desktop.
- Test touch targets, safe-area insets, long names, large costs, and localized date strings.

## 10. Tailwind implementation guidance

Map these semantic tokens into the project's Tailwind configuration or CSS theme layer when the frontend foundation is created. Components should use names such as `bg-canvas`, `bg-surface`, `text-primary`, and `text-status-overdue`, rather than arbitrary color values.

Create reusable primitives for:

- App shell and navigation.
- Button variants.
- Form controls and validation messages.
- Surface/card containers.
- Status badges.
- Service cards with expiry rails.
- Empty, loading, and error states.
- Modal, bottom sheet, and desktop drawer patterns.

Do not create abstractions before at least two real component uses demonstrate a shared pattern. Plain PHP templates, Tailwind CSS, and vanilla JavaScript remain the approved frontend stack.

## 11. Screen-specific application

### Dashboard

- Lead with the nearest expiry or upcoming-expiry count, not decorative analytics.
- Group urgent/overdue services before healthy future services.
- Use compact summary cards and a chronological service list.
- Charts are optional and must answer a real tracking question; they are not required for MVP.

### Clients

- Show client name, organization, contact summary, and service count.
- Use card/list presentation on mobile and denser structured rows on desktop.

### Services

- Use the expiry rail and countdown as the central visual system.
- Keep filters accessible through a mobile sheet and inline desktop toolbar.

### Forms

- Divide long service forms into understandable sections: service details, billing, and reminders.
- Avoid multi-step forms unless testing shows the single-page form is too difficult.

### Reports

- Treat filters as the primary setup action and file download as the clear result.
- Communicate active filters in the export confirmation and generated report context.

## 12. Design QA checklist

Before considering a screen complete, verify:

- It works at 320px width without clipped primary content.
- It works at mobile, tablet, desktop, and 200% browser zoom.
- Interactive targets meet minimum size and have keyboard focus states.
- Status remains understandable without color.
- Loading, empty, error, success, and long-content states exist.
- Mobile bottom navigation does not obscure content or form actions.
- Desktop uses available space without producing excessively long text lines.
- Typography, spacing, radii, and colors use documented tokens.
- Motion respects reduced-motion preferences.
- The screen feels part of ServiceNotify through the expiry rail and deadline-first hierarchy.

