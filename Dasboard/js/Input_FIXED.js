// Input.php FIXED JS - Self-contained for dynamic loads
(function () {
  "use strict";

  // Auto-detect BASE_PATH
  window.CURRENT_BASE_PATH =
    window.CURRENT_BASE_PATH ||
    (window.BASE_PATH ? window.BASE_PATH + "/sidebar" : "./");
  console.log(
    "🔧 Input FIXED JS loaded - BASE_PATH:",
    window.CURRENT_BASE_PATH,
  );

  function init() {
    console.log("🔄 Initializing Input page...");

    // Elements
    const form = document.querySelector("form");
    const submitBtn = form.querySelector('button[type="submit"]');

    // 1. NoPol -> ID + Sopir
    document.getElementById("nopol").addEventListener("input", (e) => {
      const val = e.target.value;
      const opt = Array.from(
        document.querySelectorAll("#kendaraan-list option"),
      ).find((o) => o.value === val);
      const idField = document.getElementById("id_kendaraan");
      const sopirField = document.getElementById("sopir");

      if (opt) {
        idField.value = opt.dataset.id;
        sopirField.value = opt.dataset.sopir || "";
        sopirField.title = "Auto dari DB";
      } else {
        idField.value = "";
        sopirField.title = "Manual";
      }
    });

    // 2. Checkbox toggles
    ["cek_customer", "cek_supplier"].forEach((id) => {
      document.getElementById(id).addEventListener("change", (e) => {
        const inputId =
          id === "cek_customer" ? "customer-input" : "supplier-input";
        const hiddenId = id === "cek_customer" ? "id_customers" : "id_supplier";
        const input = document.getElementById(inputId);
        input.style.display = e.target.checked ? "block" : "none";
        if (!e.target.checked) {
          input.value = "";
          document.getElementById(hiddenId).value = "";
        } else {
          input.focus();
        }
      });
    });

    // 3. Datalist auto ID
    ["customer-input", "supplier-input", "material-input"].forEach(
      (inputId) => {
        const input = document.getElementById(inputId);
        const datalistId = inputId.replace("-input", "-list");

        let hiddenId = "";
        if (inputId === "customer-input") hiddenId = "id_customers";
        if (inputId === "supplier-input") hiddenId = "id_supplier";
        if (inputId === "material-input") hiddenId = "id_material";

        input.addEventListener("input", (e) => {
          const val = e.target.value;
          console.log(`Material input: "${val}"`);

          const options = Array.from(
            document.querySelectorAll(`#${datalistId} option`),
          );
          console.log(
            "Available options:",
            options.map((o) => `'${o.value}' (${o.dataset.id})`).join(", "),
          );

          const opt = options.find(
            (o) =>
              o.value.trim() === val.trim() ||
              o.value.toLowerCase().includes(val.toLowerCase()),
          );

          console.log(
            "Found opt:",
            opt ? opt.value + " ID=" + opt.dataset.id : "NO MATCH",
          );

          if (opt) {
            document.getElementById(hiddenId).value = opt.dataset.id;
            console.log("✅ SET ID:", opt.dataset.id);
          } else {
            document.getElementById(hiddenId).value = "";
            console.log("❌ NO ID SET");
          }
        });
      },
    );

    // 4. Global functions for onclick/onchange
    window.calculate = () => {
      const bruto = parseFloat(document.getElementById("bruto").value) || 0;
      const tara = parseFloat(document.getElementById("tara").value) || 0;
      const netto = parseFloat(document.getElementById("netto").value) || 0;

      if (bruto && tara && !netto)
        document.getElementById("netto").value = (bruto - tara).toFixed(2);
      else if (bruto && netto && !tara)
        document.getElementById("tara").value = (bruto - netto).toFixed(2);
      else if (tara && netto && !bruto)
        document.getElementById("bruto").value = (tara + netto).toFixed(2);
    };

    window.validateForm = () => {
      return true;
    };

    window.loadEdit = async () => {
      const id = document.getElementById("edit_select").value;
      if (!id) return;

      try {
        const res = await fetch(
          `${window.CURRENT_BASE_PATH}/Input.php?edit=${id}`,
        );
        const data = await res.json();

        if (data.error) return alert(data.error);

        ["id_transaksi", "bruto", "tara", "netto"].forEach((id) => {
          const el = document.getElementById(id);
          el.value = data[id] || "";
        });

        document.getElementById("nopol").value = data.Nopol || "";
        // Trigger nopol input
        document.getElementById("nopol").dispatchEvent(new Event("input"));

        window.calculate();
        showToast("Data loaded ✅");
      } catch (e) {
        showToast("Load error: " + e, false);
      }
    };

    window.loadUnfinished = async () => {
      try {
        const res = await fetch(
          `${window.CURRENT_BASE_PATH}/Input.php?unfinished=1`,
        );
        const data = await res.json();

        const select = document.getElementById("edit_select");
        select.innerHTML = '<option value="">-- Unfinished Records --</option>';
        data.slice(0, 20).forEach((item) => {
          const opt = new Option(
            `${item.Nopol} - ${item.no_record} (${item.bruto})`,
            item.id_transaksi,
          );
          select.appendChild(opt);
        });
      } catch (e) {
        console.error("Unfinished load failed");
      }
    };

    // 5. Form submit
    form.onsubmit = async (e) => {
      e.preventDefault();
      if (!window.validateForm()) return;

      submitBtn.textContent = "Saving...";
      submitBtn.disabled = true;

      const fd = new FormData(form);
      try {
        const res = await fetch(`${window.CURRENT_BASE_PATH}/Input.php`, {
          method: "POST",
          body: fd,
        });
        const result = await res.json();

        showToast(result.message || "Saved!", result.success);

        if (result.success) {
          form.reset();
          window.loadUnfinished();
        }
      } catch (e) {
        showToast("Network error", false);
      }

      submitBtn.textContent = "SIMPAN KE DATABASE";
      submitBtn.disabled = false;
    };

    // 6. Toast
    window.showToast = (msg, success = true) => {
      const toast = document.createElement("div");
      toast.style.cssText = `
        position:fixed;top:20px;right:20px;padding:15px;border-radius:8px;
        color:white;z-index:9999;max-width:350px;box-shadow:0 4px 12px rgba(0,0,0,0.3);
        transform:translateX(400px);transition:0.3s;
        background:${success ? "#10b981" : "#ef4444"};
      `;
      toast.textContent = msg;
      document.body.appendChild(toast);

      setTimeout(() => (toast.style.transform = "translateX(0)"), 100);
      setTimeout(() => {
        toast.style.transform = "translateX(400px)";
        setTimeout(() => toast.remove(), 300);
      }, 4000);
    };

    // Auto load
    window.loadUnfinished();
  }

  // Init
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
