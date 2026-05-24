import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createPinia } from 'pinia';

const appName = import.meta.env.VITE_APP_NAME || 'BusinessCore ERP';
const corePages = import.meta.glob('./Pages/**/*.vue');
const modulePages = import.meta.glob('../../modules/*/resources/js/Pages/**/*.vue');

const resolveAppPage = async (name) => {
    const corePath = `./Pages/${name}.vue`;
    if (corePages[corePath]) {
        return resolvePageComponent(corePath, corePages);
    }

    const suffix = `/Pages/${name}.vue`;
    const modulePath = Object.keys(modulePages).find((path) => path.endsWith(suffix));
    if (modulePath) {
        return resolvePageComponent(modulePath, modulePages);
    }

    throw new Error(`Inertia page not found: ${name}`);
};

router.on('navigate', (event) => {
    const logoUrl = event.detail.page.props.erpSetting?.app_logo_url;
    if (logoUrl) {
        let link = document.querySelector("link[rel='icon']");
        if (!link) {
            link = document.createElement('link');
            link.rel = 'icon';
            document.head.appendChild(link);
        }
        link.type = '';
        link.href = logoUrl;
    }

    const token = event.detail.page.props.csrf_token;
    if (token) {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute('content', token);
        }
        if (window.axios?.defaults?.headers?.common) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }
    }
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolveAppPage(name),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(createPinia())
            .mount(el);
    },
    progress: {
        color: '#3b82f6',
    },
});
