const savedThemePreferences = (() => {
  try {
    return JSON.parse(localStorage.getItem('sicurezzachiara.theme.current')) || {};
  } catch (error) {
    localStorage.removeItem('sicurezzachiara.theme.current');
    return {};
  }
})();

const normalizeLayoutType = (layoutType) => ['vertical', 'horizontal'].includes(layoutType)
  ? layoutType
  : 'vertical';

const normalizeShellPalette = (shellPalette) => {
  if (shellPalette === 'graphite') {
    return 'sage';
  }

  return ['institutional', 'sage', 'deep-blue', 'deep-blue-soft'].includes(shellPalette)
    ? shellPalette
    : 'institutional';
};

const normalizeHomePage = (homePage) => ['companies', 'dashboard', 'method'].includes(homePage)
  ? homePage
  : 'companies';

const normalizeUiDensity = (uiDensity) => ['comfortable', 'compact'].includes(uiDensity)
  ? uiDensity
  : 'comfortable';

const state = {
  layoutType: normalizeLayoutType(savedThemePreferences.layoutType),
  layoutWidth: 'fluid',
  sidebarSize: 'lg',
  topbar: 'dark',
  mode: savedThemePreferences.mode || 'light',
  shellPalette: normalizeShellPalette(savedThemePreferences.shellPalette),
  homePage: normalizeHomePage(savedThemePreferences.homePage),
  uiDensity: normalizeUiDensity(savedThemePreferences.uiDensity),
  position: 'fixed',
  sidebarView: 'default',
  sidebarColor: 'dark',
  sidebarImage: 'none',
  preloader: savedThemePreferences.preloader || 'enable',
  visibility: 'show'
};

const mutations = {
  CHANGE_LAYOUT(state, layoutType) {
    state.layoutType = layoutType;
  },
  CHANGE_LAYOUT_WIDTH(state, layoutWidth) {
    state.layoutWidth = layoutWidth;
  },
  CHANGE_SIDEBAR_TYPE(state, sidebarSize) {
    state.sidebarSize = sidebarSize;
  },
  CHANGE_TOPBAR(state, topbar) {
    state.topbar = topbar;
  },
  CHANGE_MODE(state, mode) {
    state.mode = mode;
  },
  CHANGE_SHELL_PALETTE(state, shellPalette) {
    state.shellPalette = shellPalette;
  },
  CHANGE_HOME_PAGE(state, homePage) {
    state.homePage = homePage;
  },
  CHANGE_UI_DENSITY(state, uiDensity) {
    state.uiDensity = uiDensity;
  },
  CHANGE_POSITION(state, position) {
    state.position = position;
  },
  CHANGE_SIDEBAR_VIEW(state, sidebarView) {
    state.sidebarView = sidebarView;
  },
  CHANGE_SIDEBAR_COLOR(state, sidebarColor) {
    state.sidebarColor = sidebarColor;
  },
  CHANGE_SIDEBAR_IMAGE(state, sidebarImage) {
    state.sidebarImage = sidebarImage;
  },
  CHANGE_PRELOADER(state, preloader) {
    state.preloader = preloader;
  },
  CHANGE_VISIBILITY(state, visibility) {
    state.visibility = visibility;
  }
};

const actions = {
  changeLayoutType({ commit }, { layoutType }) {
    commit('CHANGE_LAYOUT', layoutType);
    document.body.removeAttribute("style");
  },

  changeLayoutWidth({ commit }, { layoutWidth }) {
    commit('CHANGE_LAYOUT_WIDTH', layoutWidth);
  },

  changeSidebarSize({ commit }, { sidebarSize }) {
    commit('CHANGE_SIDEBAR_TYPE', sidebarSize);
  },

  changeTopbar({ commit }, { topbar }) {
    commit('CHANGE_TOPBAR', topbar);
  },

  changeMode({ commit }, { mode }) {
    commit('CHANGE_MODE', mode);
  },

  changeShellPalette({ commit }, { shellPalette }) {
    commit('CHANGE_SHELL_PALETTE', shellPalette);
  },

  changeHomePage({ commit }, { homePage }) {
    commit('CHANGE_HOME_PAGE', homePage);
  },

  changeUiDensity({ commit }, { uiDensity }) {
    commit('CHANGE_UI_DENSITY', uiDensity);
  },

  changePosition({ commit }, { position }) {
    commit('CHANGE_POSITION', position);
  },

  changeSidebarView({ commit }, { sidebarView }) {
    commit('CHANGE_SIDEBAR_VIEW', sidebarView);
  },

  changeSidebarColor({ commit }, { sidebarColor }) {
    commit('CHANGE_SIDEBAR_COLOR', sidebarColor);
  },

  changeSidebarImage({ commit }, { sidebarImage }) {
    commit('CHANGE_SIDEBAR_IMAGE', sidebarImage);
  },

  changePreloader({ commit }, { preloader }) {
    commit('CHANGE_PRELOADER', preloader);
  },

  changeVisibility({ commit }, { visibility }) {
    commit('CHANGE_VISIBILITY', visibility);
  }
};

export default {
  namespaced: true, 
  state,
  mutations,
  actions,
};
