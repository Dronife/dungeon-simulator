## SoloRPG - Project Context

# Claude Session Rules

## After Implementation
- When a feature is finished, **read the full CLAUDE.md** and update the business logic section accordingly — add new features, remove deleted ones, correct outdated descriptions
- **Ask the user** if the implementation is finished before doing this
- **Write a brief summary** of what the user was planning/brainstorming during the session (ideas mentioned but not implemented, "I'll do it later" items, future directions) so context isn't lost between sessions

## Business Logic Documentation
- Every new feature gets a brief business logic entry pointing to relevant code
- If a feature is removed, delete its documentation — no stale entries
- Keep it factual and current, not aspirational

### What It Is
Solo RPG engine where game state (inventory, quests, NPC secrets) is tracked by code, not by the LLM. LLM only narrates; the engine enforces truth.

### Game Flow
INIT: Generate world (+ optionally character) + images → preview → start game → save to DB → redirect to play screen
PLAYING: Player types action → DM responds with structured screenplay-style scenes → saved to DB → rendered in play screen

### World Generation
- **Hook system** — `SeedGenerator` rolls random constraints that force the LLM into unique outputs. Types: threat/rumor/faction/local_color
- **Seed/constraint logic** lives in `SeedGenerator.php`, prompt assembly in `WorldGenerator.php`

### Play Screen & Narration
- Narration screen where the story happens. Player types, DM responds with structured scenes — not plain text.
- Each DM response is a mix of scene headings, prose narration, NPC dialogue with stage directions, character actions with roll results, and inner thoughts. Rendered with distinct formatting per type so it reads like a movie script.
- The DM is aware of the generated world, character sheet, and full conversation history.
- History persists between sessions.
- Crimson Pro for narrative text, JetBrains Mono for dice rolls and game mechanics.

### TODO / Future Plans
- Implement async character creation (parallel generation)

### Memory System
- Four-tier memory: **tick** (single beats) → **recap** (compressed ticks) → **summary** (compressed recaps) → **epoch** (life chapters/backstory)
- Memories have `active` flag — only active memories are loaded into context by default
- `parent_id` on memories for tracking compression chains
- Memory model: `Memory` ↔ `CharacterMemory` (pivot) ↔ `Character` (BelongsToMany)

### NPC Intent System
- Each NPC gets their own LLM call to declare intent (what they *want* to do next)
- NPC context includes: full bio, identity, speech patterns, all memory tiers, relationship map to nearby characters
- NPC states intent in first person; if speaking, includes exact dialogue in quotes
- NPCs never narrate outcomes — they only declare what they attempt

### Character Relationships
- `CharacterRelationship` model with 8 float axes: trust, affection, respect, fear, loyalty, debt, rivalry, attraction
- `CharacterRelationshipMap` connects from_character → to_character (directional — each side has separate feelings)
- `$character->relationshipWith($target)` returns the relationship or null
- `$character->createRelationshipWith($target, $axes)` for creation

### DM / Narration
- DM is a narrator only — does not need NPC memories, secrets, or internal state
- DM receives: NPC bios, speech patterns, physical appearance, and their declared intents
- DM produces structured screenplay-style JSON (heading, narrator, dialogue, action, mechanic, etc.)
- Pre-rolled d20 dice passed to DM; DM uses them for checks (persuasion, deception, stealth, etc.)
- Tested with Gemini Flash Lite — ~$0.0001 per NPC call, ~0.7-1.3s response time

### LLM Context Structure
- **NPC context:** bio, identity, speech patterns, all memory tiers, relationship map to nearby characters
- **DM context:** NPC public info (bio, speech, appearance) + NPC intents + pre-rolled dice. No memories, no secrets.
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

## Character Builder (`Games/CharacterBuilder.jsx`)

### Layout
Uses the mobile game category-based single-screen pattern with 3 zones:
1. **Character Preview** (flex-1) — 6-layer composited character (back hair → base → facial → outfit → hair shadow → front hair), all with CSS filters for hair color
2. **Icon Strip** (shrink-0) — 6 category buttons in order: Bio (`fa-id-card`), Stats (`fa-dice-d20`), Sheet (`fa-scroll`), Hair (`fa-scissors`), Outfit (`fa-shirt`), Color (`fa-palette`). Active = `text-red-500`, inactive = `text-zinc-500`. Default tab is Bio.
3. **Options Panel** (shrink-0, max-h-[45vh], flex column) — scrollable content area on top, Finished button pinned at bottom outside the scroll area (always visible on every tab)

### Category Panels
- **BioPanel** — Identity fields: name + surname (full-width, stacked, each with wand button), age + gender side-by-side (age = number input, gender = pill buttons), race as 3-column grid. Unavailable options (female, non-human races) show "Soon" inline and are disabled
- **StatsPanel** — 3×2 grid of stat cards (STR, DEX, CON, INT, WIS, CHA). Each card is tapped individually to roll (number shuffles ~800ms via setInterval, lands on 5–19). Re-rolling any stat resets all 6 ("Reset All" button). HP auto-calculated from CON (`con * 2 + 10`) shown below grid after CON is rolled.
- **SheetPanel** — 11 trait fields (personality, traits, trauma, hobbies, routines, job, skills, goals, secrets, limits, intentions). Each has a text input + wand button (`fa-wand-magic-sparkles`) that calls `POST /api/character/generate-trait` to AI-generate that single field via Gemini
- **HairPanel** — 2x2 grid of hair combo thumbnails (front × back variants)
- **OutfitPanel** — 3-column grid of outfit thumbnails
- **ColorPanel** — row of color swatches (`w-11 h-11` for 44px touch targets)

### State & Persistence
- Appearance (hair, outfit, color) saved to `localStorage('dnd_character_appearance')`
- Traits saved to `localStorage('dnd_character_traits')`
- Stats saved to `localStorage('dnd_character_stats')` — object with `{ str, dex, con, int, wis, cha }` (null = unrolled)
- All are loaded back on mount so the builder preserves state across visits
- "Finished" button saves all three and redirects to `/game`

### Backend Trait Generation
- **Route:** `POST /api/character/generate-trait` → `GameController@generateTrait`
- Accepts `{ field, existing_traits }`, validates field name against allowed list (name, surname + 11 trait fields)
- Name/surname generation uses race and gender context for thematic names (one word only)
- Other traits: builds context from existing traits, calls `GeminiClient::generate()` with creative RPG prompt (1-2 sentences)
- Returns `{ field, value }` — single trait at a time

### Integration with Index.jsx
- The concept art character card in customize mode is an `<a href="/game/character-builder">` so users can tap to re-edit
- On game start, traits, stats, and appearance from localStorage are merged into character data. Works with or without AI-generated character.
- Start Game redirects to the play screen
- `handleDiscard` clears appearance, traits, and stats from localStorage
