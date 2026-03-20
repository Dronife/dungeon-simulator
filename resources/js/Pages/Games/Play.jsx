import { useState, useEffect, useRef } from 'react';

// --- Avatar ---

function Avatar({ characterId, show }) {
    if (!characterId) return null;

    const parts = characterId.split('_');
    const letter = (parts[parts.length - 1] || '?')[0].toUpperCase();

    if (!show) {
        return <div className="w-8 shrink-0" />;
    }

    // Strictly matches your UI palette: zinc-900 card with zinc-800 border
    return (
        <div className="w-8 h-8 shrink-0 rounded-md bg-zinc-900 border border-zinc-800 flex items-center justify-center mt-1">
            <span className="text-zinc-500 font-sans text-xs font-bold">{letter}</span>
        </div>
    );
}

// --- Line components ---

function NarratorLine({ text }) {
    return <p className="text-zinc-300 font-serif text-[1.05rem] leading-relaxed">{text}</p>;
}

function DialogueLine({ speaker, direction, text, characterId, showAvatar }) {
    return (
        <div className="flex gap-4">
            <Avatar characterId={characterId} show={showAvatar} />
            <div className="min-w-0">
                {speaker && showAvatar && (
                    // Using your exact accent color for names: red-500
                    <p className="text-red-500/90 font-sans text-xs font-bold uppercase tracking-wide mb-1">
                        {speaker}
                    </p>
                )}
                {direction && (
                    <p className="text-zinc-500 font-sans text-xs italic mb-1">{direction}</p>
                )}
                <p className="text-white font-serif text-[1.05rem] leading-relaxed">"{text}"</p>
            </div>
        </div>
    );
}

function ActionLine({ speaker, text, characterId, showAvatar }) {
    return (
        <div className="flex gap-4">
            <Avatar characterId={characterId} show={showAvatar} />
            <div className="min-w-0">
                {showAvatar && (
                    // Action names are muted zinc so they don't fight with red dialogue names
                    <p className="text-zinc-500 font-sans text-xs font-bold uppercase tracking-wide mb-1">
                        {speaker}
                    </p>
                )}
                <p className="text-zinc-400 font-serif italic text-[1.05rem] leading-relaxed">{text}</p>
            </div>
        </div>
    );
}

function WhisperLine({ text, characterId, showAvatar }) {
    return (
        <div className="flex gap-4">
            <Avatar characterId={characterId} show={showAvatar} />
            <div className="min-w-0 border-l-2 border-zinc-800 pl-3">
                <p className="text-zinc-500 font-serif italic text-[1.05rem] leading-relaxed">"{text}"</p>
            </div>
        </div>
    );
}

function MechanicLine({ text }) {
    const isSuccess = /success/i.test(text);
    const isFailure = /fail/i.test(text);

    // Kept standard green/red for pass/fail, but deeply muted to match dark theme
    const colorClass = isSuccess ? 'text-emerald-500/60' : isFailure ? 'text-red-500/60' : 'text-zinc-600';

    return (
        <div className="flex justify-center py-4">
            <p className={`${colorClass} font-sans text-xs tracking-widest uppercase font-semibold`}>
                [ {text} ]
            </p>
        </div>
    );
}

function HeadingLine({ text }) {
    return (
        <div className="mt-10 mb-6 border-b border-zinc-800/80 pb-3 flex justify-center">
            <p className="text-zinc-500 font-sans font-semibold text-xs uppercase tracking-[0.2em] text-center">
                {text}
            </p>
        </div>
    );
}

const BANNER_AUTO_CLOSE_MS = 5000;

function SceneBanner({ image, placeName, expanded, onToggle, onImageTap }) {
    const [countdown, setCountdown] = useState(0);

    useEffect(() => {
        if (!expanded) return;
        setCountdown(BANNER_AUTO_CLOSE_MS);
        const interval = setInterval(() => {
            setCountdown(prev => {
                if (prev <= 100) {
                    clearInterval(interval);
                    onToggle();
                    return 0;
                }
                return prev - 100;
            });
        }, 100);
        return () => clearInterval(interval);
    }, [expanded]);

    if (!image) return null;

    const progress = countdown / BANNER_AUTO_CLOSE_MS;
    const circumference = 2 * Math.PI * 8;

    return (
        <div className="shrink-0 relative w-full overflow-hidden">
            {/* Expanded: show the image */}
            <div
                className={`relative w-full transition-all duration-500 ease-in-out ${expanded ? 'h-[120px]' : 'h-0'}`}
            >
                <button onClick={onImageTap} className="absolute inset-0 w-full h-full cursor-pointer">
                    <img
                        src={image}
                        alt=""
                        className="w-full h-full object-cover object-center"
                    />
                </button>
                <div className="absolute bottom-0 left-0 right-0 h-[30px] bg-gradient-to-t from-zinc-950 to-transparent pointer-events-none" />

                {/* Countdown ring — top right */}
                {expanded && (
                    <button onClick={onToggle} className="absolute top-2.5 right-3">
                        <svg width="20" height="20" className="rotate-[-90deg]">
                            <circle cx="10" cy="10" r="8" fill="none" stroke="white" strokeOpacity="0.15" strokeWidth="2" />
                            <circle
                                cx="10" cy="10" r="8" fill="none"
                                stroke="white" strokeOpacity="0.5" strokeWidth="2"
                                strokeDasharray={circumference}
                                strokeDashoffset={circumference * (1 - progress)}
                                strokeLinecap="round"
                                className="transition-none"
                            />
                        </svg>
                    </button>
                )}
            </div>

            {/* Place name tab — always visible, clickable to toggle */}
            <button
                onClick={onToggle}
                className="w-full flex items-center justify-center gap-2 py-1.5 bg-zinc-900/80 border-b border-zinc-800/50"
            >
                <span className="text-zinc-500 font-sans text-[14px] uppercase tracking-[0.15em] font-semibold">
                    {placeName || 'Unknown'}
                </span>
                <i className={`fa-solid fa-chevron-${expanded ? 'up' : 'down'} text-zinc-600 text-[8px]`} />
            </button>
        </div>
    );
}

function SceneLightbox({ image, placeName, onClose }) {
    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm"
            onClick={onClose}
        >
            <div className="relative w-[90vw] h-[70vh]" onClick={e => e.stopPropagation()}>
                <img
                    src={image}
                    alt=""
                    className="w-full h-full object-cover rounded-lg"
                />
                {placeName && (
                    <p className="absolute bottom-4 left-0 right-0 text-center text-zinc-300 font-sans text-xs uppercase tracking-[0.15em] font-semibold drop-shadow-lg">
                        {placeName}
                    </p>
                )}
                <button
                    onClick={onClose}
                    className="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/50 flex items-center justify-center"
                >
                    <i className="fa-solid fa-xmark text-white/70 text-sm" />
                </button>
            </div>
        </div>
    );
}

function ItalicLine({ text }) {
    return <p className="text-zinc-500 font-serif italic text-[1.05rem] leading-relaxed">{text}</p>;
}

function PlayerMessage({ text }) {
    return (
        <div className="flex justify-end my-4">
            {/* Matches your "bg-zinc-900 cards" standard from prompt */}
            <div className="bg-zinc-900 border border-zinc-800 rounded-lg px-4 py-3 max-w-[80%] shadow-sm">
                <p className="text-zinc-300 font-sans text-sm">{text}</p>
            </div>
        </div>
    );
}

function LlmMessage({ content }) {
    let lines = [];
    try {
        lines = typeof content === 'string' ? JSON.parse(content) : content;
    } catch {
        return <p className="text-zinc-300 font-serif">{content}</p>;
    }

    if (!Array.isArray(lines)) {
        return <p className="text-zinc-300 font-serif">{String(content)}</p>;
    }

    let lastCharacterId = null;

    return (
        <div className="max-w-prose mx-auto">
            {lines.map((line, i) => {
                const prev = i > 0 ? lines[i - 1]?.type : null;
                const isHeading = line.type === 'heading';
                const isMechanic = line.type === 'mechanic';
                const typeChanged = prev && prev !== line.type;

                let spacing = 'mt-2';
                if (i === 0) spacing = '';
                else if (isHeading) spacing = '';
                else if (prev === 'heading') spacing = 'mt-4';
                else if (isMechanic || prev === 'mechanic') spacing = 'mt-4';
                else if (typeChanged || line.character_id !== lastCharacterId) spacing = 'mt-6';

                let showAvatar = false;
                if (line.character_id) {
                    showAvatar = line.character_id !== lastCharacterId;
                    lastCharacterId = line.character_id;
                } else {
                    lastCharacterId = null;
                }

                return (
                    <div key={i} className={spacing}>
                        {renderLine(line, showAvatar)}
                    </div>
                );
            })}
        </div>
    );
}

function renderLine(line, showAvatar) {
    switch (line.type) {
        case 'narrator':
            return <NarratorLine text={line.text} />;
        case 'dialogue':
            return <DialogueLine speaker={line.speaker} direction={line.direction} text={line.text} characterId={line.character_id} showAvatar={showAvatar} />;
        case 'action':
            return <ActionLine speaker={line.speaker} text={line.text} characterId={line.character_id} showAvatar={showAvatar} />;
        case 'whisper':
            return <WhisperLine text={line.text} characterId={line.character_id} showAvatar={showAvatar} />;
        case 'mechanic':
            return <MechanicLine text={line.text} />;
        case 'heading':
            return <HeadingLine text={line.text} />;
        case 'italic':
            return <ItalicLine text={line.text} />;
        default:
            return <p className="text-zinc-400 font-serif">{line.text}</p>;
    }
}

function LoadingIndicator() {
    return (
        <div className="flex items-center gap-1.5 py-2">
            <div className="w-1.5 h-1.5 bg-zinc-600 rounded-full animate-bounce [animation-delay:0ms]" />
            <div className="w-1.5 h-1.5 bg-zinc-600 rounded-full animate-bounce [animation-delay:150ms]" />
            <div className="w-1.5 h-1.5 bg-zinc-600 rounded-full animate-bounce [animation-delay:300ms]" />
        </div>
    );
}

export default function Play({ game }) {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [initializing, setInitializing] = useState(false);
    const [bannerExpanded, setBannerExpanded] = useState(true);
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const scrollRef = useRef(null);
    const inputRef = useRef(null);

    const sceneImage = '/images/environment/church.png';

    useEffect(() => {
        if (game.game_chats && game.game_chats.length > 0) {
            setMessages(game.game_chats.map(chat => ({
                type: chat.type,
                content: chat.content,
            })));
        } else {
            fetchInit();
        }
    }, []);

    useEffect(() => {
        if (scrollRef.current) {
            scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
        }
    }, [messages, loading, initializing]);

    async function fetchInit() {
        setInitializing(true);
        try {
            const res = await fetch(`/game/${game.id}/play/init`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
            });
            const data = await res.json();
            setMessages([{ type: 'llm', content: data.content }]);
        } catch (err) {
            console.error('Failed to init game:', err);
        } finally {
            setInitializing(false);
        }
    }

    async function handleSend(e) {
        e.preventDefault();
        const text = input.trim();
        if (!text || loading) return;

        setInput('');
        setMessages(prev => [...prev, { type: 'player', content: text }]);
        setLoading(true);

        try {
            const res = await fetch(`/game/${game.id}/play`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ message: text }),
            });
            const data = await res.json();
            setMessages(prev => [...prev, { type: 'llm', content: data.content }]);
        } catch (err) {
            console.error('Failed to send message:', err);
        } finally {
            setLoading(false);
            inputRef.current?.focus();
        }
    }

    return (
        <div className="h-screen bg-zinc-950 text-white overflow-hidden flex flex-col relative">
            {/* Blurred scene background behind chat */}
            {sceneImage && (
                <div className="absolute inset-0 z-0">
                    <img
                        src={sceneImage}
                        alt=""
                        className="w-full h-full object-cover blur-xl scale-110 "
                    />
                    <div className="absolute inset-0 bg-zinc-950/70" />
                </div>
            )}

            {/* Scene banner */}
            <div className="relative z-10">
                <SceneBanner
                    image={sceneImage}
                    placeName="The Cathedral"
                    expanded={bannerExpanded}
                    onToggle={() => setBannerExpanded(prev => !prev)}
                    onImageTap={() => setLightboxOpen(true)}
                />
            </div>

            {/* Narration log */}
            <div
                ref={scrollRef}
                className="relative z-10 flex-1 overflow-y-auto px-4 py-8 space-y-8 scroll-smooth"
            >
                {initializing && (
                    <div className="flex items-center justify-center h-full">
                        <div className="text-center space-y-3">
                            <i className="fa-solid fa-book-open text-3xl text-zinc-700" />
                            <p className="text-zinc-500 font-sans text-sm">The story begins...</p>
                            <LoadingIndicator />
                        </div>
                    </div>
                )}

                {messages.map((msg, i) => (
                    <div key={i}>
                        {msg.type === 'player' ? (
                            <PlayerMessage text={msg.content} />
                        ) : (
                            <LlmMessage content={msg.content} />
                        )}
                    </div>
                ))}

                {loading && (
                    <div className="max-w-prose mx-auto">
                        <LoadingIndicator />
                    </div>
                )}
            </div>

            {/* Input bar */}
            <form
                onSubmit={handleSend}
                className="relative z-10 shrink-0 border-t border-zinc-800 bg-zinc-950/80 backdrop-blur px-4 py-4"
            >
                <div className="flex gap-3 max-w-4xl mx-auto">
                    <input
                        ref={inputRef}
                        type="text"
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        placeholder="What do you do?"
                        disabled={loading || initializing}
                        className="flex-1 bg-zinc-900/80 border border-zinc-800 rounded-xl px-5 py-3 text-zinc-100 placeholder-zinc-500 focus:outline-none focus:border-red-600/50 focus:ring-1 focus:ring-red-600/30 disabled:opacity-50 font-sans transition-all shadow-inner"
                    />
                    <button
                        type="submit"
                        disabled={loading || initializing || !input.trim()}
                        className="px-6 py-3 bg-red-600 hover:bg-red-500 disabled:bg-zinc-800 disabled:text-zinc-600 rounded-xl transition-colors font-sans font-semibold text-white"
                    >
                        <i className="fa-solid fa-paper-plane" />
                    </button>
                </div>
            </form>

            {/* Lightbox */}
            {lightboxOpen && (
                <SceneLightbox
                    image={sceneImage}
                    placeName="The Cathedral"
                    onClose={() => setLightboxOpen(false)}
                />
            )}
        </div>
    );
}
