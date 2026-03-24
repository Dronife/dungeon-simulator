import { useState } from 'react';
import { Grid2x2Cell } from './ImageCells';

export default function WorldDrawerContent({ world, lore, hooks, imagePath }) {
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
                    <h3 className="text-[#a38d7a] text-[9px] font-bold uppercase tracking-[0.3em] mb-3">
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
                    <h3 className="text-[#a38d7a] text-[9px] font-bold uppercase tracking-[0.3em] mb-3">
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

function Attr({ icon, label, value }) {
    return (
        <div className="bg-[#292a2f] rounded-lg p-3">
            <div className="text-[#a38d7a] text-[9px] font-bold uppercase tracking-[0.3em] mb-1">
                <i className={`fa-solid ${icon} mr-2`}></i>
                {label}
            </div>
            <p className="text-[#dbc2ad] text-sm">{value}</p>
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
        <div className="bg-[#292a2f] rounded-lg overflow-hidden">
            <div
                className="flex items-center gap-3 p-2 cursor-pointer"
                onClick={() => { setOpened(!opened); if (opened) setShowFull(false); }}
            >
                <div className="w-12 h-12 shrink-0 rounded-lg overflow-hidden bg-[#1e1f25]">
                    {imagePath ? (
                        <Grid2x2Cell imagePath={imagePath} cell={index} className="w-full h-full" />
                    ) : (
                        <div className="w-full h-full flex items-center justify-center">
                            <i className={`fa-solid ${typeIcons[hook.type] || 'fa-bolt'} text-[#554434]`}></i>
                        </div>
                    )}
                </div>
                <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                        <h4 className="font-semibold text-sm text-[#e3e1e9] truncate">{hook.name}</h4>
                        <span className="text-[10px] px-1.5 py-0.5 bg-[#93000a]/30 text-[#ffb4a8] rounded shrink-0">
                            {typeLabels[hook.type] || hook.type}
                        </span>
                    </div>
                </div>
                <i className={`fa-solid fa-chevron-down text-[#554434] text-xs transition-transform duration-200 ${opened ? 'rotate-180' : ''}`}></i>
            </div>

            {opened && (
                <div className="px-3 pb-3 space-y-2">
                    {imagePath && (
                        <div className="aspect-video rounded-lg overflow-hidden bg-[#1e1f25]">
                            <Grid2x2Cell imagePath={imagePath} cell={index} className="w-full h-full" />
                        </div>
                    )}
                    <p className="text-[#dbc2ad] text-sm">{hook.brief}</p>
                    <div className="pt-2 mb-5 border-b border-[#554434]/30"></div>
                    <div className="space-y-2 text-sm">
                        {hook.stakes && (
                            <div>
                                <span className="text-[#a38d7a] text-[9px] font-bold uppercase tracking-[0.3em]">
                                    <i className="fa-solid fa-scale-balanced mr-1"></i>Stakes
                                </span>
                                <p className="text-[#dbc2ad] mt-0.5">{hook.stakes}</p>
                            </div>
                        )}
                        {hook.clue && (
                            <div>
                                <span className="text-[#a38d7a] text-[9px] font-bold uppercase tracking-[0.3em]">
                                    <i className="fa-solid fa-magnifying-glass mr-1"></i>Clue
                                </span>
                                <p className="text-[#dbc2ad] mt-0.5">{hook.clue}</p>
                            </div>
                        )}
                    </div>

                    {hook.situation && !showFull && (
                        <button
                            onClick={(e) => { e.stopPropagation(); setShowFull(true); }}
                            className="text-[#efc84e] text-xs hover:text-[#efc84e]/80 transition"
                        >
                            Read more
                        </button>
                    )}
                    {showFull && hook.situation && (
                        <div className="pt-2 border-t border-[#554434]/30">
                            <p className="text-[#a38d7a] text-sm">{hook.situation}</p>
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
            className="bg-[#292a2f] rounded-lg overflow-hidden cursor-pointer"
            onClick={() => setExpanded(!expanded)}
        >
            <div className="aspect-square bg-[#1e1f25] flex items-center justify-center relative overflow-hidden">
                {imagePath ? (
                    <Grid2x2Cell imagePath={imagePath} cell={index} className="w-full h-full" />
                ) : (
                    <i className="fa-solid fa-image text-3xl text-[#554434]"></i>
                )}
                <span className="absolute top-2 left-2 text-[10px] px-2 py-1 bg-[#93000a]/60 text-[#ffb4a8] rounded backdrop-blur-sm">
                    {item.type}
                </span>
                {item.occurrence && (
                    <span className="absolute top-2 right-2 text-[10px] px-2 py-1 bg-[#121318]/80 text-[#a38d7a] rounded backdrop-blur-sm">
                        {item.occurrence}
                    </span>
                )}
            </div>

            <div className="p-3">
                <div className="flex items-center justify-between mb-2">
                    <h4 className="font-semibold text-[#e3e1e9]">{item.name}</h4>
                    <i className={`fa-solid fa-chevron-down text-[#554434] transition-transform duration-200 ${expanded ? 'rotate-180' : ''}`}></i>
                </div>

                <p className={`text-[#a38d7a] text-sm ${expanded ? '' : 'line-clamp-2'}`}>
                    {item.description}
                </p>

                {expanded && (
                    <div className="mt-3 pt-3 border-t border-[#554434]/30 space-y-2 text-sm">
                        {item.know_how && (
                            <div>
                                <span className="text-[#a38d7a]">
                                    <i className="fa-solid fa-magnifying-glass mr-2"></i>How:
                                </span>
                                <p className="text-[#dbc2ad] mt-1">{item.know_how}</p>
                            </div>
                        )}
                        {item.reason && (
                            <div>
                                <span className="text-[#a38d7a]">
                                    <i className="fa-solid fa-circle-question mr-2"></i>Why:
                                </span>
                                <p className="text-[#dbc2ad] mt-1">{item.reason}</p>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
