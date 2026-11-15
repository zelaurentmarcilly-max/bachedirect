(function () {
  var form = document.querySelector("form.cart");
  if (!form || !form.querySelector(".bd-config") || !window.bachedirectConfig) {
    return;
  }

  var quote = form.querySelector(".bd-quote");
  var button = form.querySelector("[name='add-to-cart']");
  var fileRef = form.querySelector(".bd-file-ref");
  var timer = 0;

  function syncFile() {
    if (!fileRef) {
      return;
    }
    var selected = form.querySelector("input[name='bd_file_mode']:checked");
    fileRef.hidden = !selected || selected.value !== "reference";
  }

  function render(data) {
    var total = quote.querySelector("[data-bd-total]");
    var ready = quote.querySelector("[data-bd-ready]");
    var note = quote.querySelector("[data-bd-note]");
    var error = quote.querySelector("[data-bd-error]");
    var list = quote.querySelector("[data-bd-lines]");
    list.replaceChildren();
    if (!data.ok) {
      total.textContent = "—";
      ready.textContent = "";
      note.textContent = "";
      error.textContent = (data.errors || []).join(" ");
      if (button) {
        button.disabled = true;
      }
      return;
    }
    error.textContent = "";
    total.textContent = data.total;
    ready.textContent = data.ready || "";
    note.textContent = data.note || "";
    (data.lines || []).forEach(function (line) {
      var row = document.createElement("div");
      var name = document.createElement("span");
      var amount = document.createElement("span");
      name.textContent = line.label;
      amount.textContent = line.amount;
      row.append(name, amount);
      list.append(row);
    });
    if (button) {
      button.disabled = false;
    }
    quote.classList.add("is-fresh");
    window.setTimeout(function () {
      quote.classList.remove("is-fresh");
    }, 420);
  }

  function ask() {
    var body = new FormData(form);
    body.set("action", "bachedirect_quote");
    body.set("nonce", window.bachedirectConfig.nonce);
    fetch(window.bachedirectConfig.ajaxUrl, {
      method: "POST",
      body: body,
      credentials: "same-origin",
    })
      .then(function (response) {
        return response.json();
      })
      .then(render)
      .catch(function () {
        render({ ok: false, errors: ["Le prix n'a pas pu être recalculé."] });
      });
  }

  function schedule(wait) {
    window.clearTimeout(timer);
    timer = window.setTimeout(ask, wait);
  }

  form.addEventListener("input", function () {
    syncFile();
    schedule(180);
  });
  form.addEventListener("change", function () {
    syncFile();
    schedule(60);
  });

  syncFile();
  var initialError = quote.querySelector("[data-bd-error]");
  if (button && initialError && initialError.textContent.trim() !== "") {
    button.disabled = true;
  }
})();
