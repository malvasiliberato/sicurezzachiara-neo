<script>
import { Link } from '@inertiajs/vue3';
import NavBar from "@/Components/nav-bar.vue";
import RightBar from "@/Components/right-bar.vue";
import Footer from "@/Components/footer.vue";
import Menu from "@/Components/menu.vue";
import { layoutComputed } from "@/state/helpers";

export default {
  mounted() {
    this.initActiveMenu();
  },
  methods: {
    initActiveMenu(ele) {
      setTimeout(() => {
        var currentPath = window.location.pathname;
        if (document.querySelector("#navbar-nav")) {
          let a = document.querySelector("#navbar-nav").querySelector('[href="' + currentPath + '"]');

          if (a) {
            a.classList.add("active");
            let parentCollapseDiv = a.closest(".collapse.menu-dropdown");
            if (parentCollapseDiv) {
              parentCollapseDiv.classList.add("show");
              parentCollapseDiv.parentElement.children[0].classList.add("active");
              parentCollapseDiv.parentElement.children[0].setAttribute("aria-expanded", "true");
              if (parentCollapseDiv.parentElement.closest(".collapse.menu-dropdown")) {
                parentCollapseDiv.parentElement.closest(".collapse").classList.add("show");
                if (parentCollapseDiv.parentElement.closest(".collapse").previousElementSibling)
                  parentCollapseDiv.parentElement.closest(".collapse").previousElementSibling.classList.add("active");
              }
            }
          }
        }
      }, 1000);
    },
  },
  computed: {
    ...layoutComputed,
    preferredHomeHref() {
      return {
        dashboard: route('dashboard'),
        method: route('sicurezzachiara.method'),
      }[this.homePage] || route('companies.index');
    },
  },
  components: {
    NavBar,
    RightBar,
    Footer,
    Menu,
    Link
  },
};
</script>

<template>
  <div>
    <div id="layout-wrapper">
      <NavBar />
      <!-- ========== App Menu ========== -->
      <div class="app-menu navbar-menu">
        <!-- LOGO -->
        <div class="navbar-brand-box">
          <!-- Dark Logo-->
          <Link :href="preferredHomeHref" class="logo logo-dark">
          <span class="logo-sm">
            <img src="@assets/images/logo-sm.png" alt="" height="40" />
          </span>
          <span class="logo-lg">
            <img src="@assets/images/logo-dark.png" alt="" height="25" />
          </span>
          </Link>
          <!-- Light Logo-->
          <Link :href="preferredHomeHref" class="logo logo-light">
          <span class="logo-sm">
            <img src="@assets/images/logo-sm-1.png" alt="" height="40" />
          </span>
          <span class="logo-lg">
            <img src="@assets/images/logo-light.png" alt="" height="40" />
          </span>
          </Link>
          <Link :href="preferredHomeHref" class="logo logo-sage">
          <span class="logo-sm">
            <img src="@assets/images/logo-sm-sage.png" alt="" height="40" />
          </span>
          <span class="logo-lg">
            <img src="@assets/images/logo-sage.png" alt="" height="40" />
          </span>
          </Link>
          <Link :href="preferredHomeHref" class="logo logo-blue">
          <span class="logo-sm">
            <img src="@assets/images/logo-sm-blue.png" alt="" height="40" />
          </span>
          <span class="logo-lg">
            <img src="@assets/images/logo-blue.png" alt="" height="40" />
          </span>
          </Link>
          <BButton size="sm" class="p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
          </BButton>
        </div>
        <div id="scrollbar">
          <Menu />
          <!-- Sidebar -->
        </div>
        <!-- Left Sidebar End -->
        <!-- Vertical Overlay-->
        <div class="vertical-overlay"></div>
      </div>
      <!-- ============================================================== -->
      <!-- Start Page Content here -->
      <!-- ============================================================== -->

      <div class="main-content">
        <div class="page-content">
          <!-- Start Content-->
          <BContainer fluid>
            <slot />
          </BContainer>
        </div>
        <Footer />
      </div>
      <RightBar />
    </div>
  </div>
</template>
