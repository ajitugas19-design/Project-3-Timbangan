// Clean Dashboard JS - Fixed nav clicks + automatic features
document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.getElementById("sidebar");
  const main = document.getElementById("main");
  const hamburgerBtn = document.querySelector(".hamburger-btn");
  const content = document.getElementById("content");
  const overlay = document.querySelector(".sidebar-overlay") || createOverlay();

  // Initial sidebar state
  const isCollapsed =
    localStorage.getItem("sidebarCollapsed") === "true" ||
    window.innerWidth <= 768;
  if (sidebar) sidebar.classList.toggle("collapsed", isCollapsed);
  if (main) main.classList.toggle("sidebar-collapsed", isCollapsed);
  if (hamburgerBtn) hamburgerBtn.classList.toggle("active", isCollapsed);
  if (overlay) overlay.classList.toggle("active", isCollapsed);

  // Toggle sidebar (works everywhere)
  window.toggleSidebar = function () {
    sidebar.classList.toggle("collapsed");
    main.classList.toggle("sidebar-collapsed");
    hamburgerBtn.classList.toggle("active");
    overlay.classList.toggle("active");

    const collapsed = sidebar.classList.contains("collapsed");
    localStorage.setItem("sidebarCollapsed", collapsed);
  };

  // Load content (nav clicks work!)
  window.loadContent = async function (url, title = "") {
    const pageTitle = document.getElementById("pageTitle");
    if (title) pageTitle.textContent = title;

    content.innerHTML = `
      <div class="loading flex flex-col items-center justify-center" style="min-height: 50vh;">
        <div class="spinner" style="width: 48px; height: 48px;"></div>
        <p style="margin-top: 1rem; color: #6b7280;">Loading ${title}...</p>
      </div>
    `;

    try {
      const response = await fetch(url);
      const html = await response.text();
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, "text/html");

      content.innerHTML = doc.body.innerHTML;

      // Execute loaded scripts
      const scripts = Array.from(doc.scripts);
      scripts.forEach((script) => {
        const newScript = document.createElement("script");
        newScript.textContent = script.textContent;
        document.head.appendChild(newScript);
        document.head.removeChild(newScript);
      });

      showToast("Content loaded successfully!");
    } catch (error) {
      content.innerHTML = `
        <div class="card p-4 text-center">
          <h3 style="color: #ef4444;">Error loading page</h3>
          <p>Please try again</p>
          <button class="btn btn-primary" onclick="location.reload()">Reload</button>
        </div>
      `;
      showToast("Failed to load content", "error");
    }
  };

  // Auto load default page
  setTimeout(() => loadContent("sidebar/Input.php", "Input Transaksi"), 800);

  // Logout
  window.confirmLogout = function () {
    if (confirm("Yakin logout?")) {
      location.href = "?logout=1";
    }
  };

  // Toast notifications
  window.showToast = function (message, type = "success") {
    const toast = document.createElement("div");
    toast.className = `toast toast-${type} show`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  };

  // Form auto calculations (Input.php)
  function initForms() {
    // Netto calculation
    const bruto = document.getElementById("bruto");
    const tara = document.getElementById("tara");
    const netto = document.getElementById("netto");
    if (bruto && tara && netto) {
      const calcNetto = () =>
        (netto.value =
          (parseFloat(bruto.value) || 0) - (parseFloat(tara.value) || 0));
      bruto.addEventListener("input", calcNetto);
      tara.addEventListener("input", calcNetto);
    }

    // Nopol autofill
    const nopol = document.getElementById("nopol");
    const sopir = document.getElementById("sopir");
    const kendaraanId = document.getElementById("id_kendaraan");
    if (nopol && sopir) {
      nopol.addEventListener("input", () => {
        document.querySelectorAll("#listNopol option").forEach((opt) => {
          if (opt.value === nopol.value) {
            sopir.value = opt.dataset.sopir || "";
            if (kendaraanId) kendaraanId.value = opt.dataset.id || "";
          }
        });
      });
    }
  }

  // Re-init forms on content change
  const observer = new MutationObserver(initForms);
  observer.observe(content, { childList: true, subtree: true });
  initForms();

  // Table search
  document.addEventListener("input", (e) => {
    if (e.target.classList.contains("table-filter")) {
      const table = document.getElementById(e.target.dataset.table);
      if (table) {
        Array.from(table.querySelectorAll("tbody tr")).forEach((row) => {
          row.style.display = row.textContent
            .toLowerCase()
            .includes(e.target.value.toLowerCase())
            ? ""
            : "none";
        });
      }
    }
  });

  function createOverlay() {
    const overlay = document.createElement("div");
    overlay.className = "sidebar-overlay";
    overlay.onclick = toggleSidebar;
    document.body.appendChild(overlay);
    return overlay;
  }

  window.addEventListener("resize", () => {
    if (
      window.innerWidth > 768 &&
      localStorage.getItem("sidebarCollapsed") !== "true"
    ) {
      sidebar.classList.remove("collapsed");
      main.classList.remove("sidebar-collapsed");
      hamburgerBtn.classList.remove("active");
      overlay.classList.remove("active");
    }
  });
});

// Debounce utility
window.debounce = (func, wait) => {
  let timeout;
  return (...args) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
};
