import { Link } from '@inertiajs/react';

export default function Layout({ children, title }) {
    return (
        <div className="h-screen bg-zinc-950 text-white overflow-hidden flex flex-col">
            {/* Header */}
            <header className="sticky top-0 z-50 bg-zinc-900/80 backdrop-blur border-b border-zinc-800">
                <div className="px-4 py-3 flex items-center justify-between">
                    <Link href="/game" className="flex items-center gap-2">
                        <i className="fa-solid fa-shield-virus text-red-500"></i>
                        <span className="font-bold">soloRpg</span>
                    </Link>
                </div>
            </header>

            {/* Main content */}
            <main className="flex-1 overflow-hidden">
                {children}
            </main>
        </div>
    );
}
