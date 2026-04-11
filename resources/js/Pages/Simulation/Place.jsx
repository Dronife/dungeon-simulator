import { Link } from '@inertiajs/react';
import Layout from '../../Layouts/Layout';

export default function SimPlaceShow({ place }) {
    const axes = ['type', 'subtype', 'scale', 'condition', 'climate', 'terrain', 'weather', 'time_of_day'];

    return (
        <Layout>
            <div className="h-full w-full overflow-y-auto p-4">
                <Link href="/simulation" className="text-xs text-zinc-500 hover:text-red-400">
                    ← back to map
                </Link>

                <div className="mt-2 mb-4">
                    <h1 className="text-2xl font-bold text-white">{place.name}</h1>
                    <div className="text-xs text-zinc-500">
                        ({place.x},{place.y}) — {place.width}×{place.height}
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div className="bg-zinc-900 border border-zinc-800 rounded-xl p-3">
                        <h3 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">Axes</h3>
                        {axes.map((k) => (
                            <div key={k} className="flex justify-between text-xs">
                                <span className="text-zinc-500">{k}</span>
                                <span className="text-zinc-200">{place[k]}</span>
                            </div>
                        ))}
                        <div className="flex justify-between text-xs">
                            <span className="text-zinc-500">danger</span>
                            <span className="font-mono text-zinc-200">{place.danger_level}</span>
                        </div>
                        <div className="flex justify-between text-xs">
                            <span className="text-zinc-500">prosperity</span>
                            <span className="font-mono text-zinc-200">{place.prosperity}</span>
                        </div>
                    </div>

                    <div className="bg-zinc-900 border border-zinc-800 rounded-xl p-3">
                        <h3 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">NPCs here</h3>
                        {place.npcs?.length === 0 && <div className="text-zinc-600 text-xs">nobody here</div>}
                        {place.npcs?.map((n) => (
                            <Link
                                key={n.id}
                                href={`/simulation/npc/${n.id}`}
                                className="block text-xs text-zinc-300 hover:text-red-400"
                            >
                                · {n.name} <span className="text-zinc-600">({n.profession})</span>
                            </Link>
                        ))}
                    </div>

                    <div className="bg-zinc-900 border border-zinc-800 rounded-xl p-3 md:col-span-2">
                        <h3 className="text-xs uppercase tracking-wide text-zinc-500 mb-2">Objects here</h3>
                        {place.objects?.length === 0 && <div className="text-zinc-600 text-xs">empty</div>}
                        {place.objects?.map((o) => (
                            <Link
                                key={o.id}
                                href={`/simulation/object/${o.id}`}
                                className="block text-xs text-zinc-300 hover:text-red-400"
                            >
                                · {o.name} <span className="text-zinc-600">({o.quality} {o.material})</span>
                            </Link>
                        ))}
                    </div>
                </div>
            </div>
        </Layout>
    );
}
