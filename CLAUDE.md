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
