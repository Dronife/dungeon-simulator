const NAV_ITEMS = [
    { key: 'adventure', icon: 'fa-compass', label: 'Adventure', href: (gameId) => `/game/${gameId}/play` },
    { key: 'inventory', icon: 'fa-suitcase', label: 'Inventory', href: (gameId) => `/game/${gameId}/inventory` },
    { key: 'world', icon: 'fa-map', label: 'World', href: () => '#' },
    { key: 'skills', icon: 'fa-wand-sparkles', label: 'Skills', href: () => '#' },
];

export default function BottomNav({ active, gameId }) {
    return (
        <nav className="fixed bottom-0 w-full z-50 flex justify-around items-center px-4 pb-6 pt-2 bg-[#121318]/90 backdrop-blur-xl border-t border-[#efc84e]/15 shadow-[0_-10px_30px_rgba(0,0,0,0.8)]">
            {NAV_ITEMS.map((item) => {
                const isActive = item.key === active;

                if (isActive) {
                    return (
                        <a
                            key={item.key}
                            href={item.href(gameId)}
                            className="flex flex-col items-center justify-center text-[#efc84e] bg-[#efc84e]/10 rounded-xl py-1 px-4 ring-1 ring-[#efc84e]/30"
                        >
                            <i className={`fa-solid ${item.icon} mb-1`} />
                            <span className="font-sans text-[10px] font-bold uppercase tracking-widest">{item.label}</span>
                        </a>
                    );
                }

                return (
                    <a
                        key={item.key}
                        href={item.href(gameId)}
                        className="flex items-center justify-center text-[#e3e1e9]/60 hover:text-[#e3e1e9] hover:bg-[#34343a] rounded-xl w-10 h-10 transition-all duration-200"
                    >
                        <i className={`fa-solid ${item.icon}`} />
                    </a>
                );
            })}
        </nav>
    );
}
