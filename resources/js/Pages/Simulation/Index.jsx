import { Link, router } from '@inertiajs/react';
import { useMemo } from 'react';
import Layout from '../../Layouts/Layout';

const CELL = 12;

const PLACE_COLOR = {
    tavern: 'bg-amber-900/40 border-amber-700/60',
    forge: 'bg-orange-900/40 border-orange-700/60',
    town_square: 'bg-stone-700/40 border-stone-600/60',
    shrine: 'bg-indigo-900/40 border-indigo-700/60',
    chapel: 'bg-indigo-900/30 border-indigo-800/50',
    well: 'bg-cyan-900/50 border-cyan-700/70',
    home: 'bg-zinc-800/50 border-zinc-700/60',
    hovel: 'bg-zinc-900/60 border-zinc-700/40',
    farm: 'bg-lime-900/30 border-lime-800/50',
    orchard: 'bg-green-900/30 border-green-800/50',
    garden: 'bg-emerald-900/30 border-emerald-800/50',
    bakery: 'bg-yellow-900/30 border-yellow-800/50',
    butchery: 'bg-red-900/30 border-red-800/50',
    shop: 'bg-sky-900/30 border-sky-800/50',
    market_stall: 'bg-sky-900/20 border-sky-800/40',
    stable: 'bg-amber-950/40 border-amber-900/50',
    warehouse: 'bg-stone-800/40 border-stone-700/50',
    apothecary: 'bg-fuchsia-900/30 border-fuchsia-800/50',
    barracks: 'bg-slate-800/40 border-slate-700/50',
};

export default function SimulationIndex({
    state,
    gridSize,
    places,
    mapNpcs,
    featuredNpcs,
    objects,
    recentActions,
    stats,
}) {
    const cellNpcs = useMemo(() => {
        const map = {};
        mapNpcs.forEach((n) => {
            const k = `${n.x},${n.y}`;
            (map[k] ||= []).push(n);
        });
        return map;
    }, [mapNpcs]);

    const onTick = () => router.post('/simulation/tick', {}, { preserveScroll: true });
    const onReset = () => router.post('/simulation/reset', {}, { preserveScroll: true });

    return (
        <Layout>
            <div className="h-full w-full flex flex-col lg:flex-row gap-3 p-3 overflow-hidden">
                {/* Map */}
                <div className="flex-1 min-h-0 flex flex-col">
                    <div className="flex items-center justify-between mb-2 shrink-0">
                        <div className="flex items-center gap-3 flex-wrap">
                            <h1 className="text-sm font-bold uppercase tracking-wide text-zinc-300">
                                Tick {state.tick}
                            </h1>
                            <span className="text-xs text-zinc-500">{state.time_of_day}</span>
                            <span className="text-xs text-zinc-500">· {state.weather}</span>
                            <span className="text-xs text-zinc-500">· {stats.total} NPCs</span>
                        </div>
                        <div className="flex gap-2">
                            <button
                                onClick={onReset}
                                className="px-3 py-1.5 text-xs uppercase tracking-wide rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300"
                            >
                                Reset
                            </button>
                            <button
                                onClick={onTick}
                                className="px-4 py-1.5 text-xs font-bold uppercase tracking-wide rounded-lg bg-red-600 hover:bg-red-500 text-white"
                            >
                                Next Turn →
                            </button>
                        </div>
                    </div>

                    <div className="flex-1 min-h-0 overflow-auto bg-zinc-900 rounded-xl border border-zinc-800 p-2">
                        <div
                            className="relative"
                            style={{
                                width: gridSize * CELL,
                                height: gridSize * CELL,
                                backgroundImage:
                                    'linear-gradient(to right, #1f1f22 1px, transparent 1px), linear-gradient(to bottom, #1f1f22 1px, transparent 1px)',
                                backgroundSize: `${CELL}px ${CELL}px`,
                            }}
                        >
                            {places.map((p) => (
                                <Link
                                    key={`p-${p.id}`}
                                    href={`/simulation/place/${p.id}`}
                                    className={`absolute rounded border ${
                                        PLACE_COLOR[p.subtype] || 'bg-zinc-800/40 border-zinc-700/50'
                                    } hover:brightness-150`}
                                    style={{
                                        left: p.x * CELL,
                                        top: p.y * CELL,
                                        width: p.width * CELL,
                                        height: p.height * CELL,
                                    }}
                                    title={p.name}
                                />
                            ))}

                            {objects.map((o) => (
                                <Link
                                    key={`o-${o.id}`}
                                    href={`/simulation/object/${o.id}`}
                                    className="absolute flex items-center justify-center text-amber-500/70 hover:text-amber-300"
                                    style={{
                                        left: o.x * CELL,
                                        top: o.y * CELL,
                                        width: CELL,
                                        height: CELL,
                                        fontSize: 7,
                                    }}
                                    title={o.name}
                                >
                                    <i className="fa-solid fa-cube" />
                                </Link>
                            ))}

                            {Object.entries(cellNpcs).map(([k, list]) => {
                                const [x, y] = k.split(',').map(Number);
                                const single = list.length === 1;
                                const color =
                                    list.length > 20
                                        ? 'bg-yellow-500 text-black'
                                        : list.length > 5
                                        ? 'bg-orange-500 text-white'
                                        : 'bg-red-600 text-white';
                                return (
                                    <Link
                                        key={`n-${k}`}
                                        href={`/simulation/npc/${list[0].id}`}
                                        className={`absolute flex items-center justify-center rounded-full border border-white/40 hover:brightness-125 z-10 font-bold ${color}`}
                                        style={{
                                            left: x * CELL + 1,
                                            top: y * CELL + 1,
                                            width: CELL - 2,
                                            height: CELL - 2,
                                            fontSize: 7,
                                        }}
                                        title={
                                            single
                                                ? `${list[0].name} (${list[0].current_action})`
                                                : `${list.length} here: ${list.slice(0, 5).map((n) => n.name).join(', ')}${list.length > 5 ? '…' : ''}`
                                        }
                                    >
                                        {single ? list[0].name[0] : list.length}
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Side panel */}
                <div className="lg:w-80 flex flex-col gap-3 min-h-0 shrink-0">
                    <div className="bg-zinc-900 rounded-xl border border-zinc-800 p-3 shrink-0">
                        <h2 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">Averages</h2>
                        <div className="grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                            <AvgRow label="hunger" value={stats.avg_hunger} />
                            <AvgRow label="thirst" value={stats.avg_thirst} />
                            <AvgRow label="rest" value={stats.avg_rest} />
                            <AvgRow label="purpose" value={stats.avg_purpose} />
                        </div>
                    </div>

                    <div className="bg-zinc-900 rounded-xl border border-zinc-800 p-3 shrink-0">
                        <h2 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">Economy</h2>
                        <div className="grid grid-cols-2 gap-x-3 gap-y-1 text-xs font-mono">
                            <div className="flex justify-between">
                                <span className="text-zinc-500">avg coin</span>
                                <span className="text-amber-400">{stats.avg_wealth}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-zinc-500">total coin</span>
                                <span className="text-amber-400">{stats.total_coin}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-zinc-500">for sale</span>
                                <span className="text-sky-400">{stats.total_for_sale}</span>
                            </div>
                        </div>
                    </div>

                    <div className="bg-zinc-900 rounded-xl border border-zinc-800 p-3 shrink-0">
                        <h2 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">Archetype</h2>
                        <div className="flex flex-wrap gap-1 text-[10px]">
                            {Object.entries(stats.by_archetype).map(([k, v]) => (
                                <span key={k} className="bg-zinc-800 rounded px-1.5 py-0.5 text-zinc-400">
                                    {k.replace(/_/g, ' ')} <span className="text-red-400 font-mono">{v}</span>
                                </span>
                            ))}
                        </div>
                    </div>

                    <div className="bg-zinc-900 rounded-xl border border-zinc-800 p-3 shrink-0">
                        <h2 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">Mood</h2>
                        <div className="flex flex-wrap gap-1 text-[10px]">
                            {Object.entries(stats.by_mood).map(([k, v]) => (
                                <span key={k} className="bg-zinc-800 rounded px-1.5 py-0.5 text-zinc-400">
                                    {k} <span className="text-red-400 font-mono">{v}</span>
                                </span>
                            ))}
                        </div>
                    </div>

                    <div className="bg-zinc-900 rounded-xl border border-zinc-800 p-3 shrink-0">
                        <h2 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">Action</h2>
                        <div className="flex flex-wrap gap-1 text-[10px]">
                            {Object.entries(stats.by_action).map(([k, v]) => (
                                <span key={k} className="bg-zinc-800 rounded px-1.5 py-0.5 text-zinc-400">
                                    {k} <span className="text-red-400 font-mono">{v}</span>
                                </span>
                            ))}
                        </div>
                    </div>

                    <div className="bg-zinc-900 rounded-xl border border-zinc-800 p-3 flex-1 min-h-0 overflow-y-auto">
                        <h2 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">
                            Most strained (top 30)
                        </h2>
                        <div className="space-y-1">
                            {featuredNpcs.map((n) => (
                                <Link
                                    key={n.id}
                                    href={`/simulation/npc/${n.id}`}
                                    className="block bg-zinc-800/60 hover:bg-zinc-800 rounded-lg p-1.5 border border-transparent hover:border-red-500/50"
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="text-[11px] font-bold text-white truncate">
                                            {n.name}
                                        </span>
                                        <span className="text-[9px] text-amber-400 font-mono ml-1 shrink-0">
                                            {n.wealth}c
                                        </span>
                                    </div>
                                    <div className="text-[9px] text-zinc-400 truncate">
                                        {n.profession} · {n.current_action}
                                        {n.current_action_target && ` → ${n.current_action_target}`}
                                    </div>
                                    <NeedBars npc={n} />
                                </Link>
                            ))}
                        </div>
                    </div>

                    <div className="bg-zinc-900 rounded-xl border border-zinc-800 p-3 max-h-40 overflow-y-auto shrink-0">
                        <h2 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">Last tick</h2>
                        <div className="space-y-0.5 text-[10px] text-zinc-400 font-mono">
                            {recentActions.length === 0 && <div className="text-zinc-600">no events yet</div>}
                            {recentActions.map((a) => (
                                <div key={a.id} className="truncate">
                                    <span className="text-red-400">{a.source?.name}</span> {a.description}
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </Layout>
    );
}

function AvgRow({ label, value }) {
    const color = value < 30 ? 'text-red-400' : value < 60 ? 'text-amber-400' : 'text-emerald-400';
    return (
        <div className="flex justify-between">
            <span className="text-zinc-500">{label}</span>
            <span className={`font-mono ${color}`}>{value}</span>
        </div>
    );
}

function NeedBars({ npc }) {
    const needs = ['hunger', 'thirst', 'rest', 'social_need', 'purpose'];
    return (
        <div className="grid grid-cols-5 gap-0.5 mt-1">
            {needs.map((k) => {
                const v = npc[k];
                const color = v < 30 ? 'bg-red-500' : v < 60 ? 'bg-amber-500' : 'bg-emerald-500';
                return (
                    <div key={k} className="h-0.5 bg-zinc-700 rounded-full overflow-hidden" title={`${k}: ${v}`}>
                        <div className={`h-full ${color}`} style={{ width: `${v}%` }} />
                    </div>
                );
            })}
        </div>
    );
}
