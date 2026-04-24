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

    // 1. NoPol -> ID + Sopir + Manual detection
    document.getElementById("nopol").addEventListener("input", (e) => {
      const val = e.target.value;
      const opt = Array.from(
        document.querySelectorAll("#kendaraan-list option"),
      ).find((o) => o.value === val);
      const idField = document.getElementById("id_kendaraan");
      const sopirField = document.getElementById("sopir");
      const nopolManual = document.getElementById("nopol_manual");
      const sopirManual = document.getElementById("sopir_manual");

      if (opt) {
        idField.value = opt.dataset.id;
        sopirField.value = opt.dataset.sopir || "";
        sopirField.title = "Auto dari DB";
        nopolManual.value = "";
        sopirManual.value = "";
      } else {
        idField.value = "";
        sopirField.title = "Manual";
        nopolManual.value = val;
        sopirManual.value = sopirField.value;
      }
    });

    // Sopir manual tracking
    document.getElementById("sopir").addEventListener("input", (e) => {
      const nopolManual = document.getElementById("nopol_manual");
      if (nopolManual.value) {
        document.getElementById("sopir_manual").value = e.target.value;
      }
    });

    // 2. Checkbox toggles
    ["cek_customer", "cek_supplier"].forEach((id) => {
      document.getElementById(id).addEventListener("change", (e) => {
        const inputId =
          id === "cek_customer" ? "customer-input" : "supplier-input";
        const hiddenId = id === "cek_customer" ? "id_customers" : "id_supplier";
        const manualId = id === "cek_customer" ? "customer_manual" : "supplier_manual";
        const input = document.getElementById(inputId);
        input.style.display = e.target.checked ? "block" : "none";
        if (!e.target.checked) {
          input.value = "";
          document.getElementById(hiddenId).value = "";
          document.getElementById(manualId).value = "";
        } else {
          input.focus();
        }
      });
    });

    // 3. Datalist auto ID + Manual detection
    ["customer-input", "supplier-input", "material-input"].forEach(
      (inputId) => {
        const input = document.getElementById(inputId);
        const datalistId = inputId.replace("-input", "-list");

        let hiddenId = "";
        let manualId = "";
        if (inputId === "customer-input") {
          hiddenId = "id_customers";
          manualId = "customer_manual";
        }
        if (inputId === "supplier-input") {
          hiddenId = "id_supplier";
          manualId = "supplier_manual";
        }
        if (inputId === "material-input") {
          hiddenId = "id_material";
          manualId = "material_manual";
        }

        input.addEventListener("input", (e) => {
          const val = e.target.value;
          const options = Array.from(
            document.querySelectorAll(`#${datalistId} option`),
          );
          const opt = options.find((o) => o.value.trim() === val.trim());

          if (opt) {
            document.getElementById(hiddenId).value = opt.dataset.id;
            document.getElementById(manualId).value = "";
            console.log(`✅ ${inputId} SET ID:`, opt.dataset.id);
          } else {
            document.getElementById(hiddenId).value = "";
            document.getElementById(manualId).value = val;
            console.log(`❌ ${inputId} NO MATCH - manual:`, val);
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
      // Allow saving with empty fields - all fields are optional
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

        // Handle nopol - could be DB-linked or manual
        if (data.id_kendaraan) {
          document.getElementById("nopol").value = data.Nopol || "";
          document.getElementById("id_kendaraan").value = data.id_kendaraan;
          document.getElementById("nopol_manual").value = "";
        } else if (data.Nopol) {
          document.getElementById("nopol").value = data.Nopol;
          document.getElementById("id_kendaraan").value = "";
          document.getElementById("nopol_manual").value = data.Nopol;
        }

        document.getElementById("sopir").value = data.Sopir || "";
        document.getElementById("sopir_manual").value = data.Sopir || "";

        // Trigger nopol input to set states properly
        document.getElementById("nopol").dispatchEvent(new Event("input"));

        // Handle material
        if (data.id_material) {
          document.getElementById("material-input").value = data.Material || "";
          document.getElementById("id_material").value = data.id_material;
          document.getElementById("material_manual").value = "";
        } else if (data.Material) {
          document.getElementById("material-input").value = data.Material;
          document.getElementById("id_material").value = "";
          document.getElementById("material_manual").value = data.Material;
        }

        // Handle customer
        if (data.id_customers) {
          document.getElementById("cek_customer").checked = true;
          const customerInput = document.getElementById("customer-input");
          customerInput.style.display = "block";
          customerInput.value = data.Customers || "";
          document.getElementById("id_customers").value = data.id_customers;
          document.getElementById("customer_manual").value = "";
        } else if (data.Customers) {
          document.getElementById("cek_customer").checked = true;
          const customerInput = document.getElementById("customer-input");
          customerInput.style.display = "block";
          customerInput.value = data.Customers;
          document.getElementById("id_customers").value = "";
          document.getElementById("customer_manual").value = data.Customers;
        }

        // Handle supplier
        if (data.id_supplier) {
          document.getElementById("cek_supplier").checked = true;
          const supplierInput = document.getElementById("supplier-input");
          supplierInput.style.display = "block";
          supplierInput.value = data.Nama_Supplier || "";
          document.getElementById("id_supplier").value = data.id_supplier;
          document.getElementById("supplier_manual").value = "";
        } else if (data.Nama_Supplier) {
          document.getElementById("cek_supplier").checked = true;
          const supplierInput = document.getElementById("supplier-input");
          supplierInput.style.display = "block";
          supplierInput.value = data.Nama_Supplier;
          document.getElementById("id_supplier").value = "";
          document.getElementById("supplier_manual").value = data.Nama_Supplier;
        }

        document.getElementById("tgl_masuk").value = data.tgl_masuk || "";
        document.getElementById("jam_masuk").value = data.jam_in || "";
        document.getElementById("tgl_keluar").value = data.tgl_keluar || "";
        document.getElementById("jam_keluar").value = data.jam_out || "";

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
          // Reset manual hidden fields
          ["nopol_manual", "sopir_manual", "material_manual", "supplier_manual", "customer_manual"].forEach(
            (id) => (document.getElementById(id).value = "")
          );
          // Reset checkbox displays
          document.getElementById("customer-input").style.display = "none";
          document.getElementById("supplier-input").style.display = "none";
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

    // 7. RS232 Scale Polling
    let pollInterval;

    window.startPolling = () => {
      if (pollInterval) clearInterval(pollInterval);

      pollInterval = setInterval(async () => {
        try {
          const baseUrl = window.CURRENT_BASE_PATH || "./";
          const apiUrl = `${baseUrl}api/scale_logs.php`;

          const weightRes = await fetch(`${apiUrl}?action=latest_weight`);
          const weightData = await weightRes.json();
          if (weightData.parsed_weight !== null) {
            const lw = document.getElementById("latestWeight");
            if (lw) lw.textContent = weightData.parsed_weight.toFixed(2);
            const st = document.getElementById("scaleTime");
            if (st) st.textContent = new Date(weightData.timestamp).toLocaleString();
            const ss = document.getElementById("scaleStatus");
            if (ss) {
              ss.textContent = "🟢 Live";
              ss.style.color = "green";
            }
          }

          window.refreshLogs && window.refreshLogs();
        } catch (e) {
          const ss = document.getElementById("scaleStatus");
          if (ss) {
            ss.textContent = "🔴 Offline";
            ss.style.color = "red";
          }
        }
      }, 3000);

      window.refreshLogs && window.refreshLogs();
    };

    window.refreshLogs = async () => {
      try {
        const baseUrl = window.CURRENT_BASE_PATH || "./";
        const res = await fetch(`${baseUrl}api/scale_logs.php?action=logs&limit=20`);
        const logs = await res.json();

        const table = document.getElementById("logsTable");
        if (!table) return;

        if (logs.length === 0) {
          table.innerHTML = "<em>No logs. Run: python penimbangan.py</em>";
          return;
        }

        table.innerHTML = logs
          .map(
            (log) => `
            <div style="padding:2px 0;border-bottom:1px solid #eee;">
                <strong>${log.parsed_weight?.toFixed(2) || "ERR"}kg</strong>
                <span style="color:#666;font-size:0.8em;">${new Date(log.timestamp).toLocaleTimeString()}</span>
                <span style="float:right;color:${log.status === "success" ? "green" : log.status === "error" ? "red" : "orange"}">[${log.status}]</span>
                <br><small>${log.raw_data?.substring(0, 50) || "N/A"}...</small>
            </div>
        `,
          )
          .reverse()
          .join("");
      } catch (e) {
        console.error("Logs error:", e);
      }
    };

    window.useLatestWeight = () => {
      const weightEl = document.getElementById("latestWeight");
      const weight = parseFloat(weightEl.textContent);
      if (!isNaN(weight)) {
        document.getElementById("bruto").value = weight.toFixed(2);
        const bs = document.getElementById("brutoSource");
        if (bs) {
          bs.textContent = "(scale)";
          bs.style.color = "#1976d2";
        }
        window.calculate();
        showToast("✅ Bruto dari scale!");
      } else {
        showToast("No weight available", false);
      }
    };

    // Start polling if scale elements exist
    if (document.getElementById("scaleStatus")) {
      setTimeout(window.startPolling, 1000);
    }

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

