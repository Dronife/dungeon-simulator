import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { useState, useEffect } from 'react';

function PageTransition({ children }) {
    const [transitioning, setTransitioning] = useState(false);

    useEffect(() => {
        const onBefore = () => setTransitioning(true);
        const onFinish = () => {
            requestAnimationFrame(() => setTransitioning(false));
        };

        router.on('before', onBefore);
        router.on('finish', onFinish);

        return () => {
            router.off('before', onBefore);
            router.off('finish', onFinish);
        };
    }, []);

    return (
        <div
            className="transition-opacity duration-150 ease-out"
            style={{ opacity: transitioning ? 0 : 1 }}
        >
            {children}
        </div>
    );
}

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        return pages[`./Pages/${name}.jsx`];
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <PageTransition>
                <App {...props} />
            </PageTransition>
        );
    },
});
