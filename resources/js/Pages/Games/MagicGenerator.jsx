import { useState } from 'react';
import {
    // Fire
    GiUnstableProjectile, GiBeamWake, GiFireSpellCast, GiBurningPassion, GiIfrit,
    GiFireShield, GiFireRing, GiPyromaniac, GiFlintSpark,
    // Ice
    GiIceSpear, GiIceSpellCast, GiIciclesAura, GiIceGolem, GiIceShield,
    GiIciclesFence, GiSpikyExplosion,
    // Light
    GiHypersonicBolt, GiLaserburn, GiGlowingHands, GiAura, GiShinyEntrance,
    GiShieldcomb, GiHole, GiTransportationRings, GiExplosionRays,
    // Shadow
    GiStrikingSplinter, GiEvilHand, GiTwoShadows, GiBellShield,
    GiQuicksand, GiBottledShadow, GiCloudyFork,
    // Earth
    GiMeteorImpact, GiFallingBoulder, GiFulguroPunch, GiPrayer, GiGroundSprout,
    GiStoneWall, GiHeavyThornyTriskelion, GiRobotGolem, GiEarthSpit,
    // Wind
    GiEchoRipples, GiIncomingRocket, GiHalfTornado, GiWindSlap, GiWindHole,
    GiStompTornado, GiCloudRing, GiPentarrowsTornado,
    // Water
    GiHeavyRain, GiDropletSplash, GiMagicSwirl, GiOilySpiral, GiWaveCrest,
    GiWaterSplash, GiAcidTube, GiTearTracks, GiGooExplosion,
    // Lightning
    GiLightningTree, GiChargedArrow, GiThorFist, GiDeadlyStrike, GiChainLightning,
    GiThunderStruck, GiLightningDissipation, GiBoltSaw, GiRoundStruck,
    // Life
    GiStarSattelites, GiDoubleRingedOrb, GiCherish, GiHealing, GiBarbedCoil,
    GiVortex, GiProcessor,
    // Death
    GiChemicalArrow, GiSinusoidalBeam, GiSkeletalHand, GiSoulVessel, GiRaiseSkeleton,
    GiOni, GiDeadEye, GiAnubis, GiChalkOutlineMurder,
    // Source
    GiStigmata, GiWingedEmblem, GiCurlingVines, GiScrollUnfurled, GiYinYang,
    GiBookAura, GiLifeInTheBalance, GiTemptation, GiSpatter, GiImprisoned, GiVineFlower, GiVines, GiSplurt,
    GiWhirlpoolShuriken, GiTransfuse, GiFamilyTree, GiInnerSelf,
} from 'react-icons/gi';

const AXES = {
    Source: ['bloodline', 'deity', 'nature', 'artifact', 'pact', 'study', 'emotion', 'forbidden'],
    Element: ['fire', 'ice', 'light', 'shadow', 'earth', 'wind', 'water', 'lightning', 'life', 'death'],
    Manifestation: ['projectile', 'beam', 'hand', 'aura', 'summon', 'barrier', 'glyph', 'transformation', 'nova'],
    // Cost: ['health', 'memory', 'emotion', 'time', 'lifespan', 'sanity', 'bond'],
    // Trigger: ['verbal', 'gesture', 'ritual', 'thought', 'rune', 'eye contact', 'proximity'],
    // Behavior: ['instant', 'lingering', 'growing', 'chain-reaction', 'delayed', 'channeled', 'infectious', 'fading'],
    Scale: ['self', 'single target', 'small area', 'large area', 'line', 'object-bound'],
};

const AXIS_NAMES = Object.keys(AXES);

const ELEMENT_COLORS = {
    fire:      { primary: '#b8432e', dark: '#6b1a0e', light: '#e8845a' },
    ice:       { primary: '#4a8db7', dark: '#1e3a52', light: '#8cc4e0' },
    light:     { primary: '#c4a85a', dark: '#6b5a28', light: '#e8d8a0' },
    shadow:    { primary: '#6b4fa0', dark: '#2e1f52', light: '#9b82cc' },
    earth:     { primary: '#8b6b3e', dark: '#4a3518', light: '#c4a06e' },
    wind:      { primary: '#6b8b7a', dark: '#2e4a3e', light: '#a0c4b0' },
    water:     { primary: '#2e6b8b', dark: '#12334a', light: '#5aa0c4' },
    lightning: { primary: '#c4a032', dark: '#6b5a12', light: '#e8d478' },
    life:      { primary: '#4a8b3e', dark: '#1e3b18', light: '#7dc06e' },
    death:     { primary: '#8b3e7a', dark: '#4a1840', light: '#c46eb0' },
};

const SPELL_ICONS = {
    fire: {
        projectile: GiUnstableProjectile, beam: GiBeamWake, hand: GiFireSpellCast,
        aura: GiBurningPassion, summon: GiIfrit, barrier: GiFireShield,
        glyph: GiFireRing, transformation: GiPyromaniac, nova: GiFlintSpark,
    },
    ice: {
        projectile: GiIceSpear, beam: GiBeamWake, hand: GiIceSpellCast,
        aura: GiIciclesAura, summon: GiIceGolem, barrier: GiIceShield,
        glyph: GiIciclesFence, transformation: null, nova: GiSpikyExplosion,
    },
    light: {
        projectile: GiHypersonicBolt, beam: GiLaserburn, hand: GiGlowingHands,
        aura: GiAura, summon: GiShinyEntrance, barrier: GiShieldcomb,
        glyph: GiHole, transformation: GiTransportationRings, nova: GiExplosionRays,
    },
    shadow: {
        projectile: GiStrikingSplinter, beam: null, hand: GiEvilHand,
        aura: GiBurningPassion, summon: GiTwoShadows, barrier: GiBellShield,
        glyph: GiQuicksand, transformation: GiBottledShadow, nova: GiCloudyFork,
    },
    earth: {
        projectile: GiMeteorImpact, beam: GiFallingBoulder, hand: GiFulguroPunch,
        aura: GiPrayer, summon: GiGroundSprout, barrier: GiStoneWall,
        glyph: GiHeavyThornyTriskelion, transformation: GiRobotGolem, nova: GiEarthSpit,
    },
    wind: {
        projectile: GiEchoRipples, beam: GiIncomingRocket, hand: GiHalfTornado,
        aura: GiWindSlap, summon: GiWhirlpoolShuriken, barrier: GiStompTornado,
        glyph: GiCloudRing, transformation: null, nova: GiPentarrowsTornado,
    },
    water: {
        projectile: GiHeavyRain, beam: GiDropletSplash, hand: GiMagicSwirl,
        aura: GiOilySpiral, summon: GiWaveCrest, barrier: GiWaterSplash,
        glyph: GiAcidTube, transformation: GiTearTracks, nova: GiSplurt,
    },
    lightning: {
        projectile: GiLightningTree, beam: GiChargedArrow, hand: GiThorFist,
        aura: GiDeadlyStrike, summon: GiChainLightning, barrier: GiThunderStruck,
        glyph: GiLightningDissipation, transformation: GiBoltSaw, nova: GiRoundStruck,
    },
    life: {
        projectile: GiStarSattelites, beam: GiDoubleRingedOrb, hand: GiCherish,
        aura: GiMagicSwirl, summon: GiHealing, barrier: GiBarbedCoil,
        glyph: GiVortex, transformation: null, nova: GiProcessor,
    },
    death: {
        projectile: GiChemicalArrow, beam: GiSinusoidalBeam, hand: GiSkeletalHand,
        aura: GiSoulVessel, summon: GiRaiseSkeleton, barrier: GiOni,
        glyph: GiDeadEye, transformation: GiAnubis, nova: GiChalkOutlineMurder,
    },
};

const SOURCE_COLORS = {
    bloodline:  '#8b3030',
    deity:      '#c4a860',
    nature:     '#4a7a3e',
    artifact:   '#7a6a4a',
    pact:       '#6b3060',
    study:      '#5a4a8b',
    emotion:    '#4a6b7a',
    forbidden:  '#3a1a1a',
};

const SOURCE_ICONS = {
    bloodline:  GiFamilyTree,
    deity:      GiWingedEmblem,
    nature:     GiVines,
    artifact:   GiScrollUnfurled,
    pact:       GiYinYang,
    study:      GiBookAura,
    emotion:    GiInnerSelf,
    forbidden:  GiImprisoned,
};

const ROLL_AXES = ['Source', 'Element', 'Manifestation'];

function roll() {
    const result = {};
    for (const axis of ROLL_AXES) {
        const options = AXES[axis];
        result[axis] = options[Math.floor(Math.random() * options.length)];
    }
    return result;
}

function MagicCard({ result }) {
    if (!result) return null;

    const el = ELEMENT_COLORS[result.Element] || { primary: '#666', dark: '#333', light: '#999' };
    const sourceColor = SOURCE_COLORS[result.Source] || '#3a3a3a';
    const Icon = SPELL_ICONS[result.Element]?.[result.Manifestation];
    const SourceIcon = SOURCE_ICONS[result.Source];

    return (
        /* Outer card — drop shadow for floating effect */
        <div
            className="w-full max-w-[240px] mx-auto aspect-[4/5] rounded-lg relative overflow-hidden transition-all duration-500"
            style={{
                boxShadow: `0 4px 24px rgba(0,0,0,0.5), 0 1px 3px rgba(0,0,0,0.3), 0 0 40px ${el.dark}40`,
            }}
        >
            {/* Frame — element gradient border */}
            <div
                className="absolute inset-0 rounded-lg"
                style={{ background: `linear-gradient(180deg, ${el.primary}, ${el.dark})` }}
            />

            {/* Metallic sheen on frame */}
            <div
                className="absolute inset-0 rounded-lg pointer-events-none"
                style={{ background: 'linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.03) 100%)' }}
            />

            {/* Grain texture on frame */}
            <div className="elysium-texture absolute inset-0 rounded-lg pointer-events-none" />

            {/* Inner card — dark recessed area */}
            <div className="absolute inset-[6px] rounded flex flex-col overflow-hidden bg-[#0e0e12]">

                {/* Art well — top 62% */}
                <div
                    className="relative flex-[62] flex items-center justify-center"
                    style={{
                        boxShadow: 'inset 0 1px 0 rgba(255,255,255,0.07), inset 0 -1px 0 rgba(0,0,0,0.3), inset 0 0 20px rgba(0,0,0,0.4)',
                    }}
                >
                    {/* Vignette */}
                    <div
                        className="absolute inset-0 pointer-events-none"
                        style={{ background: 'radial-gradient(ellipse at center, transparent 40%, rgba(0,0,0,0.5) 100%)' }}
                    />

                    {/* Element glow behind icon */}
                    <div
                        className="absolute inset-0 pointer-events-none"
                        style={{ background: `radial-gradient(ellipse at center, ${el.primary}15 0%, transparent 60%)` }}
                    />

                    {/* Source watermark */}
                    {SourceIcon && (
                        <div className="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.04]">
                            <SourceIcon size={200} color="#ffffff" />
                        </div>
                    )}

                    {/* Main spell icon */}
                    {Icon && (
                        <Icon
                            size={100}
                            color={el.light}
                            className="relative z-10"
                            style={{
                                filter: `drop-shadow(0 0 12px ${el.primary}60) drop-shadow(0 0 4px ${el.primary}40)`,
                            }}
                        />
                    )}
                </div>

                {/* Divider with element gem */}
                <div className="relative h-[1px] shrink-0 z-10" style={{ backgroundColor: `${el.primary}30` }}>
                    {/* Center gem */}
                    <div className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                        <div
                            className="w-5 h-5 rotate-45 rounded-[2px]"
                            style={{
                                background: `linear-gradient(135deg, ${el.light}, ${el.primary})`,
                                boxShadow: `0 0 8px ${el.primary}60, inset 0 1px 0 rgba(255,255,255,0.2)`,
                            }}
                        />
                    </div>
                </div>

                {/* Text plate — bottom 38% */}
                <div
                    className="relative flex-[38] flex flex-col items-center justify-center px-4 py-3 bg-[#141418]"
                    style={{
                        boxShadow: 'inset 0 1px 0 rgba(255,255,255,0.04)',
                    }}
                >
                    {/* Manifestation label */}
                    <p className="font-sans text-[11px] font-bold uppercase tracking-[0.15em] text-[#e3e1e9]/90 mb-2">
                        {result.Manifestation}
                    </p>

                    {/* Element badge */}
                    <span
                        className="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-widest mb-1.5"
                        style={{
                            backgroundColor: `${el.primary}20`,
                            color: el.light,
                        }}
                    >
                        {result.Element}
                    </span>

                    {/* Source badge */}
                    <span
                        className="px-2 py-0.5 rounded-full text-[8px] uppercase tracking-wider"
                        style={{
                            backgroundColor: `${sourceColor}20`,
                            color: `${sourceColor}cc`,
                        }}
                    >
                        {result.Source}
                    </span>
                </div>
            </div>
        </div>
    );
}

export default function MagicGenerator() {
    const [result, setResult] = useState(null);

    return (
        <div className="h-screen bg-[#121318] text-[#e3e1e9] font-sans overflow-hidden flex flex-col items-center px-6 py-8">
            <div className="max-w-lg w-full flex-1 overflow-y-auto">
                <h1 className="font-narration text-3xl text-center mb-6 text-[#e3e1e9]">Magic Generator</h1>

                {/* Card preview */}
                <div className="mb-8">
                    {result ? (
                        <MagicCard result={result} />
                    ) : (
                        <div className="w-full max-w-[240px] mx-auto aspect-[4/5] border border-[#554434]/30 border-dashed rounded-lg bg-[#0d0e13] flex items-center justify-center">
                            <span className="text-[#a38d7a]/40 text-xs font-sans uppercase tracking-widest">Roll to reveal</span>
                        </div>
                    )}
                </div>
                <div className="flex justify-center">
                    <button
                        onClick={() => setResult(roll())}
                        className="px-8 py-3 gold-shimmer rounded-lg font-sans font-bold text-[#3c2f00] uppercase tracking-widest text-xs active:scale-95 transition-transform shadow-[0_0_20px_rgba(239,200,78,0.25)]"
                    >
                        <i className="fa-solid fa-dice mr-2" />
                        Roll Magic
                    </button>
                </div>
                <div className="space-y-4 mb-10">
                    {AXIS_NAMES.map(axis => {
                        const active = result && axis in result;
                        return (
                            <div key={axis} className={active ? 'opacity-100' : 'opacity-30'}>
                                <p className="font-sans text-[10px] font-bold uppercase tracking-widest text-[#efc84e] mb-1.5">
                                    {axis}
                                </p>
                                <div className="flex flex-wrap gap-1.5">
                                    {AXES[axis].map(item => {
                                        const selected = active && result[axis] === item;
                                        return (
                                            <span
                                                key={item}
                                                className={`px-2.5 py-1 rounded text-xs font-sans ${
                                                    selected
                                                        ? 'bg-[#efc84e] text-[#3c2f00] font-bold'
                                                        : 'bg-[#1e1f25] text-[#a38d7a]'
                                                }`}
                                            >
                                                {item}
                                            </span>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}
                </div>


            </div>
        </div>
    );
}
