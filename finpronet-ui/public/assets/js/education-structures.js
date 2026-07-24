(function () {
  "use strict";

  var educationStructures = [
    {
      ia: "IA Dakar",
      iefs: ["IEF Dakar-Plateau", "IEF Grand Dakar", "IEF Parcelles Assainies", "IEF Almadies"]
    },
    {
      ia: "IA Pikine-Guediawaye",
      iefs: ["IEF Pikine", "IEF Guediawaye", "IEF Thiaroye", "IEF Yeumbeul", "IEF Keur Massar"]
    },
    {
      ia: "IA Rufisque",
      iefs: ["IEF Rufisque", "IEF Sangalkam", "IEF Diamniadio"]
    },
    {
      ia: "IA Diourbel",
      iefs: ["IEF Bambey", "IEF Diourbel", "IEF Mbacke"]
    },
    {
      ia: "IA Fatick",
      iefs: ["IEF Fatick", "IEF Foundiougne", "IEF Gossas"]
    },
    {
      ia: "IA Kaffrine",
      iefs: ["IEF Kaffrine", "IEF Birkilane", "IEF Koungheul", "IEF Malem Hodar"]
    },
    {
      ia: "IA Kaolack",
      iefs: ["IEF Kaolack", "IEF Guinguineo", "IEF Nioro du Rip"]
    },
    {
      ia: "IA Kedougou",
      iefs: ["IEF Kedougou", "IEF Salemata", "IEF Saraya"]
    },
    {
      ia: "IA Kolda",
      iefs: ["IEF Kolda", "IEF Velingara", "IEF Medina Yoro Foulah"]
    },
    {
      ia: "IA Louga",
      iefs: ["IEF Louga", "IEF Kebemer", "IEF Linguere"]
    },
    {
      ia: "IA Matam",
      iefs: ["IEF Matam", "IEF Kanel", "IEF Ranerou-Ferlo"]
    },
    {
      ia: "IA Saint-Louis",
      iefs: ["IEF Saint-Louis", "IEF Dagana", "IEF Podor", "IEF Pete"]
    },
    {
      ia: "IA Sedhiou",
      iefs: ["IEF Sedhiou", "IEF Bounkiling", "IEF Goudomp"]
    },
    {
      ia: "IA Tambacounda",
      iefs: ["IEF Tambacounda", "IEF Bakel", "IEF Goudiry", "IEF Koumpentoum"]
    },
    {
      ia: "IA Thies",
      iefs: ["IEF Thies Ville", "IEF Thies Departement", "IEF Mbour", "IEF Tivaouane"]
    },
    {
      ia: "IA Ziguinchor",
      iefs: ["IEF Ziguinchor", "IEF Bignona", "IEF Oussouye"]
    }
  ];

  function addOption(select, value, label) {
    var option = document.createElement("option");
    option.value = value;
    option.textContent = label;
    select.appendChild(option);
  }

  function getStructureByIa(iaName) {
    return educationStructures.find(function (item) {
      return item.ia === iaName;
    });
  }

  function populateIaSelect(select) {
    var selectedValue = select.value;
    select.innerHTML = "";
    addOption(select, "", "Selectionner une IA");

    educationStructures.forEach(function (item) {
      addOption(select, item.ia, item.ia);
    });

    if (selectedValue) {
      select.value = selectedValue;
    }
  }

  function populateIefSelect(select, iaName) {
    var selectedValue = select.value;
    var structure = getStructureByIa(iaName);
    select.innerHTML = "";

    if (!structure) {
      addOption(select, "", "Selectionner d'abord une IA");
      select.disabled = true;
      return;
    }

    addOption(select, "", "Selectionner une IEF");
    structure.iefs.forEach(function (ief) {
      addOption(select, ief, ief);
    });
    select.disabled = false;

    if (selectedValue && structure.iefs.indexOf(selectedValue) !== -1) {
      select.value = selectedValue;
    }
  }

  function initEducationStructureSelects(root) {
    var scope = root || document;
    scope.querySelectorAll("[data-ia-select]").forEach(function (iaSelect) {
      var targetSelector = iaSelect.getAttribute("data-ief-target");
      var iefSelect = targetSelector ? scope.querySelector(targetSelector) || document.querySelector(targetSelector) : null;

      populateIaSelect(iaSelect);
      if (iefSelect) {
        populateIefSelect(iefSelect, iaSelect.value);
        iaSelect.addEventListener("change", function () {
          populateIefSelect(iefSelect, iaSelect.value);
          iefSelect.dispatchEvent(new Event("change", { bubbles: true }));
        });
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    initEducationStructureSelects(document);
  });

  window.SICOREEducationStructures = educationStructures;
  window.initEducationStructureSelects = initEducationStructureSelects;
})();
