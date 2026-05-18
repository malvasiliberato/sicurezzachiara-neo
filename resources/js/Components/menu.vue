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
        <li class="nav-item">
          <Link class="nav-link menu-link" href="/">
          <i class="ri-dashboard-2-line"></i>
          <span>Dashboard</span>
          </Link>
        </li>
        <li class="menu-title mt-2">
          <span>Area 1 - Gestione azienda</span>
        </li>
        <li class="nav-item">
          <Link class="nav-link menu-link" :href="route('companies.index')">
          <i class="ri-building-4-line"></i>
          <span>Aziende</span>
          </Link>
        </li>
        <li class="menu-title mt-2">
          <span>Area 2 - Metodo di lavoro</span>
        </li>
        <li class="nav-item">
          <Link class="nav-link menu-link" :href="route('job-roles.index')">
          <i class="ri-briefcase-4-line"></i>
          <span>Mansioni</span>
          </Link>
        </li>
        <li class="nav-item">
          <Link class="nav-link menu-link" :href="route('risk-catalog.index')">
          <i class="ri-alert-line"></i>
          <span>Catalogo rischi</span>
          </Link>
        </li>
        <li class="nav-item">
          <Link class="nav-link menu-link" :href="route('measure-registries.index')">
          <i class="ri-shield-check-line"></i>
          <span>Registri misure</span>
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
