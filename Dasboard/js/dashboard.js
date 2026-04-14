document.addEventListener("DOMContentLoaded", function () {
  console.log("✅ Dashboard JS Loaded");

  const sidebar = document.getElementById("sidebar");
  const main = document.getElementById("main");
  const hamburgerBtn = document.querySelector(".hamburger-btn");
  const content = document.getElementById("content");
  const overlay = document.querySelector(".sidebar-overlay");
  const pageTitle = document.getElementById("pageTitle");

  if (!sidebar || !main || !hamburgerBtn || !content) {
    console.error("❌ Elemen utama tidak ditemukan");
    return;
  }

  // ================= SIDEBAR =================
  function toggleSidebar() {
    sidebar.classList.toggle("collapsed");
    main.classList.toggle("sidebar-collapsed");
    hamburgerBtn.classList.toggle("active");
    overlay.classList.toggle("active");
  }

  hamburgerBtn.addEventListener("click", function (e) {
    e.preventDefault();
    toggleSidebar();
  });

  overlay.addEventListener("click", toggleSidebar);

  // ================= ACTIVE MENU =================
  window.setActive = function (el) {
    document
      .querySelectorAll(".nav-item")
      .forEach((i) => i.classList.remove("active"));

    el.classList.add("active");

    if (window.innerWidth < 768) {
      toggleSidebar();
    }
  };

  // ================= LOAD CONTENT (FIX CSS + JS) =================
  window.loadContent = async function (url, title = "", el = null) {
    if (title) pageTitle.textContent = title;
    if (el) setActive(el);

    content.innerHTML = `
      <div style="text-align:center;padding:50px;">
        ⏳ Loading...
      </div>
    `;

    try {
      const response = await fetch(url);
      if (!response.ok) throw new Error("HTTP " + response.status);

      const html = await response.text();
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, "text/html");

      // ================= BODY CONTENT =================
      content.innerHTML = doc.body.innerHTML;

      // ================= CSS INJECTION FIX =================
      const cssList = doc.querySelectorAll("link[rel='stylesheet'], style");

      cssList.forEach((node) => {
        const clone = node.cloneNode(true);

        if (node.tagName === "LINK") {
          const exists = [...document.querySelectorAll("link")].some(
            (l) => l.href === node.href,
          );

          if (!exists) {
            document.head.appendChild(clone);
          }
        } else {
          document.head.appendChild(clone);
        }
      });

      // ================= SCRIPT EXECUTION SAFE =================
      const scripts = doc.querySelectorAll("script");

      scripts.forEach((oldScript) => {
        const newScript = document.createElement("script");

        if (oldScript.src) {
          newScript.src = oldScript.src + "?v=" + Date.now();
        } else {
          newScript.textContent = oldScript.textContent;
        }

        document.body.appendChild(newScript);
      });

      console.log("✅ Load sukses + CSS fixed:", url);
    } catch (error) {
      console.error("❌ Load error:", error);

      content.innerHTML = `
        <div style="color:red;text-align:center;padding:40px;">
          ❌ Gagal memuat halaman<br>
          <small>${error.message}</small>
        </div>
      `;
    }
  };

  // ================= LOGOUT =================
  window.confirmLogout = function () {
    if (confirm("Yakin logout?")) {
      window.location.href = "?logout=1";
    }
  };

  // ================= AUTO LOAD =================
  window.addEventListener("load", () => {
    loadContent("sidebar/Input.php", "Input");
  });
});
