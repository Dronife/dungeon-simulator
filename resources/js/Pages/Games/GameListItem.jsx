import { Link } from '@inertiajs/react';

export default function GameListItem({ game, isSelected, onSelect, onRemove }) {
    return (
        <div
            onClick={onSelect}
            className="relative bg-[#1e1f25] border border-[#554434]/20 rounded-lg overflow-hidden cursor-pointer hover:border-[#efc84e]/30 transition-colors"
        >
            <div className="flex items-center">
                <div
                    className={`flex-1 min-w-0 px-4 py-3 transition-transform duration-200 ease-out ${
                        isSelected ? '-translate-x-24' : 'translate-x-0'
                    }`}
                >
                    <h3 className="font-narration text-base text-[#e3e1e9] truncate">{game.name}</h3>
                    <p className="text-[10px] text-[#a38d7a] tracking-widest mt-0.5">
                        #{game.id} · {new Date(game.created_at).toISOString().split('T')[0]}
                    </p>
                </div>

                <div
                    className={`absolute right-0 top-0 bottom-0 flex items-stretch transition-transform duration-200 ease-out ${
                        isSelected ? 'translate-x-0' : 'translate-x-full'
                    }`}
                >
                    <Link
                        href={`/game/${game.id}/play`}
                        onClick={(e) => e.stopPropagation()}
                        className="flex items-center px-4 bg-[#efc84e] text-[#3c2f00] font-bold text-xs uppercase tracking-widest hover:brightness-110 transition"
                    >
                        Continue
                    </Link>
                    <button
                        onClick={(e) => { e.stopPropagation(); onRemove(game.id); }}
                        className="flex items-center px-3 bg-[#93000a]/80 text-[#ffb4ab] hover:bg-[#93000a] transition"
                    >
                        <i className="fa-solid fa-trash text-xs" />
                    </button>
                </div>
            </div>
        </div>
    );
}
