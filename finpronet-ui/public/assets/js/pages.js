(function () {
  "use strict";

  function badge(label, variant) {
    return '<span class="badge ' + (variant || "badge-primary") + '">' + label + "</span>";
  }

  function actions(items) {
    return '<div class="table-actions-inline">' + items.map(function (item) {
      var confirm = item.confirm ? ' data-confirm="' + item.confirm + '"' : "";
      var success = item.success ? ' data-success-message="' + item.success + '"' : "";
      return '<button class="table-action ' + (item.variant || "") + '" type="button"' + confirm + success + ">" + item.label + "</button>";
    }).join("") + "</div>";
  }

  function decodeText(value) {
    var holder = document.createElement("div");
    holder.innerHTML = value == null ? "" : String(value);
    return holder.textContent || "";
  }

  function normalizeText(value) {
    var text = decodeText(value);
    if (text.normalize) {
      text = text.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }
    return text.toLowerCase().replace(/\s+/g, " ").trim();
  }

  // Associe un libelle de filtre ("Structure") a la colonne du tableau
  // qui lui correspond ("Structure beneficiaire") pour rendre le filtre operant.
  function matchFilterColumn(filter, columns, used) {
    var target = normalizeText(filter);
    if (!target) {
      return -1;
    }

    var normalized = columns.map(function (column) {
      return normalizeText(column);
    });

    function search(test) {
      for (var index = 0; index < normalized.length; index += 1) {
        if (used[index] || normalized[index] === "actions") {
          continue;
        }
        if (test(normalized[index])) {
          return index;
        }
      }
      return -1;
    }

    var found = search(function (column) {
      return column === target;
    });

    if (found === -1) {
      found = search(function (column) {
        return column.indexOf(target) !== -1;
      });
    }

    if (found === -1) {
      found = search(function (column) {
        return target.indexOf(column) !== -1;
      });
    }

    return found;
  }

  function commonStats(totalLabel) {
    return [
      { label: totalLabel || "Dossiers", value: "128", note: "P&eacute;riode active", icon: "fa-solid fa-folder-open", color: "green" },
      { label: "Valid&eacute;s", value: "96", note: "75% trait&eacute;s", icon: "fa-solid fa-circle-check", color: "blue" },
      { label: "En attente", value: "24", note: "&Agrave; suivre", icon: "fa-solid fa-clock", color: "yellow" },
      { label: "Rejet&eacute;s", value: "8", note: "Correction requise", icon: "fa-solid fa-circle-xmark", color: "red" }
    ];
  }

  var statIconMap = {
    AB: "fa-solid fa-user-xmark",
    AD: "fa-solid fa-user-tie",
    AR: "fa-solid fa-receipt",
    AT: "fa-solid fa-clock",
    BQ: "fa-solid fa-building-columns",
    BR: "fa-solid fa-sack-dollar",
    BS: "fa-solid fa-file-lines",
    CI: "fa-solid fa-calculator",
    CS: "fa-solid fa-people-group",
    CT: "fa-solid fa-list-check",
    DC: "fa-solid fa-scale-balanced",
    DS: "fa-solid fa-folder-open",
    EC: "fa-solid fa-users",
    ED: "fa-solid fa-file-signature",
    EG: "fa-solid fa-clipboard-check",
    EN: "fa-solid fa-users",
    EP: "fa-solid fa-file-invoice-dollar",
    ES: "fa-solid fa-file-invoice-dollar",
    EX: "fa-solid fa-user-shield",
    FC: "fa-solid fa-money-bill-wave",
    FP: "fa-solid fa-lock",
    GC: "fa-solid fa-calendar-check",
    MD: "fa-solid fa-wallet",
    ME: "fa-solid fa-file-contract",
    MC: "fa-solid fa-coins",
    NG: "fa-solid fa-triangle-exclamation",
    NP: "fa-solid fa-hand-holding-dollar",
    OK: "fa-solid fa-circle-check",
    PE: "fa-solid fa-calendar-week",
    PI: "fa-solid fa-sitemap",
    PJ: "fa-solid fa-folder-open",
    RJ: "fa-solid fa-circle-xmark",
    RR: "fa-solid fa-clock-rotate-left",
    RT: "fa-solid fa-money-bill-transfer",
    SB: "fa-solid fa-building-columns",
    SF: "fa-solid fa-list-check",
    SP: "fa-solid fa-wallet",
    SR: "fa-solid fa-scale-balanced",
    TP: "fa-solid fa-gears",
    VD: "fa-solid fa-circle-check"
  };

  var pageIconMap = {
    "paie-etats-presence": "fa-solid fa-clipboard-user",
    "paie-avance-tabaski": "fa-solid fa-hand-holding-dollar",
    "paie-retenue-tabaski": "fa-solid fa-money-bill-transfer",
    "paie-retenues-rappel": "fa-solid fa-clock-rotate-left",
    "paie-exemptions": "fa-solid fa-user-shield",
    "paie-travaux-periodiques": "fa-solid fa-gears",
    "paie-recap-banque": "fa-solid fa-building-columns",
    "paie-cotisations-sociales": "fa-solid fa-people-group",
    "paie-etat-salaires": "fa-solid fa-file-invoice-dollar",
    "paie-elements-saisie-dashboard": "fa-solid fa-chart-line",
    "paie-generee-ief": "fa-solid fa-sitemap",
    "paie-fermeture-periode": "fa-solid fa-lock",
    "paie-edition-salaires-banque": "fa-solid fa-building-columns",
    "paie-bulletins": "fa-solid fa-file-lines",
    "paie-effectifs-corps": "fa-solid fa-users",
    "paie-non-generee": "fa-solid fa-triangle-exclamation",
    "paie-sommes-percues": "fa-solid fa-wallet",
    "credit-delegation": "fa-solid fa-scale-balanced",
    "credit-edition-delegations": "fa-solid fa-file-signature",
    "credit-edition-engagements": "fa-solid fa-clipboard-check",
    "indemnites-convocations": "fa-solid fa-calendar-check",
    "indemnites-services-faits": "fa-solid fa-list-check",
    "indemnites-pieces-justificatives": "fa-solid fa-folder-open",
    "indemnites-accuses-reception": "fa-solid fa-receipt",
    "indemnites-calcul": "fa-solid fa-calculator",
    "indemnites-frais-deplacement": "fa-solid fa-route",
    "indemnites-etats-paie": "fa-solid fa-file-export",
    "bourses-enregistrer-demande": "fa-solid fa-file-circle-plus",
    "bourses-valider-dossier": "fa-solid fa-circle-check",
    "bourses-attribuer-aide": "fa-solid fa-hand-holding-heart",
    utilisateurs: "fa-solid fa-user-shield",
    "profils-roles": "fa-solid fa-id-badge",
    permissions: "fa-solid fa-key"
  };

  function renderIcon(iconName, fallbackMap) {
    var iconClass = iconName && iconName.indexOf("fa-") !== -1 ? iconName : fallbackMap[iconName];
    return iconClass ? '<i class="' + iconClass + '" aria-hidden="true"></i>' : iconName;
  }

  var pages = {
    "paie-etats-presence": {
      title: "&Eacute;tats de pr&eacute;sence",
      icon: "EP",
      breadcrumb: "Gestion de la paie &gt; &Eacute;tats de pr&eacute;sence",
      objectives: [
        "Calculer les retenues liees aux absences et aux retards.",
        "Valider l'etat de presence du mois.",
        "Transmettre l'etat de presence au traitement de la paie."
      ],
      stats: [
        { label: "Enseignants suivis", value: "1 247", note: "Mois courant", icon: "EN", color: "green" },
        { label: "Absences", value: "86", note: "12 nouvelles", icon: "AB", color: "red" },
        { label: "Retards", value: "41", note: "Controle IA", icon: "RT", color: "yellow" },
        { label: "Retenues estimees", value: "2,4M", note: "FCFA", icon: "FC", color: "blue" }
      ],
      filters: ["Mois", "IA", "IEF"],
      actions: ["Nouvel etat", "Valider", "Transmettre"],
      columns: ["Enseignant", "Mois", "IA", "IEF", "Absences", "Retards", "Retenue estimee", "Statut", "Actions"],
      rows: [
        ["Mamadou DIOP", "Juin 2026", "IA Dakar", "IEF Dakar Nord", "2", "1", "18 500 FCFA", badge("Brouillon", "badge-pending"), actions([{ label: "Voir" }, { label: "Modifier" }, { label: "Valider", variant: "primary", confirm: "Valider cet etat de presence ?", success: "Etat de presence valide." }])],
        ["Aissatou FALL", "Juin 2026", "IA Thies", "IEF Thies", "0", "2", "5 000 FCFA", badge("Valide", "badge-active"), actions([{ label: "Voir" }, { label: "Transmettre", variant: "primary", confirm: "Transmettre cet etat de presence au traitement de la paie ?", success: "Etat transmis a la paie." }])],
        ["Ibrahima SOW", "Juin 2026", "IA Saint-Louis", "IEF Saint-Louis", "1", "0", "8 750 FCFA", badge("Transmis", "badge-primary"), actions([{ label: "Voir" }])],
        ["Fatou GUEYE", "Juin 2026", "IA Kaolack", "IEF Kaolack", "3", "3", "32 000 FCFA", badge("Rejete", "badge-inactive"), actions([{ label: "Voir" }, { label: "Modifier" }])]
      ]
    },
    "paie-avance-tabaski": {
      title: "Avance Tabaski",
      icon: "AT",
      breadcrumb: "Gestion de la paie &gt; Avance Tabaski",
      stats: commonStats("Demandes eligibles"),
      filters: ["Periode", "IA", "Statut"],
      actions: ["Ajouter une avance", "Valider", "Exporter"],
      columns: ["Enseignant", "Montant avance", "Periode", "Statut", "Actions"],
      rows: [
        ["Awa NDIAYE", "250 000 FCFA", "Tabaski 2026", badge("Eligible", "badge-active"), actions([{ label: "Voir" }, { label: "Valider", variant: "primary" }])],
        ["Cheikh BA", "200 000 FCFA", "Tabaski 2026", badge("En attente", "badge-pending"), actions([{ label: "Voir" }, { label: "Modifier" }])],
        ["Mariama SARR", "250 000 FCFA", "Tabaski 2026", badge("Valide", "badge-active"), actions([{ label: "Voir" }, { label: "Exporter" }])]
      ]
    },
    "paie-retenue-tabaski": {
      title: "Retenue Tabaski",
      icon: "RT",
      breadcrumb: "Gestion de la paie &gt; Retenue Tabaski",
      stats: commonStats("Retenues planifiees"),
      filters: ["Periode", "IA", "Statut"],
      actions: ["Nouvelle retenue", "Exporter"],
      columns: ["Enseignant", "Montant total avance", "Montant retenu", "Echeances", "Solde restant", "Statut", "Actions"],
      rows: [
        ["Awa NDIAYE", "250 000 FCFA", "50 000 FCFA", "1 / 5", "200 000 FCFA", badge("En cours", "badge-pending"), actions([{ label: "Voir" }])],
        ["Cheikh BA", "200 000 FCFA", "40 000 FCFA", "2 / 5", "120 000 FCFA", badge("En cours", "badge-pending"), actions([{ label: "Voir" }, { label: "Modifier" }])],
        ["Mariama SARR", "250 000 FCFA", "250 000 FCFA", "5 / 5", "0 FCFA", badge("Solde", "badge-active"), actions([{ label: "Voir" }])]
      ]
    },
    "paie-retenues-rappel": {
      title: "Retenues rappel",
      icon: "RR",
      breadcrumb: "Gestion de la paie &gt; Retenues rappel",
      stats: commonStats("Rappels traites"),
      filters: ["Type de rappel", "Periode", "Statut"],
      actions: ["Nouvelle retenue", "Exporter"],
      columns: ["Enseignant", "Type de rappel", "Montant rappel", "Montant retenu", "Justification", "Statut", "Actions"],
      rows: [
        ["Moussa DIOUF", "Regularisation indice", "120 000 FCFA", "30 000 FCFA", "Trop-percu", badge("Valide", "badge-active"), actions([{ label: "Voir" }])],
        ["Ndeye DIOP", "Absence regularisee", "75 000 FCFA", "15 000 FCFA", "Echeancier", badge("En attente", "badge-pending"), actions([{ label: "Voir" }, { label: "Valider", variant: "primary" }])]
      ]
    },
    "paie-exemptions": {
      title: "Exemption d&rsquo;avance ou de retenue par enseignant",
      icon: "EX",
      breadcrumb: "Gestion de la paie &gt; Exemption par enseignant",
      stats: commonStats("Exemptions"),
      filters: ["Type", "Periode", "Statut"],
      actions: ["Nouvelle exemption", "Exporter"],
      columns: ["Enseignant", "Type d'exemption", "Motif", "Periode", "Piece justificative", "Statut", "Actions"],
      rows: [
        ["Abdoulaye FAYE", "Avance", "Situation sociale", "Juin 2026", "Jointe", badge("A valider", "badge-pending"), actions([{ label: "Voir" }, { label: "Valider", variant: "primary" }, { label: "Rejeter", variant: "danger" }])],
        ["Fatou KANE", "Retenue", "Decision administrative", "Juin 2026", "Jointe", badge("Valide", "badge-active"), actions([{ label: "Voir" }])]
      ]
    },
    "paie-travaux-periodiques": {
      title: "Travaux p&eacute;riodiques",
      icon: "TP",
      breadcrumb: "Gestion de la paie &gt; Travaux p&eacute;riodiques",
      stats: commonStats("Operations"),
      filters: ["Periode", "Type", "Statut"],
      actions: ["Lancer un traitement", "Exporter"],
      columns: ["Traitement", "Periode", "Lance par", "Date", "Statut", "Actions"],
      rows: [
        ["Preparation paie", "Juin 2026", "Amina Diallo", "20/06/2026", badge("Termine", "badge-active"), actions([{ label: "Voir" }])],
        ["Controle retenues", "Juin 2026", "Amina Diallo", "21/06/2026", badge("En cours", "badge-pending"), actions([{ label: "Voir" }, { label: "Relancer" }])]
      ]
    },
    "paie-recap-banque": {
      title: "&Eacute;tat r&eacute;capitulatif par banque",
      icon: "BQ",
      breadcrumb: "Gestion de la paie &gt; R&eacute;capitulatif par banque",
      stats: commonStats("Banques"),
      filters: ["Periode", "Banque", "Statut"],
      actions: ["Generer", "Exporter"],
      columns: ["Banque", "Enseignants", "Total brut", "Total retenues", "Net a payer", "Statut", "Actions"],
      rows: [
        ["BHS", "420", "182 000 000 FCFA", "24 000 000 FCFA", "158 000 000 FCFA", badge("Pret", "badge-active"), actions([{ label: "Voir" }, { label: "Exporter" }])],
        ["CBAO", "315", "139 000 000 FCFA", "18 000 000 FCFA", "121 000 000 FCFA", badge("Controle", "badge-pending"), actions([{ label: "Voir" }])]
      ]
    },
    "paie-cotisations-sociales": {
      title: "Cotisations sociales",
      icon: "CS",
      breadcrumb: "Gestion de la paie &gt; Cotisations sociales",
      stats: commonStats("Cotisations"),
      filters: ["Periode", "Organisme", "Statut"],
      actions: ["Calculer", "Exporter"],
      columns: ["Organisme", "Base", "Taux", "Montant", "Periode", "Statut", "Actions"],
      rows: [
        ["IPRES", "485 000 000 FCFA", "14%", "67 900 000 FCFA", "Juin 2026", badge("Calcule", "badge-active"), actions([{ label: "Voir" }])],
        ["CSS", "485 000 000 FCFA", "7%", "33 950 000 FCFA", "Juin 2026", badge("Controle", "badge-pending"), actions([{ label: "Voir" }, { label: "Valider", variant: "primary" }])]
      ]
    },
    "paie-etat-salaires": {
      title: "&Eacute;tat des salaires",
      icon: "ES",
      breadcrumb: "Gestion de la paie &gt; &Eacute;tat des salaires",
      objectives: ["Calculer le salaire brut."],
      helpTitle: "Aide : comment le salaire brut est calcule ?",
      helpText: "Le salaire brut est calcule a partir du traitement indiciaire, des indemnites, des elements variables et des eventuels rappels, avant application des retenues sociales, fiscales ou administratives.",
      stats: commonStats("Salaires calcules"),
      filters: ["Periode", "IA", "IEF"],
      actions: ["Calculer", "Exporter"],
      columns: ["Enseignant", "Traitement indiciaire", "Indemnites", "Elements variables", "Salaire brut", "Statut", "Actions"],
      rows: [
        ["Mamadou DIOP", "310 000 FCFA", "85 000 FCFA", "25 000 FCFA", "420 000 FCFA", badge("Calcule", "badge-active"), actions([{ label: "Voir" }])],
        ["Aissatou FALL", "295 000 FCFA", "78 000 FCFA", "0 FCFA", "373 000 FCFA", badge("A verifier", "badge-pending"), actions([{ label: "Voir" }, { label: "Valider", variant: "primary" }])]
      ]
    },
    "paie-elements-saisie-dashboard": {
      title: "Tableau de bord des &eacute;l&eacute;ments de saisie",
      icon: "DS",
      breadcrumb: "Gestion de la paie &gt; El&eacute;ments de saisie",
      stats: [
        { label: "Elements saisis", value: "528", note: "Mois courant", icon: "ES", color: "green" },
        { label: "Valides", value: "391", note: "74%", icon: "OK", color: "blue" },
        { label: "En attente", value: "102", note: "A traiter", icon: "AT", color: "yellow" },
        { label: "Rejetes", value: "35", note: "Correction", icon: "RJ", color: "red" }
      ],
      chart: ["Jan", "Fev", "Mar", "Avr", "Mai", "Juin"],
      filters: ["Periode", "Type", "Statut"],
      actions: ["Nouvel element", "Exporter"],
      columns: ["Date", "Type", "Enseignant", "Montant", "Statut", "Actions"],
      rows: [
        ["21/06/2026", "Retenue absence", "Mamadou DIOP", "18 500 FCFA", badge("Valide", "badge-active"), actions([{ label: "Voir" }])],
        ["22/06/2026", "Rappel", "Aissatou FALL", "42 000 FCFA", badge("En attente", "badge-pending"), actions([{ label: "Voir" }, { label: "Valider", variant: "primary" }])]
      ]
    },
    "paie-generee-ief": {
      title: "Paie g&eacute;n&eacute;r&eacute;e par IEF",
      icon: "PI",
      breadcrumb: "Gestion de la paie &gt; Paie g&eacute;n&eacute;r&eacute;e par IEF",
      stats: [
        { label: "Total enseignants", value: "1 247", note: "Toutes IEF", icon: "EN", color: "green" },
        { label: "Total brut", value: "485M", note: "FCFA", icon: "BR", color: "blue" },
        { label: "Total retenues", value: "72M", note: "FCFA", icon: "RT", color: "red" },
        { label: "Net a payer", value: "413M", note: "FCFA", icon: "NP", color: "green" }
      ],
      filters: ["Periode", "IA", "IEF"],
      actions: ["Consulter", "Exporter"],
      columns: ["IEF", "Enseignants", "Total brut", "Total retenues", "Net a payer", "Statut generation", "Actions"],
      rows: [
        ["IEF Dakar Nord", "215", "83 000 000 FCFA", "12 000 000 FCFA", "71 000 000 FCFA", badge("Generee", "badge-active"), actions([{ label: "Consulter" }, { label: "Exporter" }])],
        ["IEF Thies", "184", "70 000 000 FCFA", "9 500 000 FCFA", "60 500 000 FCFA", badge("Controle", "badge-pending"), actions([{ label: "Consulter" }])]
      ]
    },
    "paie-fermeture-periode": {
      title: "Fermer la p&eacute;riode de paie",
      icon: "FP",
      breadcrumb: "Gestion de la paie &gt; Fermeture de p&eacute;riode",
      sensitive: true,
      objectives: [
        "Controler les paies generees avant fermeture.",
        "Verifier les paies non generees.",
        "Verrouiller la periode apres confirmation."
      ],
      stats: [
        { label: "Periode en cours", value: "06/2026", note: "Ouverte", icon: "PE", color: "blue" },
        { label: "Paies generees", value: "1 198", note: "96%", icon: "OK", color: "green" },
        { label: "Paies non generees", value: "49", note: "A regulariser", icon: "NG", color: "red" },
        { label: "Controles", value: "7/8", note: "Avant fermeture", icon: "CT", color: "yellow" }
      ],
      filters: ["Periode", "IA", "Controle"],
      actions: ["Verifier", "Exporter"],
      closePeriod: true,
      columns: ["Controle", "Resultat", "Statut", "Actions"],
      rows: [
        ["Paies generees", "1 198 dossiers", badge("Conforme", "badge-active"), actions([{ label: "Voir" }])],
        ["Paies non generees", "49 dossiers", badge("A verifier", "badge-pending"), actions([{ label: "Regulariser", variant: "primary" }])],
        ["Elements rejetes", "8 dossiers", badge("Bloquant", "badge-inactive"), actions([{ label: "Voir" }])]
      ]
    },
    "paie-edition-salaires-banque": {
      title: "&Eacute;dition des salaires par banque",
      icon: "SB",
      breadcrumb: "Gestion de la paie &gt; Salaires par banque",
      stats: commonStats("Editions"),
      filters: ["Periode", "Banque", "Statut"],
      actions: ["Generer", "Exporter"],
      columns: ["Banque", "Periode", "Nombre agents", "Net a payer", "Statut", "Actions"],
      rows: [
        ["BHS", "Juin 2026", "420", "158 000 000 FCFA", badge("Pret", "badge-active"), actions([{ label: "Voir" }, { label: "Exporter" }])],
        ["CBAO", "Juin 2026", "315", "121 000 000 FCFA", badge("Controle", "badge-pending"), actions([{ label: "Voir" }])]
      ]
    },
    "paie-bulletins": {
      title: "Bulletins des salaires",
      icon: "BS",
      breadcrumb: "Gestion de la paie &gt; Bulletins des salaires",
      stats: commonStats("Bulletins"),
      filters: ["Periode", "IA", "Statut"],
      actions: ["Generer", "Exporter"],
      columns: ["Periode", "Enseignant", "Matricule", "IA", "IEF", "Net a payer", "Statut bulletin", "Actions"],
      rows: [
        ["Juin 2026", "Mamadou DIOP", "ENS001", "IA Dakar", "IEF Dakar Nord", "402 000 FCFA", badge("Disponible", "badge-active"), actions([{ label: "Voir" }, { label: "Telecharger" }, { label: "Imprimer" }])],
        ["Juin 2026", "Aissatou FALL", "ENS002", "IA Thies", "IEF Thies", "351 000 FCFA", badge("En preparation", "badge-pending"), actions([{ label: "Voir" }])]
      ]
    },
    "paie-effectifs-corps": {
      title: "Effectifs par corps",
      icon: "EC",
      breadcrumb: "Gestion de la paie &gt; Effectifs par corps",
      objectives: ["Recuperer les agents actifs."],
      stats: commonStats("Agents actifs"),
      chart: ["PC", "PE", "MA", "CE", "CF", "AD"],
      filters: ["Periode", "IA", "Corps"],
      actions: ["Actualiser", "Exporter"],
      columns: ["Corps", "Nombre agents actifs", "IA dominante", "Evolution", "Actions"],
      rows: [
        ["Professeurs certifies", "438", "IA Dakar", "+4%", actions([{ label: "Voir" }, { label: "Exporter" }])],
        ["Maitres contractuels", "512", "IA Thies", "+2%", actions([{ label: "Voir" }])]
      ]
    },
    "paie-non-generee": {
      title: "Paie non g&eacute;n&eacute;r&eacute;e",
      icon: "NG",
      breadcrumb: "Gestion de la paie &gt; Paie non g&eacute;n&eacute;r&eacute;e",
      objectives: [
        "Identifier les agents ou structures dont la paie mensuelle n'est pas encore generee.",
        "Valider la paie mensuelle."
      ],
      stats: commonStats("Dossiers non generes"),
      filters: ["Periode", "Structure", "Statut"],
      actions: ["Regulariser", "Valider"],
      columns: ["Enseignant", "Motif", "Structure", "Statut", "Actions"],
      rows: [
        ["Saliou NDIAYE", "Indice manquant", "IEF Dakar Nord", badge("A regulariser", "badge-pending"), actions([{ label: "Regulariser", variant: "primary" }, { label: "Valider", confirm: "Valider cette paie mensuelle ?", success: "Paie mensuelle validee." }])],
        ["Nafi SENE", "Affectation incomplete", "IEF Thies", badge("Bloque", "badge-inactive"), actions([{ label: "Voir" }])]
      ]
    },
    "paie-sommes-percues": {
      title: "Sommes per&ccedil;ues",
      icon: "SP",
      breadcrumb: "Gestion de la paie &gt; Sommes per&ccedil;ues",
      stats: commonStats("Sommes suivies"),
      filters: ["Periode", "IA", "Statut"],
      actions: ["Consolider", "Exporter"],
      columns: ["Enseignant", "Periode", "Brut", "Retenues", "Net percu", "Statut", "Actions"],
      rows: [
        ["Mamadou DIOP", "Juin 2026", "420 000 FCFA", "18 000 FCFA", "402 000 FCFA", badge("Verse", "badge-active"), actions([{ label: "Voir" }])],
        ["Aissatou FALL", "Juin 2026", "373 000 FCFA", "22 000 FCFA", "351 000 FCFA", badge("Verse", "badge-active"), actions([{ label: "Voir" }])]
      ]
    },
    "credit-delegation": {
      title: "D&eacute;l&eacute;gation de cr&eacute;dit",
      icon: "DC",
      breadcrumb: "Gestion de la paie &gt; D&eacute;l&eacute;gation de cr&eacute;dit",
      objectives: ["Creer une delegation de credit.", "Affecter une delegation a un service ou a une structure."],
      stats: commonStats("Delegations"),
      filters: ["Structure", "Service", "Statut"],
      actions: ["Nouvelle delegation", "Exporter"],
      columns: ["Structure beneficiaire", "Service", "Montant alloue", "Date", "Statut", "Actions"],
      rows: [
        ["IA Dakar", "Paie enseignants", "85 000 000 FCFA", "18/06/2026", badge("Valide", "badge-active"), actions([{ label: "Voir" }, { label: "Modifier" }])],
        ["IA Thies", "Indemnites", "42 000 000 FCFA", "19/06/2026", badge("En attente", "badge-pending"), actions([{ label: "Voir" }, { label: "Valider", variant: "primary" }])]
      ]
    },
    "credit-edition-delegations": {
      title: "&Eacute;dition des d&eacute;l&eacute;gations de cr&eacute;dits",
      icon: "ED",
      breadcrumb: "Gestion de la paie &gt; &Eacute;dition des d&eacute;l&eacute;gations de cr&eacute;dits",
      objectives: ["Definir le montant disponible.", "Suivre le montant engage.", "Suivre le montant consomme.", "Suivre le solde restant."],
      stats: [
        { label: "Montant disponible", value: "420M", note: "FCFA", icon: "MD", color: "green" },
        { label: "Montant engage", value: "310M", note: "FCFA", icon: "ME", color: "blue" },
        { label: "Montant consomme", value: "248M", note: "FCFA", icon: "MC", color: "yellow" },
        { label: "Solde restant", value: "110M", note: "FCFA", icon: "SR", color: "green" }
      ],
      filters: ["Structure", "Periode", "Statut"],
      actions: ["Editer", "Exporter"],
      columns: ["Reference", "Structure", "Montant disponible", "Engage", "Consomme", "Solde", "Statut", "Actions"],
      rows: [
        ["DEL-2026-001", "IA Dakar", "150 000 000", "120 000 000", "92 000 000", "30 000 000", badge("Actif", "badge-active"), actions([{ label: "Voir" }])],
        ["DEL-2026-002", "IA Thies", "90 000 000", "62 000 000", "50 000 000", "28 000 000", badge("Actif", "badge-active"), actions([{ label: "Voir" }])]
      ]
    },
    "credit-edition-engagements": {
      title: "&Eacute;dition des engagements",
      icon: "EG",
      breadcrumb: "Gestion de la paie &gt; &Eacute;dition des engagements",
      objectives: ["Generer l'etat des credits utilises pour la paie."],
      stats: commonStats("Engagements"),
      filters: ["Periode", "Structure", "Statut"],
      actions: ["Export PDF", "Export Excel"],
      columns: ["Periode", "Structure", "Credit initial", "Credit engage", "Credit consomme", "Solde", "Actions"],
      rows: [
        ["Juin 2026", "IA Dakar", "150 000 000", "120 000 000", "92 000 000", "30 000 000", actions([{ label: "PDF" }, { label: "Excel" }])],
        ["Juin 2026", "IA Thies", "90 000 000", "62 000 000", "50 000 000", "28 000 000", actions([{ label: "PDF" }, { label: "Excel" }])]
      ]
    },
    "indemnites-convocations": {
      title: "Gestion des convocations",
      icon: "GC",
      breadcrumb: "Gestion des indemnit&eacute;s &gt; Convocations",
      stats: commonStats("Convocations"),
      filters: ["Date", "Objet", "Statut"],
      actions: ["Nouvelle convocation", "Exporter"],
      columns: ["Enseignant / agent", "Objet", "Date", "Lieu", "Statut", "Actions"],
      rows: [
        ["Mamadou DIOP", "Jury examen", "28/06/2026", "Dakar", badge("Envoyee", "badge-active"), actions([{ label: "Voir" }])],
        ["Aissatou FALL", "Atelier national", "30/06/2026", "Thies", badge("Brouillon", "badge-pending"), actions([{ label: "Voir" }, { label: "Modifier" }])]
      ]
    },
    "indemnites-services-faits": {
      title: "Gestion des services faits",
      icon: "SF",
      breadcrumb: "Gestion des indemnit&eacute;s &gt; Services faits",
      stats: commonStats("Services faits"),
      filters: ["Mission", "Periode", "Validation"],
      actions: ["Nouveau service fait", "Exporter"],
      columns: ["Agent", "Mission", "Service fait", "Date debut", "Date fin", "Validation", "Actions"],
      rows: [
        ["Mamadou DIOP", "Jury examen", "Oui", "28/06/2026", "30/06/2026", badge("Valide", "badge-active"), actions([{ label: "Voir" }])],
        ["Aissatou FALL", "Atelier national", "Partiel", "30/06/2026", "02/07/2026", badge("A valider", "badge-pending"), actions([{ label: "Voir" }, { label: "Valider", variant: "primary" }])]
      ]
    },
    "indemnites-pieces-justificatives": {
      title: "Gestion des pi&egrave;ces justificatives",
      icon: "PJ",
      breadcrumb: "Gestion des indemnit&eacute;s &gt; Pi&egrave;ces justificatives",
      stats: commonStats("Pieces"),
      filters: ["Type de piece", "Date depot", "Statut"],
      actions: ["Ajouter une piece", "Exporter"],
      columns: ["Agent", "Type de piece", "Fichier", "Date depot", "Statut", "Actions"],
      rows: [
        ["Mamadou DIOP", "Ordre de mission", "om-001.pdf", "21/06/2026", badge("Valide", "badge-active"), actions([{ label: "Voir" }])],
        ["Aissatou FALL", "Rapport", "rapport-atelier.pdf", "22/06/2026", badge("A verifier", "badge-pending"), actions([{ label: "Voir" }, { label: "Valider", variant: "primary" }, { label: "Rejeter", variant: "danger" }])]
      ]
    },
    "indemnites-accuses-reception": {
      title: "Gestion des accus&eacute;s de r&eacute;ception",
      icon: "AR",
      breadcrumb: "Gestion des indemnit&eacute;s &gt; Accus&eacute;s de r&eacute;ception",
      stats: commonStats("Accuses"),
      filters: ["Document", "Date reception", "Statut"],
      actions: ["Nouvel accuse", "Exporter"],
      columns: ["Reference", "Agent", "Document", "Date reception", "Recu par", "Statut"],
      rows: [
        ["AR-001", "Mamadou DIOP", "Ordre de mission", "22/06/2026", "Bureau paie", badge("Recu", "badge-active")],
        ["AR-002", "Aissatou FALL", "Rapport", "23/06/2026", "Cellule indemnite", badge("En attente", "badge-pending")]
      ]
    },
    "indemnites-calcul": {
      title: "Calcul des indemnit&eacute;s",
      icon: "CI",
      breadcrumb: "Gestion des indemnit&eacute;s &gt; Calcul",
      stats: commonStats("Calculs"),
      filters: ["Periode", "Type indemnite", "Statut"],
      actions: ["Calculer", "Exporter"],
      calculator: true,
      columns: ["Agent", "Type indemnite", "Base de calcul", "Montant", "Periode", "Statut", "Actions"],
      rows: [
        ["Mamadou DIOP", "Jury", "3 jours", "185 000 FCFA", "Juin 2026", badge("Calcule", "badge-active"), actions([{ label: "Voir" }])],
        ["Aissatou FALL", "Deplacement", "180 km", "72 000 FCFA", "Juin 2026", badge("A calculer", "badge-pending"), actions([{ label: "Calculer", variant: "primary" }])]
      ]
    },
    "indemnites-frais-deplacement": {
      title: "Gestion des frais de d&eacute;placement",
      icon: "FD",
      breadcrumb: "Gestion des indemnit&eacute;s &gt; Frais de d&eacute;placement",
      stats: commonStats("Frais"),
      filters: ["Mission", "Lieu", "Statut"],
      actions: ["Nouveau frais", "Exporter"],
      columns: ["Agent", "Mission", "Lieu depart", "Lieu arrivee", "Distance", "Montant", "Justificatif", "Statut"],
      rows: [
        ["Mamadou DIOP", "Jury examen", "Dakar", "Thies", "72 km", "45 000 FCFA", "Joint", badge("Valide", "badge-active")],
        ["Aissatou FALL", "Atelier", "Thies", "Saint-Louis", "190 km", "85 000 FCFA", "A joindre", badge("En attente", "badge-pending")]
      ]
    },
    "indemnites-etats-paie": {
      title: "G&eacute;n&eacute;ration des &eacute;tats de paie",
      icon: "EP",
      breadcrumb: "Gestion des indemnit&eacute;s &gt; Etats de paie",
      stats: commonStats("Etats generes"),
      filters: ["Periode", "Type indemnite", "Statut"],
      actions: ["Generer", "Exporter"],
      columns: ["Periode", "Type indemnite", "Nombre agents", "Montant total", "Statut generation", "Actions"],
      rows: [
        ["Juin 2026", "Jury", "82", "14 200 000 FCFA", badge("Genere", "badge-active"), actions([{ label: "Voir" }, { label: "Exporter" }])],
        ["Juin 2026", "Deplacement", "41", "6 450 000 FCFA", badge("En preparation", "badge-pending"), actions([{ label: "Generer", variant: "primary" }])]
      ]
    },
    "bourses-enregistrer-demande": {
      title: "Enregistrer une demande",
      icon: "BD",
      breadcrumb: "Gestion des Bourses et aides &gt; Enregistrer une demande",
      stats: commonStats("Demandes"),
      filters: ["Type aide", "Date", "Statut"],
      actions: ["Nouvelle demande", "Exporter"],
      columns: ["Beneficiaire", "Type d'aide", "Montant demande", "Motif", "Date demande", "Statut", "Actions"],
      rows: [
        ["Aminata BA", "Aide sociale", "150 000 FCFA", "Appui scolaire", "21/06/2026", badge("En attente", "badge-pending"), actions([{ label: "Voir" }, { label: "Modifier" }])],
        ["Oumar NDIAYE", "Bourse", "250 000 FCFA", "Formation technique", "22/06/2026", badge("Complete", "badge-active"), actions([{ label: "Voir" }])]
      ]
    },
    "bourses-valider-dossier": {
      title: "Valider un dossier",
      icon: "VD",
      breadcrumb: "Gestion des Bourses et aides &gt; Valider un dossier",
      stats: commonStats("Dossiers"),
      filters: ["Type aide", "Avis", "Statut"],
      actions: ["Valider", "Exporter"],
      columns: ["Beneficiaire", "Type aide", "Pieces", "Avis", "Actions"],
      rows: [
        ["Aminata BA", "Aide sociale", "Completes", "Favorable", actions([{ label: "Valider", variant: "primary" }, { label: "Rejeter", variant: "danger" }])],
        ["Oumar NDIAYE", "Bourse", "A completer", "Reserve", actions([{ label: "Voir" }, { label: "Rejeter", variant: "danger" }])]
      ]
    },
    "bourses-attribuer-aide": {
      title: "Attribuer une aide",
      icon: "AA",
      breadcrumb: "Gestion des Bourses et aides &gt; Attribuer une aide",
      stats: commonStats("Aides attribuees"),
      filters: ["Aide", "Mode paiement", "Statut"],
      actions: ["Attribuer", "Exporter"],
      columns: ["Beneficiaire", "Aide validee", "Montant attribue", "Date attribution", "Mode paiement", "Statut attribution", "Actions"],
      rows: [
        ["Aminata BA", "Aide sociale", "150 000 FCFA", "23/06/2026", "Virement", badge("Attribuee", "badge-active"), actions([{ label: "Voir" }])],
        ["Oumar NDIAYE", "Bourse", "250 000 FCFA", "23/06/2026", "Tresor", badge("En cours", "badge-pending"), actions([{ label: "Voir" }, { label: "Finaliser", variant: "primary" }])]
      ]
    },
    utilisateurs: {
      title: "Utilisateurs",
      icon: "US",
      breadcrumb: "Gestion Utilisateur &gt; Utilisateurs",
      objectives: [
        "Consulter les comptes d&rsquo;acc&egrave;s au syst&egrave;me.",
        "Suivre les profils affect&eacute;s aux utilisateurs.",
        "Garder une vision claire des comptes actifs et suspendus."
      ],
      stats: [
        { label: "Comptes", value: "42", note: "Tous profils", icon: "fa-solid fa-users", color: "green" },
        { label: "Actifs", value: "36", note: "Acc&egrave;s ouverts", icon: "fa-solid fa-circle-check", color: "blue" },
        { label: "Suspendus", value: "4", note: "&Agrave; revoir", icon: "fa-solid fa-user-lock", color: "yellow" },
        { label: "Administrateurs", value: "2", note: "Acc&egrave;s sensibles", icon: "fa-solid fa-user-shield", color: "red" }
      ],
      filters: ["Profil", "Statut", "Service"],
      actions: ["Nouvel utilisateur", "Exporter"],
      columns: ["Nom", "E-mail", "Profil", "Service", "Statut", "Actions"],
      rows: [
        ["Amina Diallo", "amina.diallo@sicore.sn", "Administrateur", "Direction", badge("Actif", "badge-active"), actions([{ label: "Voir" }, { label: "Modifier" }])],
        ["Moussa Ndiaye", "moussa.ndiaye@sicore.sn", "Gestionnaire paie", "Paie", badge("Actif", "badge-active"), actions([{ label: "Voir" }, { label: "Modifier" }])],
        ["Fatou Kane", "fatou.kane@sicore.sn", "Contr&ocirc;leur", "Indemnit&eacute;s", badge("Suspendu", "badge-suspended"), actions([{ label: "Voir" }])]
      ]
    },
    "profils-roles": {
      title: "Profils / R&ocirc;les",
      icon: "PR",
      breadcrumb: "Gestion Utilisateur &gt; Profils / R&ocirc;les",
      objectives: [
        "D&eacute;crire les responsabilit&eacute;s fonctionnelles.",
        "Associer les r&ocirc;les aux modules SICORE.",
        "Limiter les acc&egrave;s aux besoins r&eacute;els des services."
      ],
      stats: [
        { label: "Profils", value: "8", note: "R&ocirc;les actifs", icon: "fa-solid fa-id-badge", color: "green" },
        { label: "Modules couverts", value: "6", note: "Navigation officielle", icon: "fa-solid fa-layer-group", color: "blue" },
        { label: "En revue", value: "2", note: "Validation interne", icon: "fa-solid fa-clock", color: "yellow" },
        { label: "Sensibles", value: "3", note: "Acc&egrave;s renforc&eacute;s", icon: "fa-solid fa-shield-halved", color: "red" }
      ],
      filters: ["Module", "Niveau", "Statut"],
      actions: ["Nouveau profil", "Exporter"],
      columns: ["Profil", "Module principal", "Niveau d&rsquo;acc&egrave;s", "Statut", "Actions"],
      rows: [
        ["Administrateur", "Tous modules", "Complet", badge("Actif", "badge-active"), actions([{ label: "Voir" }, { label: "Modifier" }])],
        ["Gestionnaire paie", "Gestion de la paie", "Saisie et validation", badge("Actif", "badge-active"), actions([{ label: "Voir" }])],
        ["Contr&ocirc;leur indemnit&eacute;s", "Gestion des indemnit&eacute;s", "Contr&ocirc;le", badge("En revue", "badge-pending"), actions([{ label: "Voir" }, { label: "Modifier" }])]
      ]
    },
    permissions: {
      title: "Permissions",
      icon: "PM",
      breadcrumb: "Gestion Utilisateur &gt; Permissions",
      objectives: [
        "Visualiser les droits par module.",
        "S&eacute;parer les permissions de consultation, saisie, validation et administration.",
        "Pr&eacute;parer les param&egrave;tres sans inventer de backend."
      ],
      stats: [
        { label: "Permissions", value: "24", note: "Droits d&eacute;finis", icon: "fa-solid fa-key", color: "green" },
        { label: "Lecture", value: "6", note: "Modules", icon: "fa-solid fa-eye", color: "blue" },
        { label: "Validation", value: "8", note: "Processus", icon: "fa-solid fa-circle-check", color: "yellow" },
        { label: "Administration", value: "4", note: "Acc&egrave;s critiques", icon: "fa-solid fa-lock", color: "red" }
      ],
      filters: ["Module", "Permission", "Profil"],
      actions: ["Nouvelle permission", "Exporter"],
      columns: ["Module", "Permission", "Profils concern&eacute;s", "Statut", "Actions"],
      rows: [
        ["Gestion de la paie", "Valider les salaires", "Administrateur, Gestionnaire paie", badge("Actif", "badge-active"), actions([{ label: "Voir" }])],
        ["Gestion des indemnit&eacute;s", "Contr&ocirc;ler les justificatifs", "Administrateur, Contr&ocirc;leur", badge("Actif", "badge-active"), actions([{ label: "Voir" }])],
        ["Param&eacute;trage", "Modifier les r&eacute;f&eacute;rentiels", "Administrateur", badge("Restreint", "badge-pending"), actions([{ label: "Voir" }])]
      ]
    }
  };

  function renderStats(stats) {
    return '<div class="stats-grid four">' + stats.map(function (item) {
      return [
        '<article class="stat-card">',
        "<div>",
        '<p class="stat-label">' + item.label + "</p>",
        '<p class="stat-value">' + item.value + "</p>",
        '<p class="stat-note">' + item.note + "</p>",
        "</div>",
        '<span class="stat-icon ' + item.color + '">' + renderIcon(item.icon, statIconMap) + "</span>",
        "</article>"
      ].join("");
    }).join("") + "</div>";
  }

  function renderObjectives(page) {
    if (!page.objectives) {
      return "";
    }

    return [
      '<section class="objective-card' + (page.sensitive ? " sensitive-panel" : "") + '">',
      "<h2>Objectifs metier</h2>",
      '<ul class="objective-list">',
      page.objectives.map(function (objective) {
        return "<li>" + objective + "</li>";
      }).join(""),
      "</ul>",
      "</section>"
    ].join("");
  }

  function renderFilters(page, slug) {
    var columns = page.columns || [];
    var used = {};

    // Les options sont injectees par app.js a partir des valeurs reelles du tableau.
    var controls = page.filters.map(function (filter, index) {
      var id = slug + "-filter-" + index;
      var columnIndex = matchFilterColumn(filter, columns, used);
      if (columnIndex !== -1) {
        used[columnIndex] = true;
      }

      return [
        '<div class="form-group">',
        '<label for="' + id + '">' + filter + "</label>",
        '<select class="form-control" id="' + id + '" data-filter-select' + (columnIndex !== -1 ? ' data-filter-column="' + columnIndex + '"' : "") + ">",
        '<option value="">Tous</option>',
        "</select>",
        "</div>"
      ].join("");
    }).join("");

    return [
      '<section class="filter-panel" data-filter-panel data-filter-target="#moduleTable">',
      controls,
      '<div class="actions-group">',
      '<button class="btn-secondary" type="button" data-filter-apply>Filtrer</button>',
      '<button class="btn-secondary" type="button" data-filter-reset>R&eacute;initialiser</button>',
      "</div>",
      "</section>"
    ].join("");
  }

  function renderActions(page) {
    var buttons = (page.actions || []).map(function (label, index) {
      var className = index === 0 ? "btn-primary" : "btn-secondary";
      var calculatorAttr = page.calculator && label === "Calculer" ? " data-calculate-indemnity" : "";
      var normalized = normalizeText(label);
      var exportAttr = normalized.indexOf("export") === 0 ? ' data-export-table="#moduleTable"' : "";
      if (exportAttr && normalized.indexOf("pdf") !== -1) {
        exportAttr += ' data-export-format="pdf"';
      }
      return '<button class="' + className + '" type="button"' + calculatorAttr + exportAttr + ">" + label + "</button>";
    }).join("");

    if (page.closePeriod) {
      buttons += '<button class="btn-danger-soft" type="button" data-confirm="Etes-vous sur de vouloir fermer cette periode de paie ? Cette action est sensible." data-success-message="Periode de paie fermee.">Fermer la periode</button>';
    }

    return [
      '<div class="actions-row">',
      '<p class="breadcrumb">' + page.breadcrumb + "</p>",
      '<div class="actions-group">' + buttons + "</div>",
      "</div>"
    ].join("");
  }

  function renderHelp(page) {
    if (!page.helpText) {
      return "";
    }

    return [
      '<section class="help-card">',
      "<h2>" + page.helpTitle + "</h2>",
      "<p>" + page.helpText + "</p>",
      "</section>"
    ].join("");
  }

  function renderChart(page) {
    if (!page.chart) {
      return "";
    }

    var heights = [58, 74, 48, 86, 66, 96];
    return [
      '<section class="panel">',
      '<div class="panel-header"><div><h2>Graphique mensuel</h2><p>Vue synthetique de la periode</p></div></div>',
      '<div class="mini-chart">',
      page.chart.map(function (label, index) {
        return '<div class="mini-bar"><span style="height:' + heights[index % heights.length] + 'px"></span>' + label + "</div>";
      }).join(""),
      "</div>",
      "</section>"
    ].join("");
  }

  function renderCalculator(page) {
    if (!page.calculator) {
      return "";
    }

    return '<section class="result-card" data-indemnity-result hidden></section>';
  }

  function renderTable(page) {
    var head = page.columns.map(function (column, index) {
      return '<th' + (index === page.columns.length - 1 ? ' class="actions-cell"' : "") + ">" + column + "</th>";
    }).join("");

    var rows = page.rows.map(function (row) {
      return "<tr>" + row.map(function (cell, index) {
        return '<td' + (index === row.length - 1 ? ' class="actions-cell"' : "") + ">" + cell + "</td>";
      }).join("") + "</tr>";
    }).join("");

    return [
      '<section class="table-card">',
      '<div class="table-responsive">',
      '<table class="table" id="moduleTable">',
      "<thead><tr>" + head + "</tr></thead>",
      "<tbody>" + rows + "</tbody>",
      "</table>",
      "</div>",
      '<p class="empty-message">Aucune donnee trouvee.</p>',
      '<div class="pagination">',
      '<button class="page-btn" type="button">&#8592;</button>',
      '<button class="page-btn active" type="button" data-page-number>1</button>',
      '<button class="page-btn" type="button" data-page-number>2</button>',
      '<button class="page-btn" type="button">&#8594;</button>',
      "</div>",
      "</section>"
    ].join("");
  }

  function renderHeader(page, slug) {
    var header = document.querySelector("[data-page-header]");
    if (!header) {
      return;
    }

    header.innerHTML = [
      '<div class="page-title-wrap">',
      '<button class="mobile-menu-btn" type="button" data-sidebar-toggle aria-label="Ouvrir le menu">&#9776;</button>',
      '<span class="title-icon" aria-hidden="true">' + renderIcon(pageIconMap[slug] || page.icon, statIconMap) + "</span>",
      "<div>",
      "<h1>" + page.title + "</h1>",
      "<p>" + page.breadcrumb + "</p>",
      "</div>",
      "</div>",
      '<div class="search-wrap">',
      '<label class="sr-only" for="moduleSearch">Rechercher</label>',
      '<input class="search-input" id="moduleSearch" type="search" placeholder="Rechercher..." data-table-filter="#moduleTable">',
      "</div>"
    ].join("");
  }

  function renderPage() {
    var slug = document.body.getAttribute("data-module-page");
    var page = pages[slug];
    var content = document.querySelector("[data-page-content]");

    if (!slug || !page || !content) {
      return;
    }

    renderHeader(page, slug);
    content.innerHTML = [
      renderObjectives(page),
      renderStats(page.stats || commonStats()),
      renderActions(page),
      renderFilters(page, slug),
      renderHelp(page),
      renderChart(page),
      renderCalculator(page),
      renderTable(page)
    ].join("");

    if (window.SICOREApp) {
      window.SICOREApp.refresh();
    }
  }

  document.addEventListener("DOMContentLoaded", renderPage);
})();
