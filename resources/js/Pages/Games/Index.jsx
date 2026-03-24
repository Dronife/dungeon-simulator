import { useState } from 'react';
import { Link } from '@inertiajs/react';
import Layout from '@/Layouts/Layout';
import GameListItem from './GameListItem';

export default function Index({ games }) {
    const [selectedGameId, setSelectedGameId] = useState(null);

    const handleRemoveGame = async (gameId) => {
        try {
            await fetch(`/game/${gameId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
            });
            window.location.reload();
        } catch (err) {
            console.error('Failed to remove game:', err);
        }
    };

    return (
        <Layout>
            <div className="px-4 py-6 h-[calc(100vh-60px)] flex flex-col overflow-hidden bg-[#121318]">
                <Link
                    href="/game/create"
                    className="w-full py-4 gold-shimmer rounded-lg font-semibold text-lg text-[#3c2f00] transition active:scale-[0.98] shadow-[0_0_15px_rgba(239,200,78,0.2)] text-center block"
                >
                    <i className="fa-solid fa-dice-d20 mr-2"></i>
                    Generate World
                </Link>

                <div className="mt-8 flex-1 overflow-auto">
                    <h2 className="font-sans text-[9px] font-bold uppercase tracking-[0.3em] text-[#a38d7a] mb-4">
                        Your Games
                    </h2>

                    {games.length === 0 ? (
                        <div className="text-center py-12">
                            <i className="fa-solid fa-book-open text-3xl text-[#554434]/30 mb-3 block" />
                            <p className="text-[#a38d7a]/50 text-sm">No games yet</p>
                        </div>
                    ) : (
                        <div className="space-y-2">
                            {games.map(game => (
                                <GameListItem
                                    key={game.id}
                                    game={game}
                                    isSelected={selectedGameId === game.id}
                                    onSelect={() => setSelectedGameId(selectedGameId === game.id ? null : game.id)}
                                    onRemove={handleRemoveGame}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </Layout>
    );
}
