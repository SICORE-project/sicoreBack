(function () {
  "use strict";

  var SIDEBAR_STORAGE_KEY = "sicore_sidebar_collapsed";
  var FONT_AWESOME_ID = "font-awesome-css";
  var FONT_AWESOME_HREF = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css";
  var sidebarTooltip = null;

  var sidebarMenu = [
    { type: "link", icon: "fa-solid fa-gauge-high", label: "Tableau de bord", href: "/dashboard" },
    {
      type: "group",
      icon: "fa-solid fa-money-check-dollar",
      label: "Gestion de la paie",
      links: [
        { label: "Avance Tabaski", href: "/paie/avance-tabaski", icon: "fa-solid fa-hand-holding-dollar" },
        { label: "Retenue Tabaski", href: "/paie/retenue-tabaski", icon: "fa-solid fa-money-bill-transfer" },
        { label: "Retenues rappel", href: "/paie/retenues-rappel", icon: "fa-solid fa-clock-rotate-left" },
        { label: "Exemption d&rsquo;avance ou de retenue par enseignant", href: "/paie/exemptions", icon: "fa-solid fa-user-shield" },
        { label: "&Eacute;tat r&eacute;capitulatif par banque", href: "/paie/recap-banque", icon: "fa-solid fa-building-columns" },
        { label: "Cotisations sociales", href: "/paie/cotisations-sociales", icon: "fa-solid fa-people-group" },
        { label: "&Eacute;tat des salaires", href: "/paie/etat-salaires", icon: "fa-solid fa-file-invoice-dollar" },
        { label: "Tableau de bord des &eacute;l&eacute;ments de saisie", href: "/paie/elements-saisie-dashboard", icon: "fa-solid fa-chart-line" },
        { label: "Paie g&eacute;n&eacute;r&eacute;e par IEF", href: "/paie/generee-ief", icon: "fa-solid fa-sitemap" },
        { label: "&Eacute;dition des salaires par banque", href: "/paie/edition-salaires-banque", icon: "fa-solid fa-building-columns" },
        { label: "Bulletins des salaires", href: "/paie/bulletins", icon: "fa-solid fa-file-lines" },
        { label: "Effectifs par corps", href: "/paie/effectifs-corps", icon: "fa-solid fa-users" },
        { label: "Paie non g&eacute;n&eacute;r&eacute;e", href: "/paie/non-generee", icon: "fa-solid fa-triangle-exclamation" },
        { label: "Sommes per&ccedil;ues", href: "/paie/sommes-percues", icon: "fa-solid fa-wallet" },
        { label: "D&eacute;l&eacute;gation de cr&eacute;dit", href: "/credits/delegation", icon: "fa-solid fa-scale-balanced" },
        { label: "&Eacute;dition des d&eacute;l&eacute;gations de cr&eacute;dits", href: "/credits/edition-delegations", icon: "fa-solid fa-file-signature" },
        { label: "&Eacute;dition des engagements", href: "/credits/edition-engagements", icon: "fa-solid fa-clipboard-check" }
      ]
    },
    {
      type: "group",
      icon: "fa-solid fa-coins",
      label: "Gestion des indemnit&eacute;s",
      links: [
        { label: "Convocations", href: "/indemnites/convocations", icon: "fa-solid fa-calendar-check" },
        { label: "Services faits", href: "/indemnites/services-faits", icon: "fa-solid fa-list-check" },
        { label: "Pi&egrave;ces justificatives", href: "/indemnites/pieces-justificatives", icon: "fa-solid fa-folder-open" },
        { label: "Accus&eacute;s de r&eacute;ception", href: "/indemnites/accuses-reception", icon: "fa-solid fa-receipt" },
        { label: "Calcul des indemnit&eacute;s", href: "/indemnites/calcul", icon: "fa-solid fa-calculator" },
        { label: "Frais de d&eacute;placement", href: "/indemnites/frais-deplacement", icon: "fa-solid fa-route" },
        { label: "G&eacute;n&eacute;ration des &eacute;tats de paie", href: "/indemnites/etats-paie", icon: "fa-solid fa-file-export" }
      ]
    },
    {
      type: "group",
      icon: "fa-solid fa-graduation-cap",
      label: "Gestion des Bourses et aides",
      links: [
        { label: "Enregistrer une demande", href: "/bourses/enregistrer-demande", icon: "fa-solid fa-file-circle-plus" },
        { label: "Valider un dossier", href: "/bourses/valider-dossier", icon: "fa-solid fa-circle-check" },
        { label: "Attribuer une aide", href: "/bourses/attribuer-aide", icon: "fa-solid fa-hand-holding-heart" }
      ]
    },
    {
      type: "group",
      icon: "fa-solid fa-gears",
      label: "Param&eacute;trage",
      links: [
        { label: "IA", href: "/parametres#ia", icon: "fa-solid fa-landmark" },
        { label: "IEF", href: "/parametres/ief", icon: "fa-solid fa-sitemap" },
        { label: "Dipl&ocirc;mes", href: "/parametres#diplomes", icon: "fa-solid fa-graduation-cap" },
        { label: "Corps", href: "/parametres#corps", icon: "fa-solid fa-users-line" },
        { label: "Cat&eacute;gories", href: "/parametres#categories", icon: "fa-solid fa-layer-group" },
        { label: "Institutions financi&egrave;res", href: "/parametres#institutions-financieres", icon: "fa-solid fa-building-columns" },
        { label: "Discipline", href: "/parametres#discipline", icon: "fa-solid fa-book-open" },
        { label: "Syndicats", href: "/parametres#syndicats", icon: "fa-solid fa-handshake" },
        { label: "Ann&eacute;e acad&eacute;mique", href: "/parametres#annee-academique", icon: "fa-solid fa-calendar-days" },
        { label: "P&eacute;riode de paie", href: "/parametres#periode-paie", icon: "fa-solid fa-calendar-week" },
        { label: "Rubrique de paie", href: "/parametres#rubrique-paie", icon: "fa-solid fa-list-ul" },
        { label: "Rubriques par corps", href: "/parametres#rubriques-corps", icon: "fa-solid fa-diagram-project" },
        { label: "Lieu de service", href: "/parametres#lieu-service", icon: "fa-solid fa-location-dot" }
      ]
    },
    {
      type: "group",
      icon: "fa-solid fa-users-gear",
      label: "Gestion Utilisateur",
      links: [
        { label: "Utilisateurs", href: "/utilisateurs", icon: "fa-solid fa-user-shield" },
        { label: "Profils / R&ocirc;les", href: "/utilisateurs/profils-roles", icon: "fa-solid fa-id-badge" },
        { label: "Permissions", href: "/utilisateurs/permissions", icon: "fa-solid fa-key" }
      ]
    }
  ];

  function ensureFontAwesome() {
    if (!document.head || document.getElementById(FONT_AWESOME_ID) || document.querySelector('link[href*="font-awesome"]')) {
      return;
    }

    var link = document.createElement("link");
    link.id = FONT_AWESOME_ID;
    link.rel = "stylesheet";
    link.href = FONT_AWESOME_HREF;
    document.head.appendChild(link);
  }

  function normalizeRoutePath(path) {
    var value = (path || "").split("?")[0].replace(/\\/g, "/");
    value = value.replace(/^https?:\/\/[^/]+/i, "");
    value = value.replace(/^\/+/, "").replace(/\/+$/, "");
    return value === "index.html" ? "" : value;
  }

  function getCurrentPage() {
    return normalizeRoutePath(window.location.pathname);
  }

  function getSidebar() {
    return document.getElementById("sidebar");
  }

  function isDesktop() {
    return window.innerWidth > 992;
  }

  function getHrefParts(href) {
    var parts = (href || "").split("#");
    return {
      page: normalizeRoutePath(parts[0]),
      hash: parts[1] ? "#" + parts[1] : ""
    };
  }

  function isSamePage(href, currentPage) {
    var parts = getHrefParts(href);
    if (!parts.page || parts.page !== currentPage) {
      return false;
    }

    if (parts.hash) {
      return window.location.hash === parts.hash;
    }

    return window.location.hash === "";
  }

  function hasSamePath(href, currentPage) {
    var parts = getHrefParts(href);
    return Boolean(parts.page && parts.page === currentPage);
  }

  function renderIcon(icon, extraClass) {
    return [
      '<span class="nav-icon' + (extraClass ? " " + extraClass : "") + '">',
      '<i class="' + icon + '" aria-hidden="true"></i>',
      "</span>"
    ].join("");
  }

  function renderSubmenuLink(link) {
    return [
      '<a href="' + link.href + '" data-page-link title="' + link.label + '" aria-label="' + link.label + '">',
      renderIcon(link.icon || "fa-solid fa-circle", "submenu-icon"),
      '<span class="submenu-label">' + link.label + "</span>",
      "</a>"
    ].join("");
  }

  function buildSidebarMenu() {
    return sidebarMenu.map(function (item) {
      if (item.type === "link") {
        return [
          '<a class="sidebar-link" href="' + item.href + '" data-page-link data-tooltip="' + item.label + '" title="' + item.label + '" aria-label="' + item.label + '">',
          renderIcon(item.icon),
          '<span class="nav-label">' + item.label + "</span>",
          "</a>"
        ].join("");
      }

      var submenu = item.links.map(renderSubmenuLink).join("");

      return [
        '<button class="sidebar-link" type="button" data-submenu-toggle data-tooltip="' + item.label + '" title="' + item.label + '" aria-label="' + item.label + '" aria-expanded="false">',
        renderIcon(item.icon),
        '<span class="nav-label">' + item.label + "</span>",
        '<span class="chevron"><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></span>',
        "</button>",
        '<div class="sidebar-submenu">',
        submenu,
        "</div>"
      ].join("");
    }).join("");
  }

  function getSidebarMarkup() {
    return [
      '<aside class="sidebar" id="sidebar" aria-label="Menu principal SICORE">',
      '<div class="sidebar-header">',
      '<a class="sidebar-logo" href="/dashboard" data-tooltip="SICORE" title="SICORE" aria-label="Accueil SICORE">',
      '<span class="sidebar-logo-mark"><img src="/assets/images/image-fcfa.png" alt="Logo SICORE"></span>',
      '<span class="sidebar-logo-text">SICORE</span>',
      "</a>",
      "</div>",
      '<nav class="sidebar-nav" aria-label="Navigation principale">',
      '<div class="sidebar-section">',
      buildSidebarMenu(),
      "</div>",
      "</nav>",
      '<div class="sidebar-footer">',
      '<div class="user-card" title="Utilisateur administrateur" aria-label="Utilisateur administrateur">',
      '<span class="avatar" aria-hidden="true"><i class="fa-solid fa-user-shield"></i></span>',
      '<div><p class="user-name">Amina Diallo</p><p class="user-role">Administrateur</p></div>',
      "</div>",
      '<button class="logout-btn" type="button" data-toast="info" data-toast-message="Session ferm&eacute;e localement." data-tooltip="D&eacute;connexion" title="D&eacute;connexion" aria-label="D&eacute;connexion">',
      '<i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span class="logout-label">D&eacute;connexion</span>',
      "</button>",
      "</div>",
      "</aside>"
    ].join("");
  }

  function renderAppShell() {
    if (!document.body.classList.contains("app-body")) {
      return;
    }

    var currentSidebar = getSidebar();
    if (currentSidebar) {
      currentSidebar.outerHTML = getSidebarMarkup();
    } else {
      document.body.insertAdjacentHTML("afterbegin", getSidebarMarkup());
    }

    document.querySelectorAll(".mobile-overlay").forEach(function (overlay) {
      overlay.remove();
    });
    document.body.insertAdjacentHTML("beforeend", '<div class="mobile-overlay" data-sidebar-close aria-hidden="true"></div>');
  }

  function setButtonIcon(button, iconClass) {
    var icon = button.querySelector("i");
    if (!icon) {
      button.innerHTML = '<i class="' + iconClass + '" aria-hidden="true"></i>';
      return;
    }
    icon.className = iconClass;
  }

  function enhanceTopbars() {
    document.querySelectorAll(".mobile-menu-btn").forEach(function (button) {
      setButtonIcon(button, "fa-solid fa-bars");
      button.setAttribute("aria-label", "Ouvrir le menu");
      button.title = "Ouvrir le menu";
    });

    document.querySelectorAll(".page-title-wrap").forEach(function (wrap) {
      if (wrap.querySelector("[data-sidebar-collapse]")) {
        return;
      }

      var button = document.createElement("button");
      button.className = "desktop-sidebar-btn";
      button.type = "button";
      button.setAttribute("data-sidebar-collapse", "");
      button.setAttribute("aria-label", "R\u00e9duire le menu");
      button.title = "R\u00e9duire le menu";
      button.innerHTML = '<i class="fa-solid fa-angles-left" aria-hidden="true"></i>';

      var mobileButton = wrap.querySelector(".mobile-menu-btn");
      if (mobileButton && mobileButton.nextSibling) {
        wrap.insertBefore(button, mobileButton.nextSibling);
      } else {
        wrap.insertBefore(button, wrap.firstChild);
      }
    });

    updateCollapseButtons();
  }

  function getTitleIconClass(titleIcon) {
    var text = titleIcon.textContent.trim().toUpperCase();
    var wrap = titleIcon.closest(".page-title-wrap");
    var heading = wrap && wrap.querySelector("h1") ? wrap.querySelector("h1").textContent.toLowerCase() : "";

    if (text === "TB" || heading.indexOf("tableau") !== -1) {
      return "fa-solid fa-gauge-high";
    }
    if (text === "IEF" || heading.indexOf("ief") !== -1) {
      return "fa-solid fa-sitemap";
    }
    if (heading.indexOf("enseignant") !== -1) {
      return "fa-solid fa-chalkboard-user";
    }
    if (heading.indexOf("param") !== -1) {
      return "fa-solid fa-gears";
    }
    return "";
  }

  function enhanceTitleIcons() {
    document.querySelectorAll(".title-icon").forEach(function (titleIcon) {
      if (titleIcon.querySelector("i")) {
        return;
      }

      var iconClass = getTitleIconClass(titleIcon);
      if (!iconClass) {
        return;
      }

      titleIcon.innerHTML = '<i class="' + iconClass + '" aria-hidden="true"></i>';
      titleIcon.setAttribute("aria-hidden", "true");
    });
  }

  function enhanceIconActions() {
    var iconMap = {
      Voir: "fa-solid fa-eye",
      Modifier: "fa-solid fa-pen-to-square",
      Supprimer: "fa-solid fa-trash-can"
    };

    document.querySelectorAll(".icon-action").forEach(function (button) {
      var title = button.getAttribute("title") || button.getAttribute("aria-label") || "";
      var iconClass = iconMap[title];
      if (!iconClass) {
        return;
      }

      button.setAttribute("aria-label", title);
      if (!button.querySelector("i")) {
        button.innerHTML = '<i class="' + iconClass + '" aria-hidden="true"></i>';
      }
    });
  }

  function saveSidebarPreference(collapsed) {
    try {
      localStorage.setItem(SIDEBAR_STORAGE_KEY, collapsed ? "true" : "false");
    } catch (error) {
      return false;
    }
    return true;
  }

  function restoreSidebarPreference() {
    var collapsed = false;
    try {
      collapsed = localStorage.getItem(SIDEBAR_STORAGE_KEY) === "true";
    } catch (error) {
      collapsed = false;
    }

    document.body.classList.toggle("sidebar-collapsed", collapsed && isDesktop());
    updateCollapseButtons();
  }

  function updateCollapseButtons() {
    var collapsed = document.body.classList.contains("sidebar-collapsed");
    document.querySelectorAll("[data-sidebar-collapse]").forEach(function (button) {
      button.classList.toggle("is-collapsed", collapsed);
      button.setAttribute("aria-label", collapsed ? "Afficher le menu" : "R\u00e9duire le menu");
      button.title = collapsed ? "Afficher le menu" : "R\u00e9duire le menu";
      setButtonIcon(button, collapsed ? "fa-solid fa-angles-right" : "fa-solid fa-angles-left");
    });
  }

  function collapseSidebar(forceCollapsed) {
    var shouldCollapse = typeof forceCollapsed === "boolean"
      ? forceCollapsed
      : !document.body.classList.contains("sidebar-collapsed");

    document.body.classList.toggle("sidebar-collapsed", shouldCollapse && isDesktop());
    saveSidebarPreference(shouldCollapse && isDesktop());
    updateCollapseButtons();
    hideSidebarTooltip();
  }

  function openMobileSidebar() {
    var sidebar = getSidebar();
    if (!sidebar) {
      return;
    }
    sidebar.classList.add("open");
    document.body.classList.add("sidebar-open");
  }

  function closeMobileSidebar() {
    var sidebar = getSidebar();
    if (!sidebar) {
      return;
    }
    sidebar.classList.remove("open");
    document.body.classList.remove("sidebar-open");
  }

  function toggleSidebar(forceOpen) {
    var sidebar = getSidebar();
    if (!sidebar) {
      return;
    }

    if (isDesktop()) {
      collapseSidebar();
      return;
    }

    var shouldOpen = typeof forceOpen === "boolean" ? forceOpen : !sidebar.classList.contains("open");
    if (shouldOpen) {
      openMobileSidebar();
    } else {
      closeMobileSidebar();
    }
  }

  function setSubmenuState(button, expanded) {
    var submenu = button ? button.nextElementSibling : null;
    if (!button || !submenu || !submenu.classList.contains("sidebar-submenu")) {
      return;
    }

    button.classList.toggle("expanded", expanded);
    button.setAttribute("aria-expanded", expanded ? "true" : "false");
    submenu.classList.toggle("open", expanded);
  }

  function toggleSubmenu(button) {
    if (!button || document.body.classList.contains("sidebar-collapsed")) {
      return;
    }

    setSubmenuState(button, !button.classList.contains("expanded"));
  }

  function setActiveMenu() {
    var currentPage = getCurrentPage();
    var links = document.querySelectorAll("[data-page-link]");

    document.querySelectorAll("[data-submenu-toggle]").forEach(function (button) {
      button.classList.remove("active");
      setSubmenuState(button, false);
    });

    links.forEach(function (link) {
      var target = link.getAttribute("href");
      var isActive = isSamePage(target, currentPage);
      var isRelated = hasSamePath(target, currentPage);
      link.classList.toggle("active", isActive);

      if (isActive || isRelated) {
        var submenu = link.closest(".sidebar-submenu");
        if (submenu) {
          var trigger = submenu.previousElementSibling;
          if (trigger) {
            setSubmenuState(trigger, true);
            trigger.classList.add("active");
          }
        }
      }
    });
  }

  function filterTable(input) {
    var selector = input.getAttribute("data-table-filter");
    var table = selector ? document.querySelector(selector) : null;
    if (!table) {
      return;
    }

    var query = input.value.trim().toLowerCase();
    var rows = table.querySelectorAll("tbody tr");
    var visible = 0;

    rows.forEach(function (row) {
      var matches = row.textContent.toLowerCase().indexOf(query) !== -1;
      row.classList.toggle("is-hidden", !matches);
      if (matches) {
        visible += 1;
      }
    });

    var card = table.closest(".table-card");
    var empty = card ? card.querySelector(".empty-message") : null;
    if (empty) {
      empty.classList.toggle("show", visible === 0);
    }
  }

  function syncPasswordToggle(button) {
    var selector = button.getAttribute("data-password-toggle");
    var field = selector ? document.querySelector(selector) : null;
    if (!field) {
      return;
    }

    var isVisible = field.type === "text";
    button.setAttribute("aria-label", isVisible ? "Masquer le mot de passe" : "Afficher le mot de passe");
    button.setAttribute("aria-pressed", isVisible ? "true" : "false");
    setButtonIcon(button, isVisible ? "fa-solid fa-eye-slash" : "fa-solid fa-eye");
  }

  function togglePassword(button) {
    var selector = button.getAttribute("data-password-toggle");
    var field = selector ? document.querySelector(selector) : null;
    if (!field) {
      return;
    }
    field.type = field.type === "password" ? "text" : "password";
    syncPasswordToggle(button);
    field.focus();
  }

  function notify(type, message) {
    if (typeof window.showToast === "function") {
      window.showToast(type, message);
      return;
    }
    window.alert(message);
  }

  function validateRequiredFields(form) {
    var fields = form.querySelectorAll("[required]");
    var valid = true;
    var firstInvalid = null;

    fields.forEach(function (field) {
      var isEmpty = !field.value || field.value.trim() === "";
      field.classList.toggle("is-invalid", isEmpty);
      field.setAttribute("aria-invalid", isEmpty ? "true" : "false");
      if (isEmpty && !firstInvalid) {
        firstInvalid = field;
      }
      valid = valid && !isEmpty;
    });

    var status = form.querySelector("[data-form-status]");
    if (status) {
      var successMessage = form.getAttribute("data-success-message") || "Formulaire valide. Donn\u00e9es pr\u00eates \u00e0 \u00eatre enregistr\u00e9es.";
      var errorMessage = form.getAttribute("data-error-message") || "Veuillez renseigner tous les champs obligatoires.";
      status.classList.toggle("success", valid);
      status.textContent = valid ? successMessage : errorMessage;
    }

    if (!valid && firstInvalid) {
      firstInvalid.focus();
    }

    return valid;
  }

  function setupPagination() {
    document.querySelectorAll(".pagination").forEach(function (pagination) {
      if (pagination.dataset.bound === "true") {
        return;
      }
      pagination.dataset.bound = "true";
      pagination.addEventListener("click", function (event) {
        var button = event.target.closest(".page-btn");
        if (!button || button.disabled) {
          return;
        }
        event.preventDefault();
        if (button.hasAttribute("data-page-number")) {
          pagination.querySelectorAll(".page-btn").forEach(function (item) {
            item.classList.remove("active");
          });
          button.classList.add("active");
        }
      });
    });
  }

  function setupForms() {
    document.querySelectorAll("[data-validate-form]").forEach(function (form) {
      if (form.dataset.bound === "true" || form.hasAttribute("data-teacher-wizard")) {
        return;
      }
      form.dataset.bound = "true";
      form.addEventListener("submit", function (event) {
        event.preventDefault();
        if (validateRequiredFields(form)) {
          notify("success", form.getAttribute("data-success-message") || "Op\u00e9ration effectu\u00e9e avec succ\u00e8s.");
        } else {
          notify("error", form.getAttribute("data-error-message") || "Veuillez remplir les champs obligatoires.");
        }
      });

      form.querySelectorAll("[required]").forEach(function (field) {
        field.addEventListener("input", function () {
          if (field.classList.contains("is-invalid") && field.value.trim() !== "") {
            field.classList.remove("is-invalid");
            field.setAttribute("aria-invalid", "false");
          }
        });
      });
    });
  }

  function ensureConfirmModal() {
    var existing = document.querySelector("[data-confirm-modal]");
    if (existing) {
      return existing;
    }

    var modal = document.createElement("div");
    modal.className = "confirm-backdrop";
    modal.setAttribute("data-confirm-modal", "");
    modal.hidden = true;
    modal.innerHTML = [
      '<div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">',
      '<h2 id="confirmTitle">Confirmation</h2>',
      '<p data-confirm-message>Confirmer cette action ?</p>',
      '<div class="confirm-actions">',
      '<button class="btn-secondary" type="button" data-confirm-cancel>Annuler</button>',
      '<button class="btn-primary" type="button" data-confirm-accept>Confirmer</button>',
      "</div>",
      "</div>"
    ].join("");
    document.body.appendChild(modal);
    return modal;
  }

  function confirmAction(message, onConfirm) {
    var modal = ensureConfirmModal();
    var messageNode = modal.querySelector("[data-confirm-message]");
    var cancelButton = modal.querySelector("[data-confirm-cancel]");
    var acceptButton = modal.querySelector("[data-confirm-accept]");

    function close() {
      modal.hidden = true;
      cancelButton.removeEventListener("click", close);
      acceptButton.removeEventListener("click", accept);
    }

    function accept() {
      close();
      if (typeof onConfirm === "function") {
        onConfirm();
      }
    }

    messageNode.textContent = message || "Confirmer cette action ?";
    modal.hidden = false;
    cancelButton.addEventListener("click", close);
    acceptButton.addEventListener("click", accept);
    cancelButton.focus();
  }

  function setupConfirmations() {
    ensureConfirmModal();

    document.querySelectorAll(".icon-action[title='Supprimer']").forEach(function (button) {
      if (!button.hasAttribute("data-confirm")) {
        button.setAttribute("data-confirm", "Voulez-vous vraiment supprimer cet \u00e9l\u00e9ment ?");
        button.setAttribute("data-success-message", "\u00c9l\u00e9ment supprim\u00e9 avec succ\u00e8s.");
      }
    });

    document.querySelectorAll("[data-confirm]").forEach(function (trigger) {
      if (trigger.dataset.confirmBound === "true") {
        return;
      }
      trigger.dataset.confirmBound = "true";
      trigger.addEventListener("click", function (event) {
        event.preventDefault();
        confirmAction(trigger.getAttribute("data-confirm"), function () {
          notify("success", trigger.getAttribute("data-success-message") || "Action confirm\u00e9e.");
        });
      });
    });
  }

  function setupToasts() {
    document.querySelectorAll("[data-toast]").forEach(function (trigger) {
      if (trigger.dataset.toastBound === "true") {
        return;
      }
      trigger.dataset.toastBound = "true";
      trigger.addEventListener("click", function () {
        notify(trigger.getAttribute("data-toast"), trigger.getAttribute("data-toast-message"));
      });
    });
  }

  function setupDynamicCalculations() {
    document.querySelectorAll("[data-calculate-indemnity]").forEach(function (button) {
      if (button.dataset.bound === "true") {
        return;
      }
      button.dataset.bound = "true";
      button.addEventListener("click", function () {
        var result = document.querySelector("[data-indemnity-result]");
        if (result) {
          result.hidden = false;
          result.innerHTML = "<strong>Montant simul\u00e9 :</strong> 185 000 FCFA pour la p\u00e9riode s\u00e9lectionn\u00e9e.";
        }
        notify("success", "Calcul des indemnit\u00e9s effectu\u00e9.");
      });
    });
  }

  function getSidebarTooltipLabel(element) {
    return element.getAttribute("data-tooltip") || element.getAttribute("aria-label") || element.title || "";
  }

  function hideSidebarTooltip() {
    if (sidebarTooltip) {
      sidebarTooltip.remove();
      sidebarTooltip = null;
    }
  }

  function showSidebarTooltip(element) {
    var label = getSidebarTooltipLabel(element);
    if (!label || !isDesktop() || !document.body.classList.contains("sidebar-collapsed")) {
      hideSidebarTooltip();
      return;
    }

    if (!sidebarTooltip) {
      sidebarTooltip = document.createElement("div");
      sidebarTooltip.className = "sidebar-tooltip";
      sidebarTooltip.setAttribute("role", "tooltip");
      document.body.appendChild(sidebarTooltip);
    }

    var rect = element.getBoundingClientRect();
    sidebarTooltip.textContent = label;
    sidebarTooltip.style.left = Math.round(rect.right + 12) + "px";
    sidebarTooltip.style.top = Math.round(rect.top + rect.height / 2) + "px";
  }

  function setupSidebarTooltips() {
    document.querySelectorAll(".sidebar-link, .sidebar-logo, .logout-btn").forEach(function (element) {
      if (element.dataset.tooltipBound === "true") {
        return;
      }
      element.dataset.tooltipBound = "true";
      element.addEventListener("mouseenter", function () {
        showSidebarTooltip(element);
      });
      element.addEventListener("focus", function () {
        showSidebarTooltip(element);
      });
      element.addEventListener("mouseleave", hideSidebarTooltip);
      element.addEventListener("blur", hideSidebarTooltip);
    });
  }

  function bindEvents() {
    document.querySelectorAll("[data-sidebar-toggle]").forEach(function (button) {
      if (button.dataset.bound === "true") {
        return;
      }
      button.dataset.bound = "true";
      button.addEventListener("click", function () {
        toggleSidebar();
      });
    });

    document.querySelectorAll("[data-sidebar-collapse]").forEach(function (button) {
      if (button.dataset.bound === "true") {
        return;
      }
      button.dataset.bound = "true";
      button.addEventListener("click", function () {
        collapseSidebar();
      });
    });

    document.querySelectorAll("[data-sidebar-close]").forEach(function (button) {
      if (button.dataset.bound === "true") {
        return;
      }
      button.dataset.bound = "true";
      button.addEventListener("click", closeMobileSidebar);
    });

    document.querySelectorAll("[data-submenu-toggle]").forEach(function (button) {
      if (button.dataset.bound === "true") {
        return;
      }
      button.dataset.bound = "true";
      button.addEventListener("click", function () {
        toggleSubmenu(button);
      });
    });

    document.querySelectorAll("[data-page-link]").forEach(function (link) {
      if (link.dataset.pageBound === "true") {
        return;
      }
      link.dataset.pageBound = "true";
      link.addEventListener("click", function () {
        if (!isDesktop()) {
          closeMobileSidebar();
        }
      });
    });

    document.querySelectorAll("[data-table-filter]").forEach(function (input) {
      if (input.dataset.bound === "true") {
        return;
      }
      input.dataset.bound = "true";
      input.addEventListener("input", function () {
        filterTable(input);
      });
    });

    document.querySelectorAll("[data-password-toggle]").forEach(function (button) {
      syncPasswordToggle(button);
      if (button.dataset.bound === "true") {
        return;
      }
      button.dataset.bound = "true";
      button.addEventListener("click", function () {
        togglePassword(button);
      });
    });
  }

  function refreshDynamicContent() {
    ensureFontAwesome();
    enhanceTopbars();
    enhanceTitleIcons();
    enhanceIconActions();
    setActiveMenu();
    bindEvents();
    setupSidebarTooltips();
    setupForms();
    setupPagination();
    setupConfirmations();
    setupToasts();
    setupDynamicCalculations();
  }

  window.addEventListener("resize", function () {
    hideSidebarTooltip();
    if (isDesktop()) {
      closeMobileSidebar();
      restoreSidebarPreference();
    } else {
      document.body.classList.remove("sidebar-collapsed");
      updateCollapseButtons();
    }
  });

  window.addEventListener("scroll", hideSidebarTooltip, true);
  window.addEventListener("hashchange", setActiveMenu);

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closeMobileSidebar();
      hideSidebarTooltip();
      var modal = document.querySelector("[data-confirm-modal]");
      if (modal && !modal.hidden) {
        modal.hidden = true;
      }
    }
  });

  document.addEventListener("DOMContentLoaded", function () {
    ensureFontAwesome();
    renderAppShell();
    restoreSidebarPreference();
    refreshDynamicContent();
  });

  window.toggleSidebar = toggleSidebar;
  window.collapseSidebar = collapseSidebar;
  window.openMobileSidebar = openMobileSidebar;
  window.closeMobileSidebar = closeMobileSidebar;
  window.saveSidebarPreference = saveSidebarPreference;
  window.restoreSidebarPreference = restoreSidebarPreference;
  window.toggleSubmenu = toggleSubmenu;
  window.setActiveMenu = setActiveMenu;
  window.filterTable = filterTable;
  window.togglePassword = togglePassword;
  window.validateRequiredFields = validateRequiredFields;
  window.confirmAction = confirmAction;
  window.SICOREApp = {
    refresh: refreshDynamicContent,
    renderAppShell: renderAppShell,
    notify: notify
  };
})();


