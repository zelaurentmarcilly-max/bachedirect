(function () {
  var toggle = document.querySelector(".bd-nav-toggle");
  var nav = document.querySelector("#bd-nav");
  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      var open = toggle.getAttribute("aria-expanded") === "true";
      toggle.setAttribute("aria-expanded", open ? "false" : "true");
      nav.classList.toggle("is-open", !open);
    });
  }

  var art = document.querySelector(".bd-product-art svg");
  var form = document.querySelector("form.cart");
  if (!art || !form) {
    return;
  }
  var sync = function () {
    var field = form.querySelector("input[name='bd_finish_id']:checked") || form.querySelector("input[name='bd_finish_id']");
    var id = field ? field.value : "";
    art.classList.toggle("is-grommets", id.indexOf("oeillet") !== -1);
    art.classList.toggle("is-sleeve", id.indexOf("fourreau") !== -1);
  };
  form.addEventListener("change", sync);
  sync();
})();
