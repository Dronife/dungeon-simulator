import { useState, useEffect } from 'react';

export default function Drawer({ isOpen, onClose, title, children, direction = 'left' }) {
    const [shouldRender, setShouldRender] = useState(false);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        if (isOpen) {
            setShouldRender(true);
            document.body.style.overflow = 'hidden';
            const timer = setTimeout(() => setVisible(true), 10);
            return () => clearTimeout(timer);
        } else {
            setVisible(false);
            document.body.style.overflow = '';
            const timer = setTimeout(() => setShouldRender(false), 300);
            return () => clearTimeout(timer);
        }
    }, [isOpen]);

    useEffect(() => {
        return () => { document.body.style.overflow = ''; };
    }, []);

    if (!shouldRender) return null;

    const isLeft = direction === 'left';

    return (
        <div className="fixed inset-0 z-50">
            <div
                className={`absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300 ${visible ? 'opacity-100' : 'opacity-0'}`}
                onClick={onClose}
            />

            <div
                className={`absolute inset-y-0 w-[90%] bg-[#1a1b21] flex flex-col transition-transform duration-300 ease-out
                    ${isLeft ? 'left-0 rounded-r-2xl' : 'right-0 rounded-l-2xl'}
                    ${visible ? 'translate-x-0' : isLeft ? '-translate-x-full' : 'translate-x-full'}
                `}
            >
                <div className="flex items-center justify-between p-4 border-b border-[#554434]/20">
                    {isLeft ? (
                        <>
                            <h2 className="font-narration text-lg text-[#e3e1e9] italic">{title}</h2>
                            <button onClick={onClose} className="p-2 hover:bg-[#292a2f] rounded-lg transition">
                                <i className="fa-solid fa-xmark text-[#a38d7a]"></i>
                            </button>
                        </>
                    ) : (
                        <>
                            <button onClick={onClose} className="p-2 hover:bg-[#292a2f] rounded-lg transition">
                                <i className="fa-solid fa-xmark text-[#a38d7a]"></i>
                            </button>
                            <h2 className="font-narration text-lg text-[#e3e1e9] italic">{title}</h2>
                        </>
                    )}
                </div>

                <div className="flex-1 overflow-y-auto p-4">
                    {children}
                </div>
            </div>
        </div>
    );
}
