import process from 'node:process';
import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from '@vue/server-renderer';
import { createSSRApp, Fragment, h } from 'vue';
import type { DefineComponent, VNode } from 'vue';
import Feedback from './components/Feedback.vue';
import StructuredData from './components/StructuredData.vue';
createServer(
    (page) =>
        createInertiaApp({
            page,
            render: renderToString,
            title: (title) =>
                title ? `داروخونه - ${title}` : 'داروخونه - محصولات سلامت',
            resolve: async (name) => {
                const pages = import.meta.glob<{ default: DefineComponent }>(
                    './pages/**/*.vue',
                );
                const component = pages[`./pages/${name}.vue`];

                if (!component) {
                    throw new Error(`Unknown page: ${name}`);
                }

                const resolved = (await component()).default;
                const withLayout = resolved as unknown as {
                    layout?: (render: typeof h, page: VNode) => VNode;
                };
                withLayout.layout ??= (_render: typeof h, page: VNode) =>
                    h(Fragment, [h(Feedback), page]);

                return resolved;
            },
            setup: ({ App, props, plugin }) =>
                createSSRApp({
                    render: () => [h(App, props), h(StructuredData)],
                }).use(plugin),
        }),
    { port: Number(process.env.INERTIA_SSR_PORT ?? 13715), host: '127.0.0.1' },
);
