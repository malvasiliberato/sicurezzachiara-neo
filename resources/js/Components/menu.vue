<script>
import { Link, router } from '@inertiajs/vue3';
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
      currentPath: window.location.pathname,
      removeInertiaFinishListener: null,
    };
  },
  computed: {
    ...layoutComputed,
    layoutType: {
      get() {
        return this.$store ? this.$store.state.layout.layoutType : {} || {};
      },
    },
    menuSections() {
      return [
        {
          id: "companies",
          label: "Aziende",
          icon: "ri-building-4-line",
          collapseId: "sidebarCompanies",
          items: [
            { label: "Portfolio aziende", href: route('companies.index') },
            { label: "Nuova azienda", href: route('companies.create') },
          ],
        },
        {
          id: "method",
          label: "Metodo",
          icon: "ri-function-line",
          collapseId: "sidebarMethod",
          items: [
            { label: "Panoramica metodo", href: route('sicurezzachiara.method') },
            { label: "Rischi", href: route('risk-catalog.index') },
            { label: "Mansioni", href: route('job-roles.index') },
            { label: "Tipologie macchinario", href: route('equipment-types.index') },
            { label: "Tipologie luogo", href: route('workplace-types.index') },
          ],
        },
      ];
    },
  },
  mounted() {
    this.initActiveMenu();
    this.onRoutechange();
    this.removeInertiaFinishListener = router.on("finish", () => {
      this.currentPath = window.location.pathname;
      this.$nextTick(() => {
        this.initActiveMenu();
        this.onRoutechange();
      });
    });
  },
  beforeUnmount() {
    if (this.removeInertiaFinishListener) {
      this.removeInertiaFinishListener();
    }
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
        this.currentPath = window.location.pathname;
      }, 0);
    },
    isCurrentHref(href) {
      return this.normalizePath(href) === this.currentPath;
    },
    isAnyCurrentHref(hrefs) {
      return hrefs.some((href) => this.isCurrentHref(href));
    },
    normalizePath(href) {
      try {
        return new URL(href, window.location.origin).pathname;
      } catch (error) {
        return href;
      }
    },
    isSectionCurrent(section) {
      return this.isAnyCurrentHref(section.items.map((item) => item.href));
    },
    updateTwoColumnMenu(targetId, event) {
      document.body.classList.remove("twocolumn-panel");

      document.querySelectorAll("#navbar-nav .menu-dropdown.show").forEach((item) => {
        item.classList.remove("show");
      });

      document.querySelectorAll("#two-column-menu .nav-icon.active").forEach((item) => {
        item.classList.remove("active");
      });

      document.getElementById(targetId)?.classList.add("show");
      event.currentTarget.classList.add("active");
    },
  },
};
</script>

<template>
  <BContainer fluid>
    <div id="two-column-menu"></div>

    <template v-if="layoutType === 'twocolumn'">
      <div id="two-column-menu">
        <simplebar class="twocolumn-iconview list-unstyled">
          <a class="logo">
            <img class="logo-sm-default" src="@assets/images/logo-sm-1.png" alt="Logo" height="40" />
            <img class="logo-sm-sage" src="@assets/images/logo-sm-sage.png" alt="Logo" height="40" />
            <img class="logo-sm-blue" src="@assets/images/logo-sm-blue.png" alt="Logo" height="40" />
          </a>
          <li>
            <Link class="nav-icon" :class="{ active: isCurrentHref(route('dashboard')) }" :href="route('dashboard')">
            <i class="ri-dashboard-2-line"></i>
            </Link>
          </li>
          <li v-for="section in menuSections" :key="`twocolumn-icon-${section.id}`">
            <a :href="`#${section.collapseId}`" class="nav-icon" :class="{ active: isSectionCurrent(section) }"
              role="button" @click.prevent="updateTwoColumnMenu(section.collapseId, $event)">
              <i :class="section.icon"></i>
            </a>
          </li>
        </simplebar>
      </div>

      <simplebar class="navbar-nav" id="navbar-nav">
        <li v-for="section in menuSections" :key="`twocolumn-panel-${section.id}`" class="nav-item">
          <div class="collapse menu-dropdown" :class="{ show: isSectionCurrent(section) }" :id="section.collapseId">
            <div class="px-3 py-2 fw-semibold text-uppercase text-muted fs-12">{{ section.label }}</div>
            <ul class="nav nav-sm flex-column">
              <li v-for="item in section.items" :key="item.href" class="nav-item">
                <Link class="nav-link" :class="{ active: isCurrentHref(item.href) }" :href="item.href">
                {{ item.label }}
                </Link>
              </li>
            </ul>
          </div>
        </li>
      </simplebar>
    </template>

    <template v-else-if="['vertical', 'horizontal'].includes(layoutType)">
      <ul class="navbar-nav h-100" id="navbar-nav">
        <li class="nav-item">
          <Link class="nav-link menu-link" :class="{ active: isCurrentHref(route('dashboard')) }" :href="route('dashboard')">
          <i class="ri-dashboard-2-line"></i>
          <span>Dashboard</span>
          </Link>
        </li>

        <li v-for="section in menuSections" :key="`default-${section.id}`" class="nav-item">
          <a class="nav-link menu-link" :href="`#${section.collapseId}`" data-bs-toggle="collapse" role="button"
            :class="{ active: isSectionCurrent(section) }"
            :aria-expanded="isSectionCurrent(section) ? 'true' : 'false'"
            :aria-controls="section.collapseId">
            <i :class="section.icon"></i>
            <span>{{ section.label }}</span>
          </a>
          <div class="collapse menu-dropdown" :class="{ show: isSectionCurrent(section) }" :id="section.collapseId">
            <ul class="nav nav-sm flex-column">
              <li v-for="item in section.items" :key="item.href" class="nav-item">
                <Link class="nav-link" :class="{ active: isCurrentHref(item.href) }" :href="item.href">
                {{ item.label }}
                </Link>
              </li>
            </ul>
          </div>
        </li>
      </ul>
    </template>
  </BContainer>
</template>
