import { useState, useEffect, useRef, useCallback } from 'react';
import { Link } from '@inertiajs/react';
import Layout from '@/Layouts/Layout';

export default function Index({ games }) {
    const [generating, setGenerating] = useState(false);
    const [generated, setGenerated] = useState(null);
    const [error, setError] = useState(null);
    const [characterDrawerOpen, setCharacterDrawerOpen] = useState(false);
    const [worldDrawerOpen, setWorldDrawerOpen] = useState(false);
    const [showChoiceModal, setShowChoiceModal] = useState(false);
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
                // Resume polling if images were still pending
                if (parsed.imagesCacheKey && !parsed.world_lore_image_path) {
                    pollWorldImages(parsed.imagesCacheKey);
                }
            } catch (e) {
                localStorage.removeItem('dnd_generated');
            }
        }

        // Restore customizeMode if it was a custom character session
        const isCustom = localStorage.getItem('dnd_custom_character');
        if (isCustom) {
            setCustomizeMode('custom');
        }

        const appearance = localStorage.getItem('dnd_character_appearance');
        if (appearance) {
            try {
                setCharacterAppearance(JSON.parse(appearance));
            } catch (e) {
                localStorage.removeItem('dnd_character_appearance');
            }
        }

        const traits = localStorage.getItem('dnd_character_traits');
        if (traits) {
            try {
                setCharacterTraits(JSON.parse(traits));
            } catch (e) {
                localStorage.removeItem('dnd_character_traits');
            }
        }

        const stats = localStorage.getItem('dnd_character_stats');
        if (stats) {
            try {
                setCharacterStats(JSON.parse(stats));
            } catch (e) {
                localStorage.removeItem('dnd_character_stats');
            }
        }
    }, []);

    // Save to localStorage when generated
    useEffect(() => {
        if (generated) {
            localStorage.setItem('dnd_generated', JSON.stringify(generated));
        }
    }, [generated]);

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

    // Clean up polling on unmount
    useEffect(() => stopPolling, [stopPolling]);

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

    const handleGenerate = () => {
        setShowChoiceModal(true);
    };

    const handleChoiceAI = () => {
        setShowChoiceModal(false);
        setCustomizeMode('ai');
        fetchWorldGeneration();
    };

    const handleChoiceCustom = () => {
        setShowChoiceModal(false);
        setCustomizeMode('custom');
        localStorage.setItem('dnd_custom_character', 'true');
        fetchWorldGeneration();
    };

    const handleStartGame = async () => {
        if (!generated) return;

        try {
            const payload = { ...generated };
            if (characterTraits && payload.character) {
                payload.character = { ...payload.character, ...characterTraits };
            }
            if (characterStats && payload.character) {
                payload.character = { ...payload.character, ...characterStats };
            }

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
        setGenerated(null);
        setGenerating(false);
        setCustomizeMode(null);
        setCharacterAppearance(null);
        setCharacterTraits(null);
        setCharacterStats(null);
        localStorage.removeItem('dnd_generated');
        localStorage.removeItem('dnd_character_appearance');
        localStorage.removeItem('dnd_character_traits');
        localStorage.removeItem('dnd_character_stats');
        localStorage.removeItem('dnd_custom_character');
    };

    return (
        <Layout>
            <div className="px-4 py-6 h-[calc(100vh-60px)] flex flex-col overflow-hidden">
                {/* Generate Button */}
                {!generated && !generating && (
                    <>
                        <button
                            onClick={handleGenerate}
                            className="w-full py-4 bg-red-600 hover:bg-red-700 rounded-xl font-semibold text-lg transition active:scale-[0.98]"
                        >
                            <i className="fa-solid fa-dice-d20 mr-2"></i>
                            Generate World
                        </button>

                        {/* Games List */}
                        <div className="mt-8 flex-1 overflow-auto">
                            <h2 className="text-zinc-300 text-sm font-semibold uppercase tracking-wide mb-4">
                                Your Games
                            </h2>

                            {games.length === 0 ? (
                                <div className="text-center py-12 text-zinc-600">
                                    <p>No games yet</p>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {games.map(game => (
                                        <Link
                                            key={game.id}
                                            href={`/game/${game.id}`}
                                            className="block bg-zinc-900 border border-zinc-800 rounded-xl p-4 hover:border-zinc-700 transition"
                                        >
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <h3 className="font-semibold">{game.name}</h3>
                                                    <p className="text-zinc-300 text-sm">
                                                        {new Date(game.created_at).toLocaleDateString()}
                                                    </p>
                                                </div>
                                                <i className="fa-solid fa-chevron-right text-zinc-600"></i>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </div>
                    </>
                )}

                {/* Loading State — only for non-customize mode (old AI-only path) */}
                {generating && !customizeMode && (
                    <div className="flex-1 flex items-center justify-center">
                        <div className="text-center">
                            <i className="fa-solid fa-dice-d20 fa-spin text-5xl text-red-500 mb-4"></i>
                            <p className="text-zinc-400">Rolling for destiny...</p>
                        </div>
                    </div>
                )}

                {/* Error */}
                {error && (
                    <div className="bg-red-900/30 border border-red-800 rounded-lg p-4 mb-4">
                        <p className="text-red-400">{error}</p>
                    </div>
                )}

                {/* Generated Preview - Two Cards */}
                {generated && !generating && (
                    <div className="flex-1 flex flex-col items-center gap-3 min-h-0 overflow-hidden">
                        {/* Character Card - Top Half */}
                        {customizeMode === 'custom' ? (
                            characterAppearance ? (
                                <a
                                    href="/game/character-builder"
                                    className="flex-1 min-h-0 aspect-square max-w-full bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-left relative overflow-hidden hover:border-red-500/50 transition block"
                                >
                                    <ConceptArtPreview appearance={characterAppearance} className="absolute inset-0" />
                                    <div className="absolute inset-0 bg-gradient-to-r from-[#3d0d09]/50  to-black/90"></div>
                                    <div className="relative">
                                        <div className="flex items-center gap-2 text-zinc-300 text-xs uppercase tracking-wide mb-2">
                                            <i className="fa-solid fa-user"></i>
                                            <span>Character</span>
                                        </div>
                                        <p className="text-red-500 font-semibold">Custom Character</p>
                                        <p className="text-zinc-500 text-xs mt-1">Tap to edit</p>
                                    </div>
                                </a>
                            ) : (
                                <a
                                    href="/game/character-builder"
                                    className="flex-1 min-h-0 aspect-square max-w-full bg-zinc-900 border border-zinc-800 border-dashed rounded-xl p-4 flex flex-col items-center justify-center hover:border-red-500/50 transition"
                                >
                                    <i className="fa-solid fa-user-pen text-4xl text-zinc-600 mb-3"></i>
                                    <p className="text-zinc-400 font-semibold">Customize Character</p>
                                    <p className="text-zinc-600 text-sm mt-1">Tap to open builder</p>
                                </a>
                            )
                        ) : (
                            <button
                                onClick={() => setCharacterDrawerOpen(true)}
                                className="flex-1 min-h-0 aspect-square max-w-full bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-left hover:border-red-500/50 transition relative overflow-hidden"
                            >
                                {/* Background image - ZXC cell */}
                                {generated.character?.image_path && (
                                    <>
                                        <MatrixCell
                                            imagePath={generated.character.image_path}
                                            cell="zxc"
                                            className="absolute inset-0 "
                                        />
                                        {/* Gradient overlay */}
                                        <div className="absolute inset-0 bg-gradient-to-r from-[#3d0d09]/100  to-black/40"></div>
                                    </>
                                )}
                                <div className="relative">
                                    <div className="flex items-center gap-2 text-zinc-300 text-xs uppercase tracking-wide mb-2">
                                        <i className="fa-solid fa-user"></i>
                                        <span>Character</span>
                                    </div>
                                    <h2 className="text-2xl font-bold text-red-500 mb-1">{generated.character?.name}</h2>
                                    <p className="text-zinc-300 text-sm mb-3">{generated.character?.job || 'Adventurer'}</p>
                                    <p className="text-zinc-400 text-sm line-clamp-2">{generated.character?.info}</p>
                                    <div className="absolute bottom-0 right-0 text-zinc-700">
                                        <i className="fa-solid fa-chevron-right"></i>
                                    </div>
                                </div>
                            </button>
                        )}

                        {/* World Card - Bottom Half */}
                        <button
                            onClick={() => setWorldDrawerOpen(true)}
                            className="flex-1 min-h-0 aspect-square max-w-full bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-left hover:border-red-500/50 transition relative overflow-hidden"
                        >
                            {/* Background image - lore cell 4 */}
                            {generated.world_lore_image_path && (
                                <>
                                    <Grid2x2Cell
                                        imagePath={generated.world_lore_image_path}
                                        cell={4}
                                        className="absolute inset-0"
                                        cover={true}
                                    />
                                    {/* Gradient overlay */}
                                    <div className="absolute inset-0 bg-gradient-to-l from-black/100 to-black/40"></div>
                                </>
                            )}
                            <div className="relative text-right">
                                <div className="flex justify-end items-center gap-2 text-zinc-300 text-xs uppercase tracking-wide mb-2">
                                    <i className="fa-solid fa-globe"></i>
                                    <span>World</span>
                                </div>
                                <h2 className="text-xl font-bold mb-1">{generated.world?.time?.split(',')[0] || 'Unknown Era'}</h2>
                                <p className="text-zinc-400 text-sm line-clamp-2">{generated.world?.environment_description}</p>
                                {(generated.world_lore?.length > 0 || generated.world_hooks?.length > 0) && (
                                    <div className="mt-3 flex gap-2 flex-wrap">
                                        {(generated.world_lore || []).slice(0, 2).map((lore, i) => (
                                            <span key={`lore-${i}`} className="text-[10px] px-2 py-1 bg-zinc-800 text-zinc-400 rounded">
                                                {lore.name}
                                            </span>
                                        ))}
                                        {(generated.world_hooks || []).slice(0, 2).map((hook, i) => (
                                            <span key={`hook-${i}`} className="text-[10px] px-2 py-1 bg-red-900/60 text-red-400 rounded">
                                                {hook.name}
                                            </span>
                                        ))}
                                    </div>
                                )}
                                <div className="absolute bottom-0 right-0 text-zinc-700">
                                    <i className="fa-solid fa-chevron-right"></i>
                                </div>
                            </div>
                        </button>

                        {/* Action Buttons */}
                        <div className="flex gap-3 shrink-0 w-full self-stretch">
                            <button
                                onClick={handleStartGame}
                                className="flex-1 py-3 bg-red-600 hover:bg-red-700 rounded-xl font-semibold transition"
                            >
                                <i className="fa-solid fa-play mr-2"></i>
                                Start Game
                            </button>
                            <button
                                onClick={handleGenerate}
                                className="px-4 py-3 bg-zinc-800 hover:bg-zinc-700 rounded-xl transition"
                            >
                                <i className="fa-solid fa-rotate"></i>
                            </button>
                            <button
                                onClick={handleDiscard}
                                className="px-4 py-3 bg-zinc-800 hover:bg-zinc-700 rounded-xl transition"
                            >
                                <i className="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                )}

                {/* Customize Mode - Loading state while world is generating */}
                {customizeMode && !generated && generating && (
                    <div className="flex-1 flex flex-col items-center gap-3 min-h-0 overflow-hidden">
                        {/* Character Card */}
                        {customizeMode === 'ai' ? (
                            <LoadingCard
                                icon="fa-user"
                                label="Character"
                                messages={[
                                    'Setting up race',
                                    'Calculating age',
                                    'Setting up backstory',
                                    'Setting up goals',
                                    'Creating secrets',
                                ]}
                                duration={30}
                            />
                        ) : characterAppearance ? (
                            <a
                                href="/game/character-builder"
                                className="flex-1 min-h-0 aspect-square max-w-full bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-left relative overflow-hidden hover:border-red-500/50 transition block"
                            >
                                <ConceptArtPreview appearance={characterAppearance} className="absolute inset-0" />
                                <div className="absolute inset-0 bg-gradient-to-r from-[#3d0d09]/50  to-black/90"></div>
                                <div className="relative">
                                    <div className="flex items-center gap-2 text-zinc-300 text-xs uppercase tracking-wide mb-2">
                                        <i className="fa-solid fa-user"></i>
                                        <span>Character</span>
                                    </div>
                                    <p className="text-red-500 font-semibold">Custom Character</p>
                                    <p className="text-zinc-500 text-xs mt-1">Tap to edit</p>
                                </div>
                            </a>
                        ) : (
                            <a
                                href="/game/character-builder"
                                className="flex-1 min-h-0 aspect-square max-w-full bg-zinc-900 border border-zinc-800 border-dashed rounded-xl p-4 flex flex-col items-center justify-center hover:border-red-500/50 transition"
                            >
                                <i className="fa-solid fa-user-pen text-4xl text-zinc-600 mb-3"></i>
                                <p className="text-zinc-400 font-semibold">Customize Character</p>
                                <p className="text-zinc-600 text-sm mt-1">Tap to open builder</p>
                            </a>
                        )}

                        {/* World Card */}
                        <LoadingCard
                            icon="fa-globe"
                            label="World"
                            messages={[
                                'Coming up with time and place',
                                'Setting physics rules',
                                'Calculating magic use',
                                'Writing up lore',
                                'Drawing the map',
                            ]}
                            duration={30}
                        />

                        {/* Action Buttons */}
                        <div className="flex gap-3 shrink-0 w-full self-stretch">
                            <button
                                disabled
                                className="flex-1 py-3 bg-zinc-700 rounded-xl font-semibold transition opacity-50 cursor-not-allowed"
                            >
                                <i className="fa-solid fa-hourglass-half mr-2"></i>
                                Generating...
                            </button>
                            <button
                                onClick={handleDiscard}
                                className="px-4 py-3 bg-zinc-800 hover:bg-zinc-700 rounded-xl transition"
                            >
                                <i className="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                )}

                {/* Choice Modal */}
                {showChoiceModal && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center">
                        <div
                            className="absolute inset-0 bg-black/70 backdrop-blur-sm"
                            onClick={() => setShowChoiceModal(false)}
                        ></div>
                        <div className="relative bg-zinc-900 border border-zinc-800 rounded-xl p-6 mx-4 max-w-sm w-full">
                            <h2 className="text-lg font-semibold text-center mb-2">
                                Create Your Character
                            </h2>
                            <p className="text-zinc-400 text-sm text-center mb-6">
                                Do you want to customize your character yourself?
                            </p>
                            <div className="flex flex-col gap-3">
                                <button
                                    onClick={handleChoiceCustom}
                                    className="w-full py-3 bg-red-600 hover:bg-red-700 rounded-xl font-semibold transition"
                                >
                                    <i className="fa-solid fa-paintbrush mr-2"></i>
                                    I'll create my own
                                </button>
                                <button
                                    onClick={handleChoiceAI}
                                    className="w-full py-3 bg-zinc-800 hover:bg-zinc-700 rounded-xl font-semibold transition"
                                >
                                    <i className="fa-solid fa-wand-magic-sparkles mr-2"></i>
                                    Let AI decide
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {/* Character Drawer - slides from left */}
                <Drawer isOpen={characterDrawerOpen} onClose={() => setCharacterDrawerOpen(false)} title="Character" direction="left">
                    {generated?.character && (
                        <CharacterDrawerContent character={generated.character} />
                    )}
                </Drawer>

                {/* World Drawer - slides from right */}
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

function LoadingCard({ icon, label, messages, duration = 30 }) {
    const [currentMessage, setCurrentMessage] = useState(0);
    const [progress, setProgress] = useState(0);

    // Cycle through messages at random intervals
    useEffect(() => {
        const interval = setInterval(() => {
            setCurrentMessage(prev => (prev + 1) % messages.length);
        }, 2000 + Math.random() * 2000);
        return () => clearInterval(interval);
    }, [messages.length]);

    // Progress bar fills over the given duration
    useEffect(() => {
        const startTime = Date.now();
        const totalMs = duration * 1000;

        const interval = setInterval(() => {
            const elapsed = Date.now() - startTime;
            const pct = Math.min((elapsed / totalMs) * 100, 95);
            setProgress(pct);
        }, 200);

        return () => clearInterval(interval);
    }, [duration]);

    return (
        <div className="flex-1 min-h-0 aspect-square max-w-full bg-zinc-900 border border-zinc-800 rounded-xl p-4 flex flex-col justify-between relative overflow-hidden">
            <div className="flex items-center gap-2 text-zinc-300 text-xs uppercase tracking-wide">
                <i className={`fa-solid ${icon}`}></i>
                <span>{label}</span>
            </div>

            <div className="flex-1 flex items-center justify-center">
                <div className="text-center">
                    <i className="fa-solid fa-spinner fa-spin text-3xl text-red-500 mb-3"></i>
                    <p className="text-zinc-400 text-sm transition-opacity duration-300">
                        {messages[currentMessage]}...
                    </p>
                </div>
            </div>

            {/* Progress bar */}
            <div className="w-full bg-zinc-800 rounded-full h-1.5">
                <div
                    className="bg-red-600 h-1.5 rounded-full transition-all duration-500 ease-out"
                    style={{ width: `${progress}%` }}
                ></div>
            </div>
        </div>
    );
}

function ConceptArtPreview({ appearance, className = '' }) {
    const basePath = '/images/character_compose/male/concept_art';
    const artFile = `outfit_${appearance.outfit}_front_${appearance.hairFront}_back_${appearance.hairBack}`;
    const hairFilter = appearance.hairColor
        ? `hue-rotate(${appearance.hairColor.hue}deg) saturate(${appearance.hairColor.saturate}%) brightness(${appearance.hairColor.brightness}%)`
        : 'none';

    return (
        <>
            <div
                className={`bg-zinc-700 ${className}`}
                style={{
                    backgroundImage: `url(${basePath}/${artFile}.png)`,
                    backgroundSize: 'cover',
                    backgroundPosition: 'center',
                }}
            />
            <div
                className={`${className}`}
                style={{
                    backgroundImage: `url(${basePath}/hair_cutout/${artFile}_hair.png)`,
                    backgroundSize: 'cover',
                    backgroundPosition: 'center',
                    filter: hairFilter,
                }}
            />
        </>
    );
}

function Drawer({ isOpen, onClose, title, children, direction = 'left' }) {
    const [shouldRender, setShouldRender] = useState(false);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        if (isOpen) {
            setShouldRender(true);
            document.body.style.overflow = 'hidden';
            // Small timeout to ensure DOM is ready before animating
            const timer = setTimeout(() => setVisible(true), 10);
            return () => clearTimeout(timer);
        } else {
            setVisible(false);
            document.body.style.overflow = '';
            const timer = setTimeout(() => setShouldRender(false), 300);
            return () => clearTimeout(timer);
        }
    }, [isOpen]);

    useEffect(() => {
        return () => {
            document.body.style.overflow = '';
        };
    }, []);

    if (!shouldRender) return null;

    const isLeft = direction === 'left';

    return (
        <div className="fixed inset-0 z-50">
            {/* Backdrop */}
            <div
                className={`absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300 ${visible ? 'opacity-100' : 'opacity-0'}`}
                onClick={onClose}
            ></div>

            {/* Drawer - 90% width */}
            <div
                className={`absolute inset-y-0 w-[90%] bg-zinc-900 flex flex-col transition-transform duration-300 ease-out
                    ${isLeft ? 'left-0 rounded-r-2xl' : 'right-0 rounded-l-2xl'}
                    ${visible
                    ? 'translate-x-0'
                    : isLeft ? '-translate-x-full' : 'translate-x-full'
                }
                `}
            >
                {/* Header */}
                <div className="flex items-center justify-between p-4 border-b border-zinc-800">
                    {isLeft ? (
                        <>
                            <h2 className="text-lg font-semibold">{title}</h2>
                            <button onClick={onClose} className="p-2 hover:bg-zinc-800 rounded-lg transition">
                                <i className="fa-solid fa-xmark"></i>
                            </button>
                        </>
                    ) : (
                        <>
                            <button onClick={onClose} className="p-2 hover:bg-zinc-800 rounded-lg transition">
                                <i className="fa-solid fa-xmark"></i>
                            </button>
                            <h2 className="text-lg font-semibold">{title}</h2>
                        </>
                    )}
                </div>

                {/* Content */}
                <div className="flex-1 overflow-y-auto p-4">
                    {children}
                </div>
            </div>
        </div>
    );
}

function CharacterDrawerContent({ character }) {
    const stats = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
    const calcMod = (val) => {
        const mod = Math.floor((val - 10) / 2);
        return mod >= 0 ? `+${mod}` : mod;
    };

    return (
        <div className="space-y-6">
            {/* Character Card with Portrait */}
            <div className="bg-zinc-800 rounded-xl overflow-hidden">
                {/* Portrait - cell 1 (neutral expression) */}
                <div className="aspect-square relative overflow-hidden">
                    {character.image_path ? (
                        <MatrixCell
                            imagePath={character.image_path}
                            cell={1}
                            className="w-full h-full"
                        />
                    ) : (
                        <div className="w-full h-full bg-zinc-700 flex items-center justify-center">
                            <i className="fa-solid fa-user text-6xl text-zinc-600"></i>
                        </div>
                    )}
                    <div className="absolute bottom-0 inset-x-0 bg-gradient-to-t from-zinc-900 to-transparent p-4">
                        <h2 className="text-2xl font-bold text-red-500">{character.name}</h2>
                        <p className="text-zinc-400">{character.job || 'Adventurer'}</p>
                    </div>
                </div>

                {/* Basic Info */}
                <div className="p-4">
                    <p className="text-zinc-300 text-sm">{character.info}</p>
                </div>
            </div>

            {/* Item Slots */}
            <div>
                <h3 className="text-zinc-300 text-xs uppercase tracking-wide mb-3">
                    <i className="fa-solid fa-suitcase mr-2"></i>Equipment
                </h3>
                <div className="grid grid-cols-3 gap-3">
                    {[0, 1, 2].map(i => (
                        <div key={i} className="aspect-square bg-zinc-800 border border-zinc-700 border-dashed rounded-lg flex items-center justify-center">
                            <i className="fa-solid fa-plus text-zinc-700"></i>
                        </div>
                    ))}
                </div>
            </div>

            {/* Stats */}
            <div>
                <h3 className="text-zinc-300 text-xs uppercase tracking-wide mb-3">
                    <i className="fa-solid fa-chart-simple mr-2"></i>Attributes
                </h3>
                <div className="grid grid-cols-6 gap-2 text-center">
                    {stats.map(stat => (
                        <div key={stat} className="bg-zinc-800 rounded p-2">
                            <div className="text-[10px] text-zinc-300 uppercase">{stat}</div>
                            <div className="font-bold">{character[stat] || 10}</div>
                            <div className="text-xs text-red-500">{calcMod(character[stat] || 10)}</div>
                        </div>
                    ))}
                </div>
            </div>

            {/* HP & Temps */}
            <div className="flex justify-between text-sm bg-zinc-800 rounded-lg p-3">
                <div>
                    <i className="fa-solid fa-heart text-red-500 mr-1"></i>
                    <span className="font-bold">{character.hp}/{character.max_hp}</span>
                </div>
                <div className="flex gap-4">
                    <span className="text-zinc-300">
                        <i className="fa-solid fa-shuffle mr-1"></i>
                        <span className="text-white">{character.chaotic_temperature}</span>
                    </span>
                    <span className="text-zinc-300">
                        <i className="fa-solid fa-sun mr-1"></i>
                        <span className="text-white">{character.positive_temperature}</span>
                    </span>
                </div>
            </div>

            {/* Attributes */}
            <div className="space-y-3">
                {character.personality && <Attr icon="fa-masks-theater" label="Personality" value={character.personality} />}
                {character.traits && <Attr icon="fa-fingerprint" label="Traits" value={character.traits} />}
                {character.trauma && <Attr icon="fa-ghost" label="Trauma" value={character.trauma} />}
                {character.hobbies && <Attr icon="fa-gamepad" label="Hobbies" value={character.hobbies} />}
                {character.routines && <Attr icon="fa-clock-rotate-left" label="Routines" value={character.routines} />}
                {character.skills && <Attr icon="fa-screwdriver-wrench" label="Skills" value={character.skills} />}
                {character.goals && <Attr icon="fa-bullseye" label="Goals" value={character.goals} />}
                {character.intentions && <Attr icon="fa-compass" label="Intentions" value={character.intentions} />}
                {character.secrets && <Attr icon="fa-user-secret" label="Secrets" value={character.secrets} />}
                {character.limits && <Attr icon="fa-ban" label="Limits" value={character.limits} />}
            </div>
        </div>
    );
}

function WorldDrawerContent({ world, lore, hooks, imagePath }) {
    const loreCount = lore?.length || 0;

    return (
        <div className="space-y-4">
            {/* World Info */}
            <div className="space-y-3">
                {world?.time && <Attr icon="fa-clock" label="Time" value={world.time} />}
                {world?.universe_rules && <Attr icon="fa-scroll" label="Rules" value={world.universe_rules} />}
                {world?.environment_description && <Attr icon="fa-mountain-sun" label="Environment" value={world.environment_description} />}
            </div>

            {/* Hooks */}
            {hooks?.length > 0 && (
                <div>
                    <h3 className="text-zinc-300 text-xs uppercase tracking-wide mb-3">
                        <i className="fa-solid fa-bolt mr-2"></i>Hooks ({hooks.length})
                    </h3>
                    <div className="space-y-3">
                        {hooks.map((hook, i) => (
                            <HookCard key={i} hook={hook} index={loreCount + i + 1} imagePath={imagePath} />
                        ))}
                    </div>
                </div>
            )}

            {/* Lore */}
            {lore?.length > 0 && (
                <div>
                    <h3 className="text-zinc-300 text-xs uppercase tracking-wide mb-3">
                        <i className="fa-solid fa-book-atlas mr-2"></i>Lore ({lore.length})
                    </h3>
                    <div className="space-y-3">
                        {lore.map((item, i) => (
                            <LoreCard key={i} item={item} index={i + 1} imagePath={imagePath} />
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

function HookCard({ hook, index, imagePath }) {
    const [opened, setOpened] = useState(false);
    const [showFull, setShowFull] = useState(false);

    const typeLabels = {
        threat: 'Danger',
        rumor: 'Rumor',
        faction: 'Faction',
        local_color: 'Flavor',
    };

    const typeIcons = {
        threat: 'fa-skull-crossbones',
        rumor: 'fa-ear-listen',
        faction: 'fa-users',
        local_color: 'fa-palette',
    };

    return (
        <div className="bg-zinc-800 rounded-lg overflow-hidden">
            {/* Compact row — tap to open */}
            <div
                className="flex items-center gap-3 p-2 cursor-pointer"
                onClick={() => { setOpened(!opened); if (opened) setShowFull(false); }}
            >
                <div className="w-12 h-12 shrink-0 rounded-lg overflow-hidden bg-zinc-700">
                    {imagePath ? (
                        <Grid2x2Cell imagePath={imagePath} cell={index} className="w-full h-full" />
                    ) : (
                        <div className="w-full h-full flex items-center justify-center">
                            <i className={`fa-solid ${typeIcons[hook.type] || 'fa-bolt'} text-zinc-600`}></i>
                        </div>
                    )}
                </div>
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                        <h4 className="font-semibold text-sm truncate">{hook.name}</h4>
                        <span className="text-[10px] px-1.5 py-0.5 bg-red-900/60 text-red-400 rounded shrink-0">
                            {typeLabels[hook.type] || hook.type}
                        </span>
                    </div>
                </div>
                <i className={`fa-solid fa-chevron-down text-zinc-600 text-xs transition-transform duration-200 ${opened ? 'rotate-180' : ''}`}></i>
            </div>

            {/* Opened — brief + image + stakes + clue */}
            {opened && (
                <div className="px-3 pb-3 space-y-2">
                    {imagePath && (
                        <div className="aspect-video rounded-lg overflow-hidden bg-zinc-700">
                            <Grid2x2Cell imagePath={imagePath} cell={index} className="w-full h-full" />
                        </div>
                    )}
                    <p className="text-zinc-300 text-sm">{hook.brief}</p>
                    <div className="pt-2 mb-5 border-b border-gray-600"></div>
                    <div className="space-y-2 text-sm">
                        {hook.stakes && (
                            <div>
                                <span className="text-zinc-500 text-xs uppercase tracking-wide">
                                    <i className="fa-solid fa-scale-balanced mr-1"></i>Stakes
                                </span>
                                <p className="text-zinc-300 mt-0.5">{hook.stakes}</p>
                            </div>
                        )}
                        {hook.clue && (
                            <div>
                                <span className="text-zinc-500 text-xs uppercase tracking-wide">
                                    <i className="fa-solid fa-magnifying-glass mr-1"></i>Clue
                                </span>
                                <p className="text-zinc-300 mt-0.5">{hook.clue}</p>
                            </div>
                        )}
                    </div>

                    {/* Read more — full situation */}
                    {hook.situation && !showFull && (
                        <button
                            onClick={(e) => { e.stopPropagation(); setShowFull(true); }}
                            className="text-red-500 text-xs hover:text-red-400 transition"
                        >
                            Read more
                        </button>
                    )}
                    {showFull && hook.situation && (
                        <div className="pt-2 border-t border-zinc-700">
                            <p className="text-zinc-400 text-sm">{hook.situation}</p>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

function LoreCard({ item, index, imagePath }) {
    const [expanded, setExpanded] = useState(false);

    return (
        <div
            className="bg-zinc-800 rounded-lg overflow-hidden cursor-pointer"
            onClick={() => setExpanded(!expanded)}
        >
            <div className="aspect-square bg-zinc-700 flex items-center justify-center relative overflow-hidden">
                {imagePath ? (
                    <Grid2x2Cell imagePath={imagePath} cell={index} className="w-full h-full" />
                ) : (
                    <i className="fa-solid fa-image text-3xl text-zinc-600"></i>
                )}
                <span className="absolute top-2 left-2 text-[10px] px-2 py-1 bg-red-900/80 text-red-400 rounded backdrop-blur-sm">
                    {item.type}
                </span>
                {item.occurrence && (
                    <span className="absolute top-2 right-2 text-[10px] px-2 py-1 bg-zinc-900/80 text-zinc-400 rounded backdrop-blur-sm">
                        {item.occurrence}
                    </span>
                )}
            </div>

            <div className="p-3">
                <div className="flex items-center justify-between mb-2">
                    <h4 className="font-semibold">{item.name}</h4>
                    <i className={`fa-solid fa-chevron-down text-zinc-300 transition-transform duration-200 ${expanded ? 'rotate-180' : ''}`}></i>
                </div>

                <p className={`text-zinc-400 text-sm ${expanded ? '' : 'line-clamp-2'}`}>
                    {item.description}
                </p>

                {expanded && (
                    <div className="mt-3 pt-3 border-t border-zinc-700 space-y-2 text-sm">
                        {item.know_how && (
                            <div>
                                <span className="text-zinc-300">
                                    <i className="fa-solid fa-magnifying-glass mr-2"></i>How:
                                </span>
                                <p className="text-zinc-300 mt-1">{item.know_how}</p>
                            </div>
                        )}
                        {item.reason && (
                            <div>
                                <span className="text-zinc-300">
                                    <i className="fa-solid fa-circle-question mr-2"></i>Why:
                                </span>
                                <p className="text-zinc-300 mt-1">{item.reason}</p>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}


/**
 * Display a specific cell from a 2x2 grid image
 * 1=top-left, 2=top-right, 3=bottom-left, 4=bottom-right
 *
 * @param {boolean} cover - If true, uses larger sizing (for card backgrounds)
 */
function Grid2x2Cell({ imagePath, cell, className = '', cover = false }) {
    const positions = {
        1: '0% 0%',
        2: '100% 0%',
        3: '0% 100%',
        4: '100% 100%',
    };

    return (
        <div
            className={`bg-zinc-700 ${className}`}
            style={{
                backgroundImage: `url(/storage/${imagePath})`,
                backgroundSize: cover ? '250% 250%' : '200% 200%',
                backgroundPosition: positions[cell] || '0% 0%',
            }}
        />
    );
}

function Attr({ icon, label, value }) {
    return (
        <div className="bg-zinc-800 rounded-lg p-3">
            <div className="text-zinc-300 text-xs uppercase tracking-wide mb-1">
                <i className={`fa-solid ${icon} mr-2`}></i>
                {label}
            </div>
            <p className="text-zinc-300 text-sm">{value}</p>
        </div>
    );
}

/**
 * Display a specific cell from the character matrix image
 * Grid layout:
 * Row 0: 1, 2, ZXC(2x2)
 * Row 1: 3, 4, ZXC
 * Row 2: 5, 6, 7, 8
 * Row 3: 9, 10, 11, 12
 *
 * @param {boolean} cover - If true, uses cover sizing (for card backgrounds)
 */
function MatrixCell({ imagePath, cell, className = '', cover = false }) {
    const cellPositions = {
        1: { col: 0, row: 0 },
        2: { col: 1, row: 0 },
        3: { col: 0, row: 1 },
        4: { col: 1, row: 1 },
        5: { col: 0, row: 2 },
        6: { col: 1, row: 2 },
        7: { col: 2, row: 2 },
        8: { col: 3, row: 2 },
        9: { col: 0, row: 3 },
        10: { col: 1, row: 3 },
        11: { col: 2, row: 3 },
        12: { col: 3, row: 3 },
        'zxc': { col: 2, row: 0, span: 2 },
    };

    const pos = cellPositions[cell];
    if (!pos) return null;

    const isZxc = cell === 'zxc';

    // For cover mode (card backgrounds), scale up more to fill space
    const bgSize = cover
        ? (isZxc ? '250% 250%' : '500% 500%')
        : (isZxc ? '200% 200%' : '400% 400%');
    const xPos = isZxc ? '100%' : `${pos.col * (100/3)}%`;
    const yPos = isZxc ? '0%' : `${pos.row * (100/3)}%`;

    return (
        <div
            className={`bg-zinc-700 object-fill ${className}`}
            style={{
                backgroundImage: `url(/storage/${imagePath})`,
                backgroundSize: bgSize,
                backgroundPosition: `${xPos} ${yPos}`,
            }}
        />
    );
}
