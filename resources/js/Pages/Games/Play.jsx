import { useState, useEffect, useRef } from 'react';
import { LlmMessage, LoadingIndicator } from './NarrationLines';
import BottomNav from './BottomNav';

export default function Play({ game }) {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [initializing, setInitializing] = useState(false);
    const scrollRef = useRef(null);
    const inputRef = useRef(null);

    const sceneImage = '/images/environment/forest.png';
    const placeName = game.world?.environment_description || 'Unknown Location';
    const regionName = game.world?.time || 'Unknown';

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
        <div className="h-screen bg-[#121318] text-[#e3e1e9] font-sans overflow-hidden flex flex-col">
            {/* Hero Environment Section */}
            <section className="relative h-[22vh] w-full shrink-0 overflow-hidden">
                <img
                    src={sceneImage}
                    alt={placeName}
                    className="w-full h-full object-cover brightness-[0.7] contrast-[1.1]"
                />
                {/* Vignette overlays */}
                <div className="absolute inset-0 bg-gradient-to-t from-[#121318] via-transparent to-transparent" />
                <div className="absolute inset-0 bg-gradient-to-b from-[#1c1e29]/90 via-transparent to-transparent" />

                {/* Location label */}
                <div className="absolute bottom-6 left-8">
                    <span className="font-sans text-[9px] font-bold uppercase tracking-[0.3em] text-[#efc84e]/80 mb-1 block">
                        Afternoon
                    </span>
                    <h1 className="font-narration text-3xl md:text-4xl text-[#e3e1e9] tracking-tight leading-none">
                        Forest
                    </h1>
                </div>

                {/* Ambient glow */}
                <div className="absolute top-1/2 right-12 w-24 h-24 bg-[#efc84e]/10 rounded-full blur-3xl" />
            </section>

            {/* Narration Window */}
            <section ref={scrollRef} className="flex-1 overflow-y-auto px-6 md:px-12 py-8">
                <div className="max-w-2xl w-full mx-auto">
                    <div className="relative bg-[#0d0e13] p-6 md:p-10 shadow-2xl border-l border-[#efc84e]/5">
                        {/* Noise texture */}
                        <div className="elysium-texture absolute inset-0 pointer-events-none" />

                        {/* "Current Episode" divider */}
                        <div className="flex items-center gap-4 mb-6 opacity-60">
                            <div className="h-[1px] flex-grow bg-gradient-to-r from-transparent via-[#554434] to-transparent" />
                            <span className="font-sans text-[9px] font-bold uppercase tracking-widest text-[#a38d7a]">
                                Current Episode
                            </span>
                            <div className="h-[1px] flex-grow bg-gradient-to-r from-transparent via-[#554434] to-transparent" />
                        </div>

                        {/* Latest narration only */}
                        <div className="space-y-5 relative z-10">
                            {initializing && (
                                <div className="flex items-center justify-center py-12">
                                    <div className="text-center space-y-3">
                                        <i className="fa-solid fa-book-open text-3xl text-[#a38d7a]/50" />
                                        <p className="text-[#a38d7a] font-sans text-sm">The story begins...</p>
                                        <LoadingIndicator />
                                    </div>
                                </div>
                            )}

                            {(() => {
                                const lastLlm = [...messages].reverse().find(m => m.type === 'llm');
                                if (lastLlm) return <LlmMessage content={lastLlm.content} />;
                                return null;
                            })()}

                            {loading && (
                                <div className="py-2">
                                    <LoadingIndicator />
                                </div>
                            )}
                        </div>

                        {/* Corner decorations */}
                        <div className="absolute -top-1 -right-1 w-12 h-12 border-t border-r border-[#efc84e]/20 pointer-events-none" />
                        <div className="absolute -bottom-1 -left-1 w-12 h-12 border-b border-l border-[#efc84e]/20 pointer-events-none" />
                    </div>
                </div>
            </section>

            {/* Input Bar */}
            <form onSubmit={handleSend} className="shrink-0 px-6 pb-24 pt-4">
                <div className="flex gap-3 max-w-2xl mx-auto">
                    <input
                        ref={inputRef}
                        type="text"
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        placeholder="What do you do?"
                        disabled={loading || initializing}
                        className="flex-1 bg-[#1e1f25] border border-[#554434]/30 rounded-lg px-5 py-3 text-[#e3e1e9] placeholder-[#a38d7a]/50 focus:outline-none focus:border-[#efc84e]/40 focus:ring-1 focus:ring-[#efc84e]/20 disabled:opacity-50 font-sans transition-all"
                    />
                    <button
                        type="submit"
                        disabled={loading || initializing || !input.trim()}
                        className="px-5 py-3 gold-shimmer disabled:opacity-30 rounded-lg transition-all font-sans font-semibold text-[#3c2f00] active:scale-95 shadow-[0_0_15px_rgba(239,200,78,0.2)]"
                    >
                        <i className="fa-solid fa-paper-plane" />
                    </button>
                </div>
            </form>

            {/* Bottom Navigation */}
            <BottomNav active="adventure" gameId={game.id} />
        </div>
    );
}
