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

  // ================= SIDEBAR TOGGLE =================
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

  // ================= SET ACTIVE MENU =================
  window.setActive = function (el) {
    document.querySelectorAll(".nav-item").forEach(i => i.classList.remove("active"));
    el.classList.add("active");

    // Auto close sidebar (mobile)
    if (window.innerWidth < 768) {
      toggleSidebar();
    }
  };

  // ================= LOAD CONTENT =================
  window.loadContent = async function (url, title = "", el = null) {
    if (title) pageTitle.textContent = title;

    // Active menu
    if (el) setActive(el);

    // Loading UI
    content.innerHTML = `
      <div style="text-align:center;padding:50px;">
        <div style="font-size:18px;">⏳ Loading...</div>
      </div>
    `;

    try {
      const response = await fetch(url);

      if (!response.ok) {
        throw new Error("HTTP error " + response.status);
      }

      const html = await response.text();
      content.innerHTML = html;

      // ================= FIX SCRIPT =================
      // Jalankan ulang script dari halaman yang di-load
      const scripts = content.querySelectorAll("script");
      scripts.forEach(oldScript => {
        const newScript = document.createElement("script");
        if (oldScript.src) {
          newScript.src = oldScript.src;
        } else {
          newScript.textContent = oldScript.textContent;
        }
        document.body.appendChild(newScript);
      });

      console.log("✅ Load sukses:", url);

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

  // ================= AUTO LOAD DEFAULT =================
  window.addEventListener("load", () => {
    loadContent("sidebar/Input.php", "Input");
  });

});