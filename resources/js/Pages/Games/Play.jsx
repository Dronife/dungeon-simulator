import { useState, useEffect, useRef } from 'react';
import Layout from '@/Layouts/Layout';

// --- Line components ---

function NarratorLine({ text }) {
    return <p className="text-zinc-300 leading-relaxed">{text}</p>;
}

function DialogueLine({ speaker, text }) {
    return (
        <div>
            {speaker && (
                <p className="text-red-500/80 text-xs font-semibold uppercase tracking-wide mb-0.5">{speaker}</p>
            )}
            <p className="text-zinc-100 font-medium leading-relaxed">"{text}"</p>
        </div>
    );
}

function ActionLine({ speaker, text }) {
    return (
        <div>
            <p className="text-red-500/70 text-xs font-semibold uppercase tracking-wide mb-0.5">{speaker}</p>
            <p className="text-zinc-400 italic leading-relaxed">{text}</p>
        </div>
    );
}

function MechanicLine({ text }) {
    const isSuccess = /success/i.test(text);
    const isFailure = /fail/i.test(text);
    const colorClass = isSuccess ? 'text-emerald-500/60' : isFailure ? 'text-red-500/60' : 'text-zinc-600';

    return (
        <p className={`${colorClass} font-mono text-xs tracking-wide uppercase`}>
            &#x276C; {text} &#x276D;
        </p>
    );
}

function HeadingLine({ text }) {
    return (
        <div className="mt-10 mb-1">
            <p className="text-zinc-500 font-semibold text-s uppercase tracking-[0.35em]">{text}</p>
            <hr className="my-2"></hr>
        </div>
    );
}

function ItalicLine({ text }) {
    return <p className="text-zinc-500 italic leading-relaxed">{text}</p>;
}

function PlayerMessage({ text }) {
    return (
        <div className="flex justify-end">
            <div className="bg-zinc-800 rounded-xl px-4 py-2.5 max-w-[85%]">
                <p className="text-zinc-100">{text}</p>
            </div>
        </div>
    );
}

function LlmMessage({ content }) {
    let lines = [];
    try {
        lines = typeof content === 'string' ? JSON.parse(content) : content;
    } catch {
        return <p className="text-zinc-300">{content}</p>;
    }

    if (!Array.isArray(lines)) {
        return <p className="text-zinc-300">{String(content)}</p>;
    }

    return (
        <div className="max-w-prose">
            {lines.map((line, i) => {
                const prev = i > 0 ? lines[i - 1]?.type : null;
                const isHeading = line.type === 'heading';
                const isMechanic = line.type === 'mechanic';
                const typeChanged = prev && prev !== line.type;

                // Variable spacing: tight within same type, wider between different types
                let spacing = 'mt-1';
                if (i === 0) spacing = '';
                else if (isHeading) spacing = ''; // heading handles its own mt-6
                else if (prev === 'heading') spacing = 'mt-2';
                else if (isMechanic || prev === 'mechanic') spacing = 'mt-2';
                else if (typeChanged) spacing = 'mt-3';

                return (
                    <div key={i} className={spacing}>
                        {renderLine(line)}
                    </div>
                );
            })}
        </div>
    );
}

function renderLine(line) {
    switch (line.type) {
        case 'narrator':
            return <NarratorLine text={line.text} />;
        case 'dialogue':
            return <DialogueLine speaker={line.speaker} text={line.text} />;
        case 'action':
            return <ActionLine speaker={line.speaker} text={line.text} />;
        case 'mechanic':
            return <MechanicLine text={line.text} />;
        case 'heading':
            return <HeadingLine text={line.text} />;
        case 'italic':
            return <ItalicLine text={line.text} />;
        default:
            return <p className="text-zinc-400">{line.text}</p>;
    }
}

function LoadingIndicator() {
    return (
        <div className="flex items-center gap-1.5 py-2">
            <div className="w-1.5 h-1.5 bg-zinc-500 rounded-full animate-bounce [animation-delay:0ms]" />
            <div className="w-1.5 h-1.5 bg-zinc-500 rounded-full animate-bounce [animation-delay:150ms]" />
            <div className="w-1.5 h-1.5 bg-zinc-500 rounded-full animate-bounce [animation-delay:300ms]" />
        </div>
    );
}

export default function Play({ game }) {
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [initializing, setInitializing] = useState(false);
    const scrollRef = useRef(null);
    const inputRef = useRef(null);

    // Initialize from props or fetch opening narration
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

    // Auto-scroll to bottom on new messages
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
        <Layout>
            <div className="flex flex-col h-full">
                {/* Narration log */}
                <div
                    ref={scrollRef}
                    className="flex-1 overflow-y-auto px-4 py-4 space-y-4 font-narration text-[16px]"
                >
                    {initializing && (
                        <div className="flex items-center justify-center h-full">
                            <div className="text-center space-y-3">
                                <i className="fa-solid fa-book-open text-3xl text-zinc-600" />
                                <p className="text-zinc-500 text-sm">The story begins...</p>
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

                    {loading && <LoadingIndicator />}
                </div>

                {/* Input bar */}
                <form
                    onSubmit={handleSend}
                    className="shrink-0 border-t border-zinc-800 bg-zinc-900/80 backdrop-blur px-4 py-3"
                >
                    <div className="flex gap-2">
                        <input
                            ref={inputRef}
                            type="text"
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            placeholder="What do you do?"
                            disabled={loading || initializing}
                            className="flex-1 bg-zinc-800 border border-zinc-700 rounded-xl px-4 py-2.5 text-white placeholder-zinc-500 focus:outline-none focus:border-red-500/50 disabled:opacity-50"
                        />
                        <button
                            type="submit"
                            disabled={loading || initializing || !input.trim()}
                            className="px-4 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-zinc-700 disabled:text-zinc-500 rounded-xl transition font-semibold"
                        >
                            <i className="fa-solid fa-paper-plane" />
                        </button>
                    </div>
                </form>
            </div>
        </Layout>
    );
}
