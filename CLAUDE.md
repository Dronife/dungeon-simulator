## SoloRPG - Project Context

### What It Is
Solo RPG engine where game state (inventory, quests, NPC secrets) is tracked by code, not by the LLM. LLM only narrates; the engine enforces truth.

### Game Flow
INIT: Generate world + characters + images → preview → start game → save to DB
PLAYING: Player input → pre-rolled dice → LLM Manager decides stakes → NPCs act → DM narrates via JSON → DB updated

### LLM Context Structure
- **Character context:** personality, traits, goals, secrets, items, skills + tiered memory (Summary → Recap → Ticks)
- **DM context:** global rules + own tiered memory
- **Two-tier secrets:** `public_secrets` (discoverable) vs `secrets` (internal motives)
- Memory compresses periodically (oldest ticks → recap → summary)

# Project Rules

## Tech Stack
- Laravel + Inertia.js + React
- Tailwind CSS for styling
- Font Awesome icons (fa-solid)

## Design Rules
- Mobile-first design
- Pages must NEVER scroll - all content must fit within the viewport height
- Scrolling is only allowed inside drawers, modals, or explicitly scrollable containers
- Layout is `h-screen overflow-hidden` with a flex column structure
- Page containers use the remaining viewport height after the header (currently 60px)
- Use `overflow-hidden` on page-level containers to prevent any scroll leaking

## Current Visual Style (temporary - functional/minimal)
- Dark theme: `bg-zinc-950` base, `bg-zinc-900` cards/surfaces, `bg-zinc-800` secondary elements
- Accent color: `red-500`/`red-600` for primary actions, highlights, and character names
- Text hierarchy: `text-white` primary, `text-zinc-300` secondary, `text-zinc-400` tertiary, `text-zinc-600`/`text-zinc-700` muted/icons
- Borders: `border-zinc-800` default, `border-red-500/50` hover accent
- Rounded corners: `rounded-xl` for cards/buttons, `rounded-lg` for smaller elements
- Gradient overlays on image backgrounds (e.g. `bg-gradient-to-r from-[#3d0d09]/100 to-black/40`)
- Uppercase `text-xs tracking-wide` labels for section headers
- **Future direction:** Travian-inspired medieval look. Current style is placeholder.

## Component Patterns
- Image grid components: `MatrixCell` (4x4 character matrix) and `Grid2x2Cell` (2x2 lore grid) use CSS background-position to show specific cells
- `Drawer` component slides from left or right with backdrop blur
- Square cards use height-driven sizing (`flex-1 min-h-0 aspect-square max-w-full`) to adapt across screen sizes
- `ConceptArtPreview` renders composed character art + hair cutout overlay with CSS color filter

## UX Principles (Mobile-First Game UI)

### Core Layout Pattern (from Genshin, Ready Player Me, gacha games)
- **Single-screen with category icon strip**: preview fixed top (~50-55% viewport), horizontal icon strip below (~44-48px), swappable options area at bottom. This is the dominant pattern in mobile game character creators.
- **Two-level hierarchy**: major categories in icon strip → sub-categories as horizontal pill row when a category is selected. Keeps everything flat, no deep navigation.
- **Swipeable panels**: options area can swipe left/right between categories as complement to icon strip (tap to jump, swipe to browse adjacent).

### Navigation Rules
- **No page-level scroll.** Scrolling only inside contained option panels.
- **Modals for confirmations/small inputs only.**
- **Redirects for full-screen experiences.**
- **Min 44x44px touch targets.** Icon strip: 3-5 visible, horizontal scroll if more.
- **Bottom sheet pattern**: half-height panel slides up for options, draggable up/down. Good for complex option panels while keeping preview visible.

### Builder/Customizer Patterns
- **Live preview is non-negotiable.** Character updates instantly on every tap. No separate "preview" step.
- **Visual thumbnails over text lists.** Grid of small previews (4-5 per row), tap to select.
- **Color separate from shape.** Pick hairstyle first, then color from swatch row — avoids multiplying grid by N colors.
- **Randomize/quick-start button.** Reduces decision paralysis, lets players tweak from a random starting point instead of building from scratch.
- **Undo/reset per category.** Small reset icon per section gives confidence to experiment.
- **Progressive disclosure.** Show default/popular options first, expandable "more" for the rest.

### Project-Specific Decisions
- **Each trait/field can be manual or AI-generated** — user opts in per field, not all-or-nothing.
- **Wizard (step-by-step) is for one-time onboarding only.** For freely editable builders, use the single-screen category pattern instead.
