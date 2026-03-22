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
    fire:      { from: '#e8552e' },
    ice:       { from: '#6ec6e6' },
    light:     { from: '#f0d060' },
    shadow:    { from: '#8878a8' },
    earth:     { from: '#c4884a' },
    wind:      { from: '#a0d8c0' },
    water:     { from: '#4090d0' },
    lightning: { from: '#e0cc40' },
    life:      { from: '#60c060' },
    death:     { from: '#c060d0' },
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
    bloodline:  { bg: '#301818', highlight: '#f44b4b' },
    deity:      { bg: '#302818', highlight: '#ffc402' },
    nature:     { bg: '#182818', highlight: '#4ff44f' },
    artifact:   { bg: '#554d41', highlight: '#ecd9b8' },
    pact:       { bg: '#182828', highlight: '#98c7fd' },
    study:      { bg: '#272743', highlight: '#4f4ffd' },
    emotion:    { bg: '#281828', highlight: '#ff52ff' },
    forbidden:  { bg: '#222224', highlight: '#681eff' },
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

    const source = SOURCE_COLORS[result.Source] || { bg: '#1a1a1a', highlight: '#3a3a3a' };
    const iconColor = ELEMENT_COLORS[result.Element]?.from || '#ffffff';
    const Icon = SPELL_ICONS[result.Element]?.[result.Manifestation];
    const SourceIcon = SOURCE_ICONS[result.Source];
    const stroke = `drop-shadow(2px 0 0 ${source.highlight}) drop-shadow(-2px 0 0 ${source.highlight}) drop-shadow(0 2px 0 ${source.highlight}) drop-shadow(0 -2px 0 ${source.highlight})`;

    return (
        <div className="w-full max-w-[240px] mx-auto aspect-[4/5] border border-[#efc84e]/30 rounded-lg p-4 flex flex-col relative overflow-hidden transition-all duration-500 bg-[#0d0e13]">
            {/* Texture */}
            <div className="elysium-texture absolute inset-0 pointer-events-none" />

            {/* Source icon as faint background */}
            {SourceIcon && (
                <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <SourceIcon size={500} color={source.bg} />
                </div>
            )}

            {/* Top accent line */}
            <div className="h-[1px] w-full bg-gradient-to-r from-transparent via-[#efc84e]/40 to-transparent mb-3" />

            {/* Card content */}
            <div className="flex-1 flex flex-col items-center justify-center relative z-10 ">
                {Icon ? <Icon size={200} color={iconColor} style={{ filter: stroke }} /> : null}
            </div>

            {/* Bottom accent line */}
            <div className="h-[1px] w-full bg-gradient-to-r from-transparent via-[#efc84e]/40 to-transparent mt-3" />

            {/* Corner decorations */}
            <div className="absolute top-1 right-1 w-6 h-6 border-t border-r border-[#efc84e]/20 pointer-events-none" />
            <div className="absolute bottom-1 left-1 w-6 h-6 border-b border-l border-[#efc84e]/20 pointer-events-none" />
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
