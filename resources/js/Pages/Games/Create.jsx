import { useState, useEffect, useRef, useCallback } from 'react';
import Layout from '@/Layouts/Layout';
import { MatrixCell, Grid2x2Cell } from './ImageCells';
import ConceptArtPreview from './ConceptArtPreview';
import Drawer from './Drawer';
import LoadingCard from './LoadingCard';
import CharacterDrawerContent from './CharacterDrawerContent';
import WorldDrawerContent from './WorldDrawerContent';

export default function Create() {
    const [generating, setGenerating] = useState(false);
    const [generated, setGenerated] = useState(null);
    const [error, setError] = useState(null);
    const [characterDrawerOpen, setCharacterDrawerOpen] = useState(false);
    const [worldDrawerOpen, setWorldDrawerOpen] = useState(false);
    const [customizeMode, setCustomizeMode] = useState(null); // 'ai' | 'custom' | null
    const [characterAppearance, setCharacterAppearance] = useState(null);
    const [characterTraits, setCharacterTraits] = useState(null);
    const [characterStats, setCharacterStats] = useState(null);
    const pollIntervalRef = useRef(null);

    // Load from localStorage on mount
    useEffect(() => {
        const cached = localStorage.getItem('dnd_generated');
        if (cached) {
            try {
                const parsed = JSON.parse(cached);
                setGenerated(parsed);
                if (parsed.imagesCacheKey && !parsed.world_lore_image_path) {
                    pollWorldImages(parsed.imagesCacheKey);
                }
            } catch (e) {
                localStorage.removeItem('dnd_generated');
            }
        }

        // Only restore customizeMode if we also have generated data
        // Otherwise it's a stale session — start fresh
        const isCustom = localStorage.getItem('dnd_custom_character');
        if (isCustom && cached) {
            setCustomizeMode('custom');
        } else if (isCustom && !cached) {
            localStorage.removeItem('dnd_custom_character');
        }

        const appearance = localStorage.getItem('dnd_character_appearance');
        if (appearance) {
            try { setCharacterAppearance(JSON.parse(appearance)); }
            catch (e) { localStorage.removeItem('dnd_character_appearance'); }
        }

        const traits = localStorage.getItem('dnd_character_traits');
        if (traits) {
            try { setCharacterTraits(JSON.parse(traits)); }
            catch (e) { localStorage.removeItem('dnd_character_traits'); }
        }

        const stats = localStorage.getItem('dnd_character_stats');
        if (stats) {
            try { setCharacterStats(JSON.parse(stats)); }
            catch (e) { localStorage.removeItem('dnd_character_stats'); }
        }
    }, []);

    // Persist generated data
    useEffect(() => {
        if (generated) {
            localStorage.setItem('dnd_generated', JSON.stringify(generated));
        }
    }, [generated]);

    // --- Polling for world images ---

    const stopPolling = useCallback(() => {
        if (pollIntervalRef.current) {
            clearInterval(pollIntervalRef.current);
            pollIntervalRef.current = null;
        }
    }, []);

    const pollWorldImages = useCallback((cacheKey) => {
        stopPolling();
        pollIntervalRef.current = setInterval(async () => {
            try {
                const res = await fetch(`/game/world-images/${cacheKey}`);
                const data = await res.json();
                if (data.status === 'done') {
                    stopPolling();
                    setGenerated(prev => prev ? { ...prev, world_lore_image_path: data.world_lore_image_path } : prev);
                } else if (data.status === 'failed') {
                    stopPolling();
                }
            } catch {
                stopPolling();
            }
        }, 2000);
    }, [stopPolling]);

    useEffect(() => stopPolling, [stopPolling]);

    // --- Actions ---

    const fetchWorldGeneration = useCallback(async () => {
        stopPolling();
        setGenerating(true);
        setGenerated(null);
        setError(null);
        try {
            const res = await fetch('/game/world-generate');
            if (!res.ok) throw new Error('Failed to generate world');
            const data = await res.json();
            setGenerated(data);
            setGenerating(false);
            if (data.imagesCacheKey) {
                pollWorldImages(data.imagesCacheKey);
            }
        } catch (err) {
            setError(err.message);
            setGenerating(false);
        }
    }, [pollWorldImages]);

    const handleChoiceAI = () => {
        setCustomizeMode('ai');
        fetchWorldGeneration();
    };

    const handleChoiceCustom = () => {
        setCustomizeMode('custom');
        localStorage.setItem('dnd_custom_character', 'true');
        fetchWorldGeneration();
    };

    const handleRegenerate = () => {
        fetchWorldGeneration();
    };

    const handleStartGame = async () => {
        if (!generated) return;

        try {
            const payload = { ...generated };
            if (!payload.character) payload.character = {};
            if (characterTraits) payload.character = { ...payload.character, ...characterTraits };
            if (characterStats) payload.character = { ...payload.character, ...characterStats };
            if (characterAppearance) payload.character = { ...payload.character, appearance: characterAppearance };

            const response = await fetch('/game', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (response.ok && data.redirect) {
                localStorage.removeItem('dnd_generated');
                window.location.href = data.redirect;
            } else {
                throw new Error(data.message || 'Failed to create game');
            }
        } catch (err) {
            setError(err.message);
        }
    };

    const handleDiscard = () => {
        stopPolling();
        localStorage.removeItem('dnd_generated');
        localStorage.removeItem('dnd_character_appearance');
        localStorage.removeItem('dnd_character_traits');
        localStorage.removeItem('dnd_character_stats');
        localStorage.removeItem('dnd_custom_character');
        window.location.href = '/game';
    };

    // --- Render states ---

    const showChoice = !customizeMode && !generated && !generating;
    const isGenerating = generating;
    const isPreviewReady = generated && !generating;

    return (
        <Layout>
            <div className="px-4 py-6 h-[calc(100vh-60px)] flex flex-col overflow-hidden bg-[#121318]">
                {/* Step 1: Choose character mode */}
                {showChoice && (
                    <div className="flex-1 flex items-center justify-center">
                        <div className="w-full max-w-sm">
                            <h2 className="font-narration text-xl text-[#e3e1e9] text-center mb-2 italic">
                                Create Your Character
                            </h2>
                            <p className="text-[#a38d7a] text-sm text-center mb-6">
                                Do you want to customize your character yourself?
                            </p>
                            <div className="flex flex-col gap-3">
                                <button
                                    onClick={handleChoiceCustom}
                                    className="w-full py-3 gold-shimmer rounded-lg font-semibold text-[#3c2f00] transition active:scale-[0.98]"
                                >
                                    <i className="fa-solid fa-paintbrush mr-2"></i>
                                    I'll create my own
                                </button>
                                <button
                                    onClick={handleChoiceAI}
                                    className="w-full py-3 bg-[#292a2f] hover:bg-[#34343a] border border-[#554434]/20 rounded-lg font-semibold text-[#e3e1e9] transition"
                                >
                                    <i className="fa-solid fa-wand-magic-sparkles mr-2 text-[#efc84e]"></i>
                                    Let AI decide
                                </button>
                                <a
                                    href="/game"
                                    className="w-full py-2 text-center text-[#a38d7a] text-sm hover:text-[#e3e1e9] transition"
                                >
                                    Back to games
                                </a>
                            </div>
                        </div>
                    </div>
                )}

                {/* Step 2: Generating */}
                {isGenerating && (
                    <div className="flex-1 flex flex-col items-center gap-3 min-h-0 overflow-hidden">
                        {customizeMode === 'ai' ? (
                            <LoadingCard
                                icon="fa-user"
                                label="Character"
                                messages={['Setting up race', 'Calculating age', 'Setting up backstory', 'Setting up goals', 'Creating secrets']}
                                duration={30}
                            />
                        ) : customizeMode === 'custom' ? (
                            <CustomCharacterCard appearance={characterAppearance} />
                        ) : (
                            <div className="flex-1 flex items-center justify-center">
                                <div className="text-center">
                                    <i className="fa-solid fa-dice-d20 fa-spin text-5xl text-[#efc84e] mb-4"></i>
                                    <p className="text-[#a38d7a]">Rolling for destiny...</p>
                                </div>
                            </div>
                        )}

                        <LoadingCard
                            icon="fa-globe"
                            label="World"
                            messages={['Coming up with time and place', 'Setting physics rules', 'Calculating magic use', 'Writing up lore', 'Drawing the map']}
                            duration={30}
                        />

                        <div className="flex gap-3 shrink-0 w-full self-stretch">
                            <button
                                disabled
                                className="flex-1 py-3 bg-[#292a2f] rounded-lg font-semibold text-[#a38d7a] transition opacity-50 cursor-not-allowed"
                            >
                                <i className="fa-solid fa-hourglass-half mr-2"></i>
                                Generating...
                            </button>
                            <button
                                onClick={handleDiscard}
                                className="px-4 py-3 bg-[#292a2f] hover:bg-[#34343a] border border-[#554434]/20 rounded-lg transition"
                            >
                                <i className="fa-solid fa-xmark text-[#a38d7a]"></i>
                            </button>
                        </div>
                    </div>
                )}

                {/* Error */}
                {error && (
                    <div className="bg-[#93000a]/20 border border-[#93000a]/40 rounded-lg p-4 mb-4">
                        <p className="text-[#ffb4ab]">{error}</p>
                    </div>
                )}

                {/* Step 3: Preview — character + world cards */}
                {isPreviewReady && (
                    <div className="flex-1 flex flex-col items-center gap-3 min-h-0 overflow-hidden">
                        <CharacterPreviewCard
                            customizeMode={customizeMode}
                            characterAppearance={characterAppearance}
                            generated={generated}
                            onOpenDrawer={() => setCharacterDrawerOpen(true)}
                        />

                        <WorldPreviewCard
                            generated={generated}
                            onOpenDrawer={() => setWorldDrawerOpen(true)}
                        />

                        <div className="flex gap-3 shrink-0 w-full self-stretch">
                            <button
                                onClick={handleStartGame}
                                className="flex-1 py-3 gold-shimmer rounded-lg font-semibold text-[#3c2f00] transition active:scale-[0.98] shadow-[0_0_15px_rgba(239,200,78,0.2)]"
                            >
                                <i className="fa-solid fa-play mr-2"></i>
                                Start Game
                            </button>
                            <button
                                onClick={handleRegenerate}
                                className="px-4 py-3 bg-[#292a2f] hover:bg-[#34343a] border border-[#554434]/20 rounded-lg transition"
                            >
                                <i className="fa-solid fa-rotate text-[#a38d7a]"></i>
                            </button>
                            <button
                                onClick={handleDiscard}
                                className="px-4 py-3 bg-[#292a2f] hover:bg-[#34343a] border border-[#554434]/20 rounded-lg transition"
                            >
                                <i className="fa-solid fa-xmark text-[#a38d7a]"></i>
                            </button>
                        </div>
                    </div>
                )}

                {/* Drawers */}
                <Drawer isOpen={characterDrawerOpen} onClose={() => setCharacterDrawerOpen(false)} title="Character" direction="left">
                    {generated?.character && <CharacterDrawerContent character={generated.character} />}
                </Drawer>

                <Drawer isOpen={worldDrawerOpen} onClose={() => setWorldDrawerOpen(false)} title="World" direction="right">
                    {generated && (
                        <WorldDrawerContent
                            world={generated.world}
                            lore={generated.world_lore}
                            hooks={generated.world_hooks}
                            imagePath={generated.world_lore_image_path}
                        />
                    )}
                </Drawer>
            </div>
        </Layout>
    );
}

// --- Sub-components ---

function CustomCharacterCard({ appearance }) {
    if (appearance) {
        return (
            <a
                href="/game/character-builder"
                className="flex-1 min-h-0 aspect-square max-w-full bg-[#1e1f25] border border-[#554434]/20 rounded-lg p-4 text-left relative overflow-hidden hover:border-[#efc84e]/30 transition block"
            >
                <ConceptArtPreview appearance={appearance} className="absolute inset-0" />
                <div className="absolute inset-0 bg-gradient-to-r from-[#121318]/80 to-black/90"></div>
                <div className="relative">
                    <div className="flex items-center gap-2 text-[#a38d7a] text-[9px] font-bold uppercase tracking-[0.3em] mb-2">
                        <i className="fa-solid fa-user"></i>
                        <span>Character</span>
                    </div>
                    <p className="text-[#efc84e] font-narration text-lg">Custom Character</p>
                    <p className="text-[#a38d7a]/50 text-xs mt-1">Tap to edit</p>
                </div>
            </a>
        );
    }

    return (
        <a
            href="/game/character-builder"
            className="flex-1 min-h-0 aspect-square max-w-full bg-[#1e1f25] border border-[#554434]/30 border-dashed rounded-lg p-4 flex flex-col items-center justify-center hover:border-[#efc84e]/30 transition"
        >
            <i className="fa-solid fa-user-pen text-4xl text-[#554434]/40 mb-3"></i>
            <p className="text-[#e3e1e9] font-narration">Customize Character</p>
            <p className="text-[#a38d7a]/50 text-sm mt-1">Tap to open builder</p>
        </a>
    );
}

function CharacterPreviewCard({ customizeMode, characterAppearance, generated, onOpenDrawer }) {
    if (customizeMode === 'custom') {
        return <CustomCharacterCard appearance={characterAppearance} />;
    }

    return (
        <button
            onClick={onOpenDrawer}
            className="flex-1 min-h-0 aspect-square max-w-full bg-[#1e1f25] border border-[#554434]/20 rounded-lg p-4 text-left hover:border-[#efc84e]/30 transition relative overflow-hidden"
        >
            {generated.character?.image_path && (
                <>
                    <MatrixCell
                        imagePath={generated.character.image_path}
                        cell="zxc"
                        className="absolute inset-0"
                    />
                    <div className="absolute inset-0 bg-gradient-to-r from-[#121318]/90 to-black/40"></div>
                </>
            )}
            <div className="relative">
                <div className="flex items-center gap-2 text-[#a38d7a] text-[9px] font-bold uppercase tracking-[0.3em] mb-2">
                    <i className="fa-solid fa-user"></i>
                    <span>Character</span>
                </div>
                <h2 className="text-2xl font-narration text-[#efc84e] mb-1">{generated.character?.name}</h2>
                <p className="text-[#dbc2ad] text-sm mb-3">{generated.character?.job || 'Adventurer'}</p>
                <p className="text-[#a38d7a] text-sm line-clamp-2">{generated.character?.info}</p>
                <div className="absolute bottom-0 right-0 text-[#554434]">
                    <i className="fa-solid fa-chevron-right"></i>
                </div>
            </div>
        </button>
    );
}

function WorldPreviewCard({ generated, onOpenDrawer }) {
    return (
        <button
            onClick={onOpenDrawer}
            className="flex-1 min-h-0 aspect-square max-w-full bg-[#1e1f25] border border-[#554434]/20 rounded-lg p-4 text-left hover:border-[#efc84e]/30 transition relative overflow-hidden"
        >
            {generated.world_lore_image_path && (
                <>
                    <Grid2x2Cell
                        imagePath={generated.world_lore_image_path}
                        cell={4}
                        className="absolute inset-0"
                        cover={true}
                    />
                    <div className="absolute inset-0 bg-gradient-to-l from-black/100 to-black/40"></div>
                </>
            )}
            <div className="relative text-right">
                <div className="flex justify-end items-center gap-2 text-[#a38d7a] text-[9px] font-bold uppercase tracking-[0.3em] mb-2">
                    <i className="fa-solid fa-globe"></i>
                    <span>World</span>
                </div>
                <h2 className="text-xl font-narration text-[#e3e1e9] mb-1">{generated.world?.time?.split(',')[0] || 'Unknown Era'}</h2>
                <p className="text-[#a38d7a] text-sm line-clamp-2">{generated.world?.environment_description}</p>
                {(generated.world_lore?.length > 0 || generated.world_hooks?.length > 0) && (
                    <div className="mt-3 flex gap-2 flex-wrap justify-end">
                        {(generated.world_lore || []).slice(0, 2).map((lore, i) => (
                            <span key={`lore-${i}`} className="text-[10px] px-2 py-1 bg-[#292a2f] text-[#a38d7a] rounded">
                                {lore.name}
                            </span>
                        ))}
                        {(generated.world_hooks || []).slice(0, 2).map((hook, i) => (
                            <span key={`hook-${i}`} className="text-[10px] px-2 py-1 bg-[#93000a]/30 text-[#ffb4a8] rounded">
                                {hook.name}
                            </span>
                        ))}
                    </div>
                )}
                <div className="absolute bottom-0 right-0 text-[#554434]">
                    <i className="fa-solid fa-chevron-right"></i>
                </div>
            </div>
        </button>
    );
}
