import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),

    resolve: (name) => {
        const pages = import.meta.glob<DefineComponent>(
            './pages/**/*.vue',
            {
                eager: true,
            },
        );

        const page = pages[`./pages/${name}.vue`];

        if (!page) {
            throw new Error(`Inertia page "${name}" was not found.`);
        }

        return page;
    },

    setup({ el, App, props, plugin }) {
        createApp({
            render: () => h(App, props),
        })
            .use(plugin)
            .mount(el);
    },

    progress: {
        color: '#4B5563',
    },
});