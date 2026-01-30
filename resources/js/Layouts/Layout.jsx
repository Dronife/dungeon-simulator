import { Link } from '@inertiajs/react';

export default function Layout({ children, title }) {
    return (
        <div className="min-h-screen bg-zinc-950 text-white">
            {/* Header */}
            <header className="sticky top-0 z-50 bg-zinc-900/80 backdrop-blur border-b border-zinc-800">
                <div className="px-4 py-3 flex items-center justify-between">
                    <Link href="/game" className="flex items-center gap-2">
                        <span className="text-red-500 text-xl">⚔</span>
                        <span className="font-bold">DND Engine</span>
                    </Link>
                </div>
            </header>

            {/* Main content */}
            <main className="pb-20">
                {children}
            </main>
        </div>
    );
}
