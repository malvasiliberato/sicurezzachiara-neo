<script>
localStorage.setItem("rightbar_isopen", false);
import { layoutMethods, layoutComputed } from "@/state/helpers";
import simplebar from "simplebar-vue";
/**
 * Right sidebar component
 */

export default {
  data() {
    return {
      show: false,
      showGradients: false,
      resetLayoutMode: {},
      isThemePreferencesReady: false,
    };
  },
  beforeCreate() {
    localStorage.setItem("resetValue", JSON.stringify(this.$store.state.layout));
  },
  methods: {
    ...layoutMethods,
    click() {
      this.show = !this.show;
    },
    openThemeCustomizer() {
      localStorage.setItem("rightbar_isopen", true);
      this.show = true;
    },
    themePreferencesKey() {
      const user = this.$page?.props?.auth?.user;
      const userKey = user?.id || user?.email || "guest";

      return `sicurezzachiara.theme.${userKey}`;
    },
    normalizeLayoutType(layoutType) {
      return ["vertical", "horizontal"].includes(layoutType) ? layoutType : "vertical";
    },
    normalizeShellPalette(shellPalette) {
      if (shellPalette === "graphite") {
        return "sage";
      }

      return ["institutional", "sage", "deep-blue", "deep-blue-soft"].includes(shellPalette) ? shellPalette : "institutional";
    },
    normalizeHomePage(homePage) {
      return ["companies", "dashboard", "method"].includes(homePage) ? homePage : "companies";
    },
    normalizeUiDensity(uiDensity) {
      return ["comfortable", "compact"].includes(uiDensity) ? uiDensity : "comfortable";
    },
    defaultThemePreferences() {
      return {
        layoutType: "vertical",
        mode: "light",
        shellPalette: "institutional",
        homePage: "companies",
        uiDensity: "comfortable",
        preloader: "enable",
      };
    },
    loadThemePreferences() {
      const savedPreferences = localStorage.getItem(this.themePreferencesKey());
      let preferences = this.defaultThemePreferences();

      if (savedPreferences) {
        try {
          preferences = { ...preferences, ...JSON.parse(savedPreferences) };
        } catch (error) {
          localStorage.removeItem(this.themePreferencesKey());
        }
      }

      this.changeLayoutType({ layoutType: this.normalizeLayoutType(preferences.layoutType) });
      this.changeMode({ mode: preferences.mode || "light" });
      this.changeShellPalette({ shellPalette: this.normalizeShellPalette(preferences.shellPalette) });
      this.changeHomePage({ homePage: this.normalizeHomePage(preferences.homePage) });
      this.changeUiDensity({ uiDensity: this.normalizeUiDensity(preferences.uiDensity) });
      this.changePreloader({ preloader: preferences.preloader || "enable" });
    },
    themePreferencesPayload(overrides = {}) {
      return {
        layoutType: this.normalizeLayoutType(overrides.layoutType || this.layoutType),
        mode: overrides.mode || this.mode,
        shellPalette: this.normalizeShellPalette(overrides.shellPalette || this.shellPalette),
        homePage: this.normalizeHomePage(overrides.homePage || this.homePage),
        uiDensity: this.normalizeUiDensity(overrides.uiDensity || this.uiDensity),
        preloader: overrides.preloader || this.preloader,
      };
    },
    persistThemePreferences(preferences) {
      localStorage.setItem(this.themePreferencesKey(), JSON.stringify(preferences));
      localStorage.setItem("sicurezzachiara.theme.current", JSON.stringify(preferences));
    },
    saveThemePreferences() {
      if (!this.isThemePreferencesReady) {
        return;
      }

      this.persistThemePreferences(this.themePreferencesPayload());
    },
    topFunction() {
      document.body.scrollTop = 0;
      document.documentElement.scrollTop = 0;
    },

    resizeWindow() {
      var windowSize = document.documentElement.clientWidth;
      if (windowSize >= 1025) {
        if (document.documentElement.getAttribute("data-layout") === "vertical") {
          document.documentElement.setAttribute("data-sidebar-size", this.$store.state.layout.sidebarSize);
        }
        if (document.documentElement.getAttribute("data-sidebar-visibility") === "show" && document.querySelector(".hamburger-icon")) {
          document.querySelector(".hamburger-icon").classList.remove("open");
        }
      } else if (windowSize < 1025 && windowSize > 767) {
        document.body.classList.remove("twocolumn-panel");
        if (document.documentElement.getAttribute("data-layout") === "vertical") {
          document.documentElement.setAttribute("data-sidebar-size", "sm");
        }
        if (document.querySelector(".hamburger-icon")) {
          document.querySelector(".hamburger-icon").classList.add("open");
        }
      } else if (windowSize <= 767) {
        document.body.classList.remove("vertical-sidebar-enable");
        document.body.classList.add("twocolumn-panel");
        if (document.documentElement.getAttribute("data-layout") !== "horizontal") {
          document.documentElement.setAttribute("data-sidebar-size", "lg");
        }
        if (document.querySelector(".hamburger-icon")) {
          document.querySelector(".hamburger-icon").classList.add("open");
        }
      }
    },

    resetLayout() {
      const reset = this.defaultThemePreferences();
      document.documentElement.setAttribute("data-sidebar-size", "lg");
      this.changeMode({ mode: reset.mode });
      this.changeLayoutType({ layoutType: reset.layoutType });
      this.changeShellPalette({ shellPalette: reset.shellPalette });
      this.changeHomePage({ homePage: reset.homePage });
      this.changeUiDensity({ uiDensity: reset.uiDensity });
      this.changePreloader({ preloader: reset.preloader });
      this.saveThemePreferences();
    },

    gradiantColor() {
      this.changeSidebarColor({ sidebarColor: "gradient" })
    },

    onSideBarColorClick(color) {
      if (color !== 'gradient') {
        this.showGradients = false
      } else {
        this.showGradients = true
        this.gradiantColor();
      }
    }
  },
  mounted() {
    let backtoTop = document.getElementById("back-to-top");

    if (backtoTop) {
      backtoTop = document.getElementById("back-to-top");
      window.onscroll = function () {
        if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
          backtoTop.style.display = "block";
        } else {
          backtoTop.style.display = "none";
        }
      };
    }
    this.loadThemePreferences();
    this.isThemePreferencesReady = true;
    this.saveThemePreferences();

    var setpreloader = document.getElementById("preloader");
    const storedPreloader = localStorage.getItem('data-preloader') || "enable";
    if (storedPreloader == 'enable') {
      document.documentElement.setAttribute("data-preloader", "enable");
      if (setpreloader) {
        setTimeout(function () {
          setpreloader.style.opacity = "0";
          setpreloader.style.visibility = "hidden";
        }, 1000);
      }
    } else {
      document.documentElement.setAttribute("data-preloader", "disable");
      if (setpreloader) {
        setpreloader.style.opacity = "0";
        setpreloader.style.visibility = "hidden";
      }
    }
    if (document.getElementById('collapseBgGradient')) {
      Array.from(document.querySelectorAll("#collapseBgGradient .form-check input")).forEach(function () {
        if (document.querySelector("[data-bs-target='#collapseBgGradient']")) {
          document.querySelector("[data-bs-target='#collapseBgGradient']").addEventListener('click', function () {
            document.getElementById("sidebar-color-gradient").click();
          });
        }
      });
      Array.from(document.querySelectorAll("[name='data-sidebar']")).forEach(function (elem) {
        if (document.querySelector("[data-bs-target='#collapseBgGradient']")) {
          if (document.querySelector("#collapseBgGradient .form-check input:checked")) {
            document.querySelector("[data-bs-target='#collapseBgGradient']").classList.add("active");
          } else {
            document.querySelector("[data-bs-target='#collapseBgGradient']").classList.remove("active");
            document.getElementById('collapseBgGradient').classList.remove('show');
          }

          elem.addEventListener("change", function () {
            if (document.querySelector("#collapseBgGradient .form-check input:checked")) {
              document.querySelector("[data-bs-target='#collapseBgGradient']").classList.add("active");
            } else {
              document.getElementById('collapseBgGradient').classList.remove('show');
              document.querySelector("[data-bs-target='#collapseBgGradient']").classList.remove("active");
            }
          });
        }
      });
    }

    window.addEventListener("resize", this.resizeWindow);
    window.addEventListener("sicurezzachiara:open-theme-customizer", this.openThemeCustomizer);
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.resizeWindow);
    window.removeEventListener("sicurezzachiara:open-theme-customizer", this.openThemeCustomizer);
  },
  computed: {
    ...layoutComputed,
    layoutType: {
      get() {
        return this.$store ? this.$store.state.layout.layoutType : {} || {};
      },
      set(layout) {
        const nextLayoutType = this.normalizeLayoutType(layout);

        localStorage.setItem("rightbar_isopen", true);
        this.persistThemePreferences(this.themePreferencesPayload({ layoutType: nextLayoutType }));
        this.changeLayoutType({ layoutType: nextLayoutType, });
        document.querySelector(".hamburger-icon")?.classList.remove("open");
      },
    },
    preloader: {
      get() {
        return this.$store ? this.$store.state.layout.preloader : {} || {};
      },
      set(preloader) {
        return this.changePreloader({
          preloader: preloader,
        });
      },
    },
    mode: {
      get() {
        return this.$store ? this.$store.state.layout.mode : {} || {};
      },
      set(mode) {
        this.changeMode({ mode: mode });
        this.changeTopbar({ topbar: "dark" });
        this.changeSidebarColor({ sidebarColor: "dark" });
      },
    },
    shellPalette: {
      get() {
        return this.$store ? this.$store.state.layout.shellPalette : {} || {};
      },
      set(shellPalette) {
        const nextShellPalette = this.normalizeShellPalette(shellPalette);

        this.persistThemePreferences(this.themePreferencesPayload({ shellPalette: nextShellPalette }));
        this.changeShellPalette({ shellPalette: nextShellPalette });
      },
    },
    homePage: {
      get() {
        return this.$store ? this.$store.state.layout.homePage : "companies";
      },
      set(homePage) {
        const nextHomePage = this.normalizeHomePage(homePage);

        this.persistThemePreferences(this.themePreferencesPayload({ homePage: nextHomePage }));
        this.changeHomePage({ homePage: nextHomePage });
      },
    },
    uiDensity: {
      get() {
        return this.$store ? this.$store.state.layout.uiDensity : "comfortable";
      },
      set(uiDensity) {
        const nextUiDensity = this.normalizeUiDensity(uiDensity);

        this.persistThemePreferences(this.themePreferencesPayload({ uiDensity: nextUiDensity }));
        this.changeUiDensity({ uiDensity: nextUiDensity });
      },
    },
    sidebarSize: {
      get() {
        return this.$store ? this.$store.state.layout.sidebarSize : {} || {};
      },
      set(type) {
        return this.changeSidebarSize({
          sidebarSize: type,
        });
      },
    },
    layoutWidth: {
      get() {
        return this.$store ? this.$store.state.layout.layoutWidth : {} || {};
      },
      set(width) {
        if (width == 'boxed') {
          this.changeLayoutWidth({ layoutWidth: width });
          this.changeSidebarSize({ sidebarSize: 'sm-hover' });
        } else {
          this.changeLayoutWidth({ layoutWidth: width });
          this.changeSidebarSize({ sidebarSize: 'lg' });
        }
      },
    },
    position: {
      get() {
        return this.$store ? this.$store.state.layout.position : {} || {};
      },
      set(position) {
        return this.changePosition({
          position: position,
        });
      },
    },
    topbar: {
      get() {
        return this.$store ? this.$store.state.layout.topbar : {} || {};
      },
      set(topbar) {
        this.changeTopbar({
          topbar: topbar,
        });
      },
    },
    sidebarView: {
      get() {
        return this.$store ? this.$store.state.layout.sidebarView : {} || {};
      },
      set(sidebarView) {
        return this.changeSidebarView({
          sidebarView: sidebarView,
        });
      },
    },
    sidebarColor: {
      get() {
        return this.$store ? this.$store.state.layout.sidebarColor : {} || {};
      },
      set(sidebarColor) {
        return this.changeSidebarColor({
          sidebarColor: sidebarColor,
        });
      },
    },
    sidebarImage: {
      get() {
        return this.$store ? this.$store.state.layout.sidebarImage : {} || {};
      },
      set(sidebarImage) {
        return this.changeSidebarImage({
          sidebarImage: sidebarImage,
        });
      },
    },

    visibility: {
      get() {
        return this.$store ? this.$store.state.layout.visibility : {} || {};
      },
      set(visibility) {
        if (visibility == 'hidden') {
          document.querySelector(".hamburger-icon").classList.add("open");
        } else {
          document.querySelector(".hamburger-icon").classList.remove("open");
        }
        this.changeVisibility({ visibility: visibility });
      },
    },
  },

  watch: {
    show: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          if (!newVal) {
            document.body.removeAttribute("style");
          } 
          // else {
          //   setTimeout(() => {
          //     document.body.setAttribute("style", "overflow: hidden; padding-right:17px");
          //   }, 500)
          // }
        }
      },
    },
    mode: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          switch (newVal) {
            case "dark":
              document.documentElement.setAttribute("data-bs-theme", "dark");
              break;
            case "light":
              document.documentElement.setAttribute("data-bs-theme", "light");
              break;
          }
          this.saveThemePreferences();
        }
      },
    },
    shellPalette: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          document.documentElement.setAttribute("data-shell-palette", this.normalizeShellPalette(newVal));
          this.saveThemePreferences();
        }
      },
    },
    homePage: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          document.documentElement.setAttribute("data-home-page", this.normalizeHomePage(newVal));
          this.saveThemePreferences();
        }
      },
    },
    uiDensity: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          document.documentElement.setAttribute("data-ui-density", this.normalizeUiDensity(newVal));
          this.saveThemePreferences();
        }
      },
    },
    preloader: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          switch (newVal) {
            case "enable":
              document.documentElement.setAttribute("data-preloader", "enable");
              break;
            case "disable":
              document.documentElement.setAttribute("data-preloader", "disable");
              break;
          }
          localStorage.setItem('data-preloader', newVal);
          this.saveThemePreferences();
        }
      },
    },
    layoutType: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          switch (newVal) {
            case "horizontal":
              document.documentElement.setAttribute("data-layout", "horizontal");
              break;
            case "vertical":
              document.documentElement.setAttribute("data-layout", "vertical");
              break;
          }
          this.saveThemePreferences();
        }
      },
    },
    layoutWidth: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          switch (newVal) {
            case "fluid":
              document.documentElement.setAttribute("data-layout-width", "fluid");
              break;
            case "boxed":
              document.documentElement.setAttribute("data-layout-width", "boxed");
              break;
          }
        }
      },
    },
    position: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          switch (newVal) {
            case "fixed":
              document.documentElement.setAttribute("data-layout-position", "fixed");
              break;
            case "scrollable":
              document.documentElement.setAttribute("data-layout-position", "scrollable");
              break;
          }
        }
      },
    },
    topbar: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          switch (newVal) {
            case "light":
              document.documentElement.setAttribute("data-topbar", "light");
              break;
            case "dark":
              document.documentElement.setAttribute("data-topbar", "dark");
              break;
          }
        }
      },
    },
    sidebarSize: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          switch (newVal) {
            case "lg":
              document.documentElement.setAttribute("data-sidebar-size", "lg");
              break;
            case "sm":
              document.documentElement.setAttribute("data-sidebar-size", "sm");
              break;
            case "md":
              document.documentElement.setAttribute("data-sidebar-size", "md");
              break;
            case "sm-hover":
              document.documentElement.setAttribute("data-sidebar-size", "sm-hover");
              break;
          }
        }
      },
    },
    sidebarView: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          switch (newVal) {
            case "detached":
              document.documentElement.setAttribute("data-layout-style", "detached");
              break;
            case "default":
              document.documentElement.setAttribute("data-layout-style", "default");
              break;
          }
        }
      },
    },
    sidebarColor: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          switch (newVal) {
            case "dark":
              document.documentElement.setAttribute("data-sidebar", "dark");
              break;
            case "light":
              document.documentElement.setAttribute("data-sidebar", "light");
              break;
            case "gradient":
              document.documentElement.setAttribute("data-sidebar", "gradient");
              break;
            case "gradient-2":
              document.documentElement.setAttribute("data-sidebar", "gradient-2");
              break;
            case "gradient-3":
              document.documentElement.setAttribute("data-sidebar", "gradient-3");
              break;
            case "gradient-4":
              document.documentElement.setAttribute("data-sidebar", "gradient-4");
              break;
          }
        }
      },
    },
    sidebarImage: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          switch (newVal) {
            case "img-1":
              document.documentElement.setAttribute("data-sidebar-image", "img-1");
              break;
            case "img-2":
              document.documentElement.setAttribute("data-sidebar-image", "img-2");
              break;
            case "img-3":
              document.documentElement.setAttribute("data-sidebar-image", "img-3");
              break;
            case "img-4":
              document.documentElement.setAttribute("data-sidebar-image", "img-4");
              break;
            case "none":
              document.documentElement.setAttribute("data-sidebar-image", "none");
              break;
          }
        }
      },
    },
    visibility: {
      immediate: true,
      deep: true,
      handler(newVal, oldVal) {
        if (newVal !== oldVal) {
          switch (newVal) {
            case "show":
              document.documentElement.setAttribute("data-sidebar-visibility", "show");
              break;
            case "hidden":
              document.documentElement.setAttribute("data-sidebar-visibility", "hidden");
              break;
          }
        }
      },
    },
  },
  components: { simplebar }
};


</script>

<template>
  <div>
    <div id="preloader">
      <div id="status">
        <div class="spinner-border text-primary avatar-sm" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
    </div>

    <BButton variant="danger" @click="topFunction" class="btn-icon" id="back-to-top">
      <i class="ri-arrow-up-line"></i>
    </BButton>

    <BOffcanvas class="border-0" id="theme-settings-offcanvas" header-class="d-flex align-items-center bg-primary bg-gradient p-3" body-class="p-0" z-index="1005" footer-class="offcanvas-footer border-top p-3 text-center" placement="end" v-model="show">
      <template #header>
        <div class="me-2">
          <h5 class="m-0 me-2 text-white">Impostazioni interfaccia</h5>
        </div>
        <button type="button" class="btn-close btn-close-white ms-auto" id="customizerclose-btn" @click="click"></button>
      </template>
      <simplebar class="h-100">
        <div class="p-4">
          <h6 class="mb-0 fw-semibold text-uppercase">Layout</h6>
          <p class="text-muted">Scegli la struttura generale dell'area di lavoro.</p>

          <BRow class="gy-3">
            <BCol cols="6">
              <div class="form-check card-radio">
                <input id="customizer-layout01" name="data-layout" type="radio" value="vertical" class="form-check-input" v-model="layoutType" />
                <label class="form-check-label p-0 avatar-md w-100" for="customizer-layout01">
                  <span class="d-flex gap-1 h-100">
                    <span class="flex-shrink-0">
                      <span class="bg-light d-flex h-100 flex-column gap-1 p-1">
                        <span class="d-block p-1 px-2 bg-primary-subtle rounded mb-2"></span>
                        <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                        <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                        <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                      </span>
                    </span>
                    <span class="flex-grow-1">
                      <span class="d-flex h-100 flex-column">
                        <span class="bg-light d-block p-1"></span>
                        <span class="bg-light d-block p-1 mt-auto"></span>
                      </span>
                    </span>
                  </span>
                </label>
              </div>
              <h5 class="fs-13 text-center mt-2">Verticale</h5>
            </BCol>
            <BCol cols="6">
              <div class="form-check card-radio">
                <input id="customizer-layout02" name="data-layout" type="radio" value="horizontal" class="form-check-input" v-model="layoutType" />
                <label class="form-check-label p-0 avatar-md w-100" for="customizer-layout02">
                  <span class="d-flex h-100 flex-column gap-1">
                    <span class="bg-light d-flex p-1 gap-1 align-items-center">
                      <span class="d-block p-1 bg-primary-subtle rounded me-1"></span>
                      <span class="d-block p-1 pb-0 px-2 bg-primary-subtle ms-auto"></span>
                      <span class="d-block p-1 pb-0 px-2 bg-primary-subtle"></span>
                    </span>
                    <span class="bg-light d-block p-1"></span>
                    <span class="bg-light d-block p-1 mt-auto"></span>
                  </span>
                </label>
              </div>
              <h5 class="fs-13 text-center mt-2">Orizzontale</h5>
            </BCol>
          </BRow>

          <h6 class="mt-4 mb-0 fw-semibold text-uppercase">Schema colori</h6>
          <p class="text-muted">Scegli tra modalita' chiara e modalita' scura.</p>

          <div class="colorscheme-cardradio">
            <BRow class="gy-3">
              <BCol cols="6">
                <div class="form-check card-radio">
                  <input class="form-check-input" type="radio" name="data-bs-theme" id="layout-mode-light" value="light" v-model="mode" />
                  <label class="form-check-label p-0 avatar-md w-100" for="layout-mode-light">
                    <span class="d-flex gap-1 h-100">
                      <span class="flex-shrink-0">
                        <span class="bg-light d-flex h-100 flex-column gap-1 p-1">
                          <span class="d-block p-1 px-2 bg-primary-subtle rounded mb-2"></span>
                          <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                          <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                          <span class="d-block p-1 px-2 pb-0 bg-primary-subtle"></span>
                        </span>
                      </span>
                      <span class="flex-grow-1">
                        <span class="d-flex h-100 flex-column">
                          <span class="bg-light d-block p-1"></span>
                          <span class="bg-light d-block p-1 mt-auto"></span>
                        </span>
                      </span>
                    </span>
                  </label>
                </div>
                <h5 class="fs-13 text-center mt-2">Chiaro</h5>
              </BCol>

              <BCol cols="6">
                <div class="form-check card-radio dark">
                  <input class="form-check-input" v-model="mode" type="radio" name="data-bs-theme" id="layout-mode-dark" value="dark" />
                  <label class="form-check-label p-0 avatar-md w-100 bg-dark" for="layout-mode-dark">
                    <span class="d-flex gap-1 h-100">
                      <span class="flex-shrink-0">
                        <span class="bg-white bg-opacity-10 d-flex h-100 flex-column gap-1 p-1">
                          <span class="d-block p-1 px-2 bg-white bg-opacity-10 rounded mb-2"></span>
                          <span class="d-block p-1 px-2 pb-0 bg-white bg-opacity-10"></span>
                          <span class="d-block p-1 px-2 pb-0 bg-white bg-opacity-10"></span>
                          <span class="d-block p-1 px-2 pb-0 bg-white bg-opacity-10"></span>
                        </span>
                      </span>
                      <span class="flex-grow-1">
                        <span class="d-flex h-100 flex-column">
                          <span class="bg-white bg-opacity-10 d-block p-1"></span>
                          <span class="bg-white bg-opacity-10 d-block p-1 mt-auto"></span>
                        </span>
                      </span>
                    </span>
                  </label>
                </div>
                <h5 class="fs-13 text-center mt-2">Scuro</h5>
              </BCol>
            </BRow>
          </div>

          <h6 class="mt-4 mb-0 fw-semibold text-uppercase">Colore barre</h6>
          <p class="text-muted">Scegli il colore di sidebar e topbar nello schema chiaro.</p>

          <BRow class="gy-3">
            <BCol cols="6">
              <div class="form-check card-radio">
                <input id="shell-palette-institutional" name="data-shell-palette" type="radio" value="institutional" class="form-check-input" v-model="shellPalette" />
                <label class="form-check-label p-0 avatar-md w-100" for="shell-palette-institutional">
                  <span class="d-flex h-100 flex-column">
                    <span class="d-block p-2" style="background-color: #093d37"></span>
                    <span class="d-flex flex-grow-1">
                      <span class="d-block h-100 w-25" style="background-color: #062f2b"></span>
                      <span class="d-block flex-grow-1 bg-light"></span>
                    </span>
                  </span>
                </label>
              </div>
              <h5 class="fs-13 text-center mt-2">Istituzionale</h5>
            </BCol>

            <BCol cols="6">
              <div class="form-check card-radio">
                <input id="shell-palette-sage" name="data-shell-palette" type="radio" value="sage" class="form-check-input" v-model="shellPalette" />
                <label class="form-check-label p-0 avatar-md w-100" for="shell-palette-sage">
                  <span class="d-flex h-100 flex-column">
                    <span class="d-block p-2" style="background-color: #dcebe4"></span>
                    <span class="d-flex flex-grow-1">
                      <span class="d-block h-100 w-25" style="background-color: #c8ddd3"></span>
                      <span class="d-block flex-grow-1 bg-light"></span>
                    </span>
                  </span>
                </label>
              </div>
              <h5 class="fs-13 text-center mt-2">Salvia chiaro</h5>
            </BCol>

            <BCol cols="6">
              <div class="form-check card-radio">
                <input id="shell-palette-deep-blue" name="data-shell-palette" type="radio" value="deep-blue" class="form-check-input" v-model="shellPalette" />
                <label class="form-check-label p-0 avatar-md w-100" for="shell-palette-deep-blue">
                  <span class="d-flex h-100 flex-column">
                    <span class="d-block p-2" style="background-color: #193a5a"></span>
                    <span class="d-flex flex-grow-1">
                      <span class="d-block h-100 w-25" style="background-color: #122c43"></span>
                      <span class="d-block flex-grow-1 bg-light"></span>
                    </span>
                  </span>
                </label>
              </div>
              <h5 class="fs-13 text-center mt-2">Blu profondo</h5>
            </BCol>

            <BCol cols="6">
              <div class="form-check card-radio">
                <input id="shell-palette-deep-blue-soft" name="data-shell-palette" type="radio" value="deep-blue-soft" class="form-check-input" v-model="shellPalette" />
                <label class="form-check-label p-0 avatar-md w-100" for="shell-palette-deep-blue-soft">
                  <span class="d-flex h-100 flex-column">
                    <span class="d-block p-2" style="background-color: #dbe9f4"></span>
                    <span class="d-flex flex-grow-1">
                      <span class="d-block h-100 w-25" style="background-color: #c4d8e8"></span>
                      <span class="d-block flex-grow-1 bg-light"></span>
                    </span>
                  </span>
                </label>
              </div>
              <h5 class="fs-13 text-center mt-2">Blu chiaro</h5>
            </BCol>
          </BRow>

          <h6 class="mt-4 mb-0 fw-semibold text-uppercase">Pagina iniziale</h6>
          <p class="text-muted">Scegli dove aprire l'area di lavoro dopo l'accesso.</p>

          <BRow class="gy-3">
            <BCol cols="12">
              <div class="form-check card-radio">
                <input id="home-page-companies" name="data-home-page" type="radio" value="companies" class="form-check-input" v-model="homePage" />
                <label class="form-check-label d-flex align-items-center gap-3 p-3 w-100" for="home-page-companies">
                  <span class="avatar-xs rounded bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0">
                    <i class="ri-building-4-line"></i>
                  </span>
                  <span>
                    <span class="d-block fw-medium">Portfolio aziende</span>
                    <span class="d-block text-muted fs-12">Riparti dall'elenco operativo dei clienti.</span>
                  </span>
                </label>
              </div>
            </BCol>

            <BCol cols="12">
              <div class="form-check card-radio">
                <input id="home-page-dashboard" name="data-home-page" type="radio" value="dashboard" class="form-check-input" v-model="homePage" />
                <label class="form-check-label d-flex align-items-center gap-3 p-3 w-100" for="home-page-dashboard">
                  <span class="avatar-xs rounded bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0">
                    <i class="ri-dashboard-2-line"></i>
                  </span>
                  <span>
                    <span class="d-block fw-medium">Dashboard</span>
                    <span class="d-block text-muted fs-12">Apri subito il quadro operativo generale.</span>
                  </span>
                </label>
              </div>
            </BCol>

            <BCol cols="12">
              <div class="form-check card-radio">
                <input id="home-page-method" name="data-home-page" type="radio" value="method" class="form-check-input" v-model="homePage" />
                <label class="form-check-label d-flex align-items-center gap-3 p-3 w-100" for="home-page-method">
                  <span class="avatar-xs rounded bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0">
                    <i class="ri-route-line"></i>
                  </span>
                  <span>
                    <span class="d-block fw-medium">Metodo</span>
                    <span class="d-block text-muted fs-12">Vai al percorso di lavoro guidato.</span>
                  </span>
                </label>
              </div>
            </BCol>
          </BRow>

          <h6 class="mt-4 mb-0 fw-semibold text-uppercase">Densita'</h6>
          <p class="text-muted">Scegli quanto compatta deve essere l'interfaccia.</p>

          <BRow class="gy-3">
            <BCol cols="6">
              <div class="form-check card-radio">
                <input id="ui-density-comfortable" name="data-ui-density" type="radio" value="comfortable" class="form-check-input" v-model="uiDensity" />
                <label class="form-check-label p-3 w-100" for="ui-density-comfortable">
                  <span class="d-flex flex-column gap-2">
                    <span class="d-block rounded bg-primary-subtle p-2"></span>
                    <span class="d-block rounded bg-light p-2"></span>
                    <span class="d-block rounded bg-light p-2"></span>
                  </span>
                </label>
              </div>
              <h5 class="fs-13 text-center mt-2">Comoda</h5>
            </BCol>

            <BCol cols="6">
              <div class="form-check card-radio">
                <input id="ui-density-compact" name="data-ui-density" type="radio" value="compact" class="form-check-input" v-model="uiDensity" />
                <label class="form-check-label p-3 w-100" for="ui-density-compact">
                  <span class="d-flex flex-column gap-1">
                    <span class="d-block rounded bg-primary-subtle p-1"></span>
                    <span class="d-block rounded bg-light p-1"></span>
                    <span class="d-block rounded bg-light p-1"></span>
                    <span class="d-block rounded bg-light p-1"></span>
                  </span>
                </label>
              </div>
              <h5 class="fs-13 text-center mt-2">Compatta</h5>
            </BCol>
          </BRow>

        </div>
      </simplebar>
      <template #footer>
        <BRow>
          <BCol cols="12">
            <BButton type="button" variant="light" class="w-100" id="reset-layout" @click="resetLayout">Ripristina impostazioni</BButton>
          </BCol>
        </BRow>
      </template>
    </BOffcanvas>
  </div>
</template>

<style lang="scss">
.b-overlay-wrap {
  .b-overlay {
    z-index: 1005 !important;
  }
}
</style>

  
