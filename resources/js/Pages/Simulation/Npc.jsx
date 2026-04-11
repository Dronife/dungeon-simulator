import { Link } from '@inertiajs/react';
import Layout from '../../Layouts/Layout';

export default function SimNpcShow({ npc, recentActions }) {
    const needs = ['hunger', 'thirst', 'rest', 'hygiene', 'safety', 'social_need', 'purpose'];
    const ocean = ['openness', 'conscientiousness', 'extraversion', 'agreeableness', 'neuroticism'];
    const stats = ['str', 'dex', 'con', 'int'];

    return (
        <Layout>
            <div className="h-full w-full overflow-y-auto p-4">
                <Link href="/simulation" className="text-xs text-zinc-500 hover:text-red-400">
                    ← back to map
                </Link>

                <div className="mt-2 mb-4">
                    <h1 className="text-2xl font-bold text-white">{npc.name}</h1>
                    <div className="text-sm text-zinc-400">
                        {npc.race} · {npc.gender} · {npc.age} · {npc.build} · {npc.profession} ({npc.social_class})
                    </div>
                    <div className="text-xs text-zinc-500 mt-1">
                        at ({npc.x},{npc.y}){' '}
                        {npc.place && (
                            <Link href={`/simulation/place/${npc.place.id}`} className="text-red-400 hover:underline">
                                in {npc.place.name}
                            </Link>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <Card title="State">
                        <Row label="mood">{npc.mood}</Row>
                        <Row label="action">{npc.current_action}</Row>
                        <Row label="target">{npc.current_action_target || '—'}</Row>
                        <Row label="hp">{npc.hp}/{npc.max_hp}</Row>
                        <Row label="wealth">{npc.wealth}</Row>
                    </Card>

                    <Card title="Needs">
                        {needs.map((k) => (
                            <Bar key={k} label={k} value={npc[k]} />
                        ))}
                    </Card>

                    <Card title="OCEAN">
                        {ocean.map((k) => (
                            <Row key={k} label={k}>
                                <span className="font-mono">{npc[k]}/10</span>
                            </Row>
                        ))}
                    </Card>

                    <Card title="Stats">
                        {stats.map((k) => (
                            <Row key={k} label={k}>
                                <span className="font-mono">{npc[k]}</span>
                            </Row>
                        ))}
                    </Card>

                    <Card title="Inventory" wide>
                        {npc.inventory?.length === 0 && <div className="text-zinc-600 text-xs">empty</div>}
                        {npc.inventory?.map((o) => (
                            <Link
                                key={o.id}
                                href={`/simulation/object/${o.id}`}
                                className="block text-xs text-zinc-300 hover:text-red-400"
                            >
                                · {o.name} <span className="text-zinc-600">({o.quality} {o.material})</span>
                            </Link>
                        ))}
                    </Card>

                    <Card title="Recent actions" wide>
                        <div className="space-y-1 text-[11px] font-mono">
                            {recentActions.map((a) => (
                                <div key={a.id} className="text-zinc-400">
                                    <span className="text-zinc-600">[t{a.tick}]</span> {a.description}
                                </div>
                            ))}
                            {recentActions.length === 0 && <div className="text-zinc-600">no actions yet</div>}
                        </div>
                    </Card>
                </div>
            </div>
        </Layout>
    );
}

function Card({ title, children, wide }) {
    return (
        <div className={`bg-zinc-900 border border-zinc-800 rounded-xl p-3 ${wide ? 'md:col-span-2' : ''}`}>
            <h3 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">{title}</h3>
            <div className="space-y-1">{children}</div>
        </div>
    );
}

function Row({ label, children }) {
    return (
        <div className="flex justify-between text-xs">
            <span className="text-zinc-500">{label}</span>
            <span className="text-zinc-200">{children}</span>
        </div>
    );
}

function Bar({ label, value }) {
    const color = value < 30 ? 'bg-red-500' : value < 60 ? 'bg-amber-500' : 'bg-emerald-500';
    return (
        <div className="text-xs">
            <div className="flex justify-between text-zinc-500">
                <span>{label}</span>
                <span className="font-mono text-zinc-400">{value}</span>
            </div>
            <div className="h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                <div className={`h-full ${color}`} style={{ width: `${value}%` }} />
            </div>
        </div>
    );
}
