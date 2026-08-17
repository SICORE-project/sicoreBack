(function () {
  "use strict";

  var labels = {
    success: "Succes",
    error: "Erreur",
    warning: "Avertissement",
    info: "Information"
  };

  function ensureToastHost() {
    var host = document.getElementById("toastHost");
    if (host) {
      return host;
    }

    host = document.createElement("div");
    host.id = "toastHost";
    host.className = "toast-host";
    host.setAttribute("aria-live", "polite");
    host.setAttribute("aria-atomic", "true");
    document.body.appendChild(host);
    return host;
  }

  function showToast(type, message) {
    var variant = labels[type] ? type : "info";
    var host = ensureToastHost();
    var toast = document.createElement("div");
    toast.className = "toast toast-" + variant;
    toast.innerHTML = [
      '<strong>' + labels[variant] + "</strong>",
      '<span>' + (message || "Operation effectuee.") + "</span>",
      '<button type="button" aria-label="Fermer">&times;</button>'
    ].join("");

    toast.querySelector("button").addEventListener("click", function () {
      toast.remove();
    });

    host.appendChild(toast);
    window.setTimeout(function () {
      toast.classList.add("is-leaving");
      window.setTimeout(function () {
        toast.remove();
      }, 180);
    }, 4200);
  }

  function showSuccessToast(message) {
    showToast("success", message);
  }

  window.showToast = showToast;
  window.showSuccessToast = showSuccessToast;
})();
