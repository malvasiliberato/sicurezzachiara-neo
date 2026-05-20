<script>
import { Link } from '@inertiajs/vue3';
import { layoutComputed } from "@/state/helpers";
import simplebar from "simplebar-vue";

export default {
  components: {
    simplebar,
    Link
  },
  data() {
    return {
      settings: {
        minScrollbarLength: 60,
      },
    };
  },
  computed: {
    ...layoutComputed,
    layoutType: {
      get() {
        return this.$store ? this.$store.state.layout.layoutType : {} || {};
      },
    },
  },
  mounted() {
    this.initActiveMenu();
    this.onRoutechange();
  },
  methods: {
    onRoutechange() {
      setTimeout(() => {
        const currentPath = window.location.pathname;
        if (document.querySelector("#navbar-nav")) {
          const currentPosition = document.querySelector("#navbar-nav").querySelector('[href="' + currentPath + '"]')?.offsetTop;
          if (currentPosition > document.documentElement.clientHeight) {
            document.querySelector("#scrollbar .simplebar-content-wrapper")
              ? document.querySelector("#scrollbar .simplebar-content-wrapper").scrollTop = currentPosition + 300
              : '';
          }
        }
      }, 500);
    },
    initActiveMenu() {
      setTimeout(() => {
        const currentPath = window.location.pathname;
        if (document.querySelector("#navbar-nav")) {
          const activeLink = document.querySelector("#navbar-nav").querySelector('[href="' + currentPath + '"]');
          if (activeLink) {
            activeLink.classList.add("active");
          }
        }
      }, 0);
    },
  },
};
</script>

<template>
  <BContainer fluid>
    <div id="two-column-menu"></div>

    <template v-if="layoutType === 'vertical' || layoutType === 'semibox'">
      <ul class="navbar-nav h-100" id="navbar-nav">
        <li class="menu-title">
          <span>SicurezzaChiara</span>
        </li>
        <li class="menu-title mt-2">
          <span>Aziende</span>
        </li>
        <li class="nav-item">
          <Link class="nav-link menu-link" :href="route('companies.index')">
          <i class="ri-building-4-line"></i>
          <span>Portfolio aziende</span>
          </Link>
        </li>
        <li class="nav-item">
          <Link class="nav-link menu-link" :href="route('companies.create')">
          <i class="ri-add-circle-line"></i>
          <span>Nuova azienda</span>
          </Link>
        </li>
        <li class="menu-title mt-2">
          <span>Metodo</span>
        </li>
        <li class="nav-item">
          <Link class="nav-link menu-link" :href="route('sicurezzachiara.method')">
          <i class="ri-function-line"></i>
          <span>Panoramica metodo</span>
          </Link>
        </li>
        <li class="nav-item">
          <Link class="nav-link menu-link" :href="route('risk-catalog.index')">
          <i class="ri-alert-line"></i>
          <span>Rischi</span>
          </Link>
        </li>
        <li class="nav-item">
          <Link class="nav-link menu-link" :href="route('job-roles.index')">
          <i class="ri-briefcase-4-line"></i>
          <span>Mansioni</span>
          </Link>
        </li>
        <li class="nav-item">
          <Link class="nav-link menu-link" :href="route('equipment-types.index')">
          <i class="ri-settings-3-line"></i>
          <span>Tipologie macchinario</span>
          </Link>
        </li>
        <li class="nav-item">
          <Link class="nav-link menu-link" :href="route('workplace-types.index')">
          <i class="ri-map-pin-2-line"></i>
          <span>Tipologie luogo</span>
          </Link>
        </li>
        <li class="menu-title mt-2">
          <span>Workspace</span>
        </li>
        <li class="nav-item">
          <Link class="nav-link menu-link" href="/">
          <i class="ri-dashboard-2-line"></i>
          <span>Dashboard</span>
          </Link>
        </li>
        <li class="menu-title mt-2">
          <span>Supporto</span>
        </li>
        <li class="nav-item">
          <Link class="nav-link menu-link" href="/sicurezzachiara/ui-reference">
          <i class="ri-palette-line"></i>
          <span>UI Reference</span>
          </Link>
        </li>
      </ul>
    </template>
  </BContainer>
</template>
