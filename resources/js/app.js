import './bootstrap';
import '../scss/config/default/app.scss';
import '@vueform/slider/themes/default.css';
import '../scss/mermaid.min.css';

import { createApp, h } from 'vue';
import { createInertiaApp, Link, Head } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import BootstrapVueNext from 'bootstrap-vue-next';
import vClickOutside from "click-outside-vue3";
import VueApexCharts from "vue3-apexcharts";
import VueFeather from 'vue-feather';
import VueTheMask from 'vue-the-mask';

import AOS from 'aos';
import 'aos/dist/aos.css';

import store from "./state/store";
import i18n from './i18n'

const savedThemePreferences = (() => {
    try {
        return JSON.parse(localStorage.getItem('sicurezzachiara.theme.current') || '{}');
    } catch (error) {
        localStorage.removeItem('sicurezzachiara.theme.current');
        return {};
    }
})();

const uiDensity = ['comfortable', 'compact'].includes(savedThemePreferences.uiDensity)
    ? savedThemePreferences.uiDensity
    : 'comfortable';

const homePage = ['companies', 'dashboard', 'method'].includes(savedThemePreferences.homePage)
    ? savedThemePreferences.homePage
    : 'companies';

document.documentElement.setAttribute('data-ui-density', uiDensity);
document.documentElement.setAttribute('data-home-page', homePage);

AOS.init({
    easing: 'ease-out-back',
    duration: 1000
});

createInertiaApp({
    title: title => title ? `${title} | SicurezzaChiara` : 'SicurezzaChiara',
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(store)
            .use(i18n)
            .use(ZiggyVue)
            .use(BootstrapVueNext)
            .component('Link', Link)
            .component('Head', Head)
            .use(VueApexCharts)
            .use(VueTheMask)
            .use(vClickOutside)
            .component(VueFeather.type, VueFeather)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
