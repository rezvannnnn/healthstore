import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, createSSRApp, Fragment, h } from 'vue';
import type { DefineComponent, VNode } from 'vue';
import Feedback from './components/Feedback.vue';
import StructuredData from './components/StructuredData.vue';

const appName = 'داروخونه';
const siteTitle = 'داروخونه - محصولات سلامت';

createInertiaApp({
    title: (title) => (title ? `${appName} - ${title}` : siteTitle),

    resolve: async (name) => {
        const pages = import.meta.glob<{ default: DefineComponent }>(
            './pages/**/*.vue',
        );

        const page = pages[`./pages/${name}.vue`];

        if (!page) {
            throw new Error(`Inertia page "${name}" was not found.`);
        }

        const component = (await page()).default;
        const withLayout = component as unknown as {
            layout?: (render: typeof h, page: VNode) => VNode;
        };
        withLayout.layout ??= (_render: typeof h, page: VNode) =>
            h(Fragment, [h(Feedback), page]);

        return component;
    },

    setup({ el, App, props, plugin }) {
        (el.hasChildNodes() ? createSSRApp : createApp)({
            render: () => [h(App, props), h(StructuredData)],
        })
            .use(plugin)
            .mount(el);
    },

    progress: {
        color: '#4B5563',
    },
});
