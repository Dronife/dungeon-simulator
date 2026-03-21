import { useState, useEffect, useRef } from 'react';
import { PlayerMessage, LlmMessage, LoadingIndicator } from './NarrationLines';

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

export default function Play({ game, environment }) {
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
