import { Link } from '@inertiajs/react';
import Layout from '../../Layouts/Layout';

export default function SimObjectShow({ object }) {
    const axes = ['type', 'subtype', 'material', 'quality', 'wear', 'rarity'];
    const numeric = ['weight', 'size', 'integrity', 'value'];

    return (
        <Layout>
            <div className="h-full w-full overflow-y-auto p-4">
                <Link href="/simulation" className="text-xs text-zinc-500 hover:text-red-400">
                    ← back to map
                </Link>

                <div className="mt-2 mb-4">
                    <h1 className="text-2xl font-bold text-white">{object.name}</h1>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div className="bg-zinc-900 border border-zinc-800 rounded-xl p-3">
                        <h3 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">Axes</h3>
                        {axes.map((k) => (
                            <div key={k} className="flex justify-between text-xs">
                                <span className="text-zinc-500">{k}</span>
                                <span className="text-zinc-200">{object[k] || '—'}</span>
                            </div>
                        ))}
                    </div>

                    <div className="bg-zinc-900 border border-zinc-800 rounded-xl p-3">
                        <h3 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">Properties</h3>
                        {numeric.map((k) => (
                            <div key={k} className="flex justify-between text-xs">
                                <span className="text-zinc-500">{k}</span>
                                <span className="font-mono text-zinc-200">{object[k]}</span>
                            </div>
                        ))}
                    </div>

                    <div className="bg-zinc-900 border border-zinc-800 rounded-xl p-3">
                        <h3 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">Location</h3>
                        {object.owner && (
                            <Link href={`/simulation/npc/${object.owner.id}`} className="text-xs text-red-400 hover:underline">
                                owned by {object.owner.name}
                            </Link>
                        )}
                        {object.place && (
                            <Link href={`/simulation/place/${object.place.id}`} className="block text-xs text-red-400 hover:underline">
                                in {object.place.name}
                            </Link>
                        )}
                        {object.x != null && (
                            <div className="text-xs text-zinc-500 mt-1">
                                ({object.x},{object.y})
                            </div>
                        )}
                    </div>

                    {object.affordances && (
                        <div className="bg-zinc-900 border border-zinc-800 rounded-xl p-3">
                            <h3 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">Affordances</h3>
                            {Object.entries(object.affordances).map(([need, amt]) => (
                                <div key={need} className="flex justify-between text-xs">
                                    <span className="text-zinc-500">{need}</span>
                                    <span className="text-emerald-400 font-mono">+{amt}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </Layout>
    );
}
