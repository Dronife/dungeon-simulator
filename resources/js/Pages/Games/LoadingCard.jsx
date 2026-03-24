import { useState, useEffect } from 'react';

export default function LoadingCard({ icon, label, messages, duration = 30 }) {
    const [currentMessage, setCurrentMessage] = useState(0);
    const [progress, setProgress] = useState(0);

    useEffect(() => {
        const interval = setInterval(() => {
            setCurrentMessage(prev => (prev + 1) % messages.length);
        }, 2000 + Math.random() * 2000);
        return () => clearInterval(interval);
    }, [messages.length]);

    useEffect(() => {
        const startTime = Date.now();
        const totalMs = duration * 1000;

        const interval = setInterval(() => {
            const elapsed = Date.now() - startTime;
            const pct = Math.min((elapsed / totalMs) * 100, 95);
            setProgress(pct);
        }, 200);

        return () => clearInterval(interval);
    }, [duration]);

    return (
        <div className="flex-1 min-h-0 aspect-square max-w-full bg-[#1e1f25] border border-[#554434]/20 rounded-lg p-4 flex flex-col justify-between relative overflow-hidden">
            <div className="flex items-center gap-2 text-[#a38d7a] text-[9px] font-bold uppercase tracking-[0.3em]">
                <i className={`fa-solid ${icon}`}></i>
                <span>{label}</span>
            </div>

            <div className="flex-1 flex items-center justify-center">
                <div className="text-center">
                    <i className="fa-solid fa-spinner fa-spin text-3xl text-[#efc84e] mb-3"></i>
                    <p className="text-[#a38d7a] text-sm transition-opacity duration-300">
                        {messages[currentMessage]}...
                    </p>
                </div>
            </div>

            <div className="w-full bg-[#292a2f] rounded-full h-1.5">
                <div
                    className="bg-[#efc84e] h-1.5 rounded-full transition-all duration-500 ease-out"
                    style={{ width: `${progress}%` }}
                ></div>
            </div>
        </div>
    );
}
