// Dashboard JS Minimal - Toggle + LoadContent
document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.getElementById("sidebar");
  const main = document.getElementById("main");
  const hamburgerBtn = document.querySelector(".hamburger-btn");
  const content = document.getElementById("content");
  const overlay = document.querySelector(".sidebar-overlay");

  // Default expanded (no localStorage for simplicity)
  if (sidebar) sidebar.classList.remove("collapsed");
  if (main) main.classList.remove("sidebar-collapsed");
  if (hamburgerBtn) hamburgerBtn.classList.remove("active");
  if (overlay) overlay.classList.remove("active");

  window.toggleSidebar = function () {
    sidebar.classList.toggle("collapsed");
    main.classList.toggle("sidebar-collapsed");
    hamburgerBtn.classList.toggle("active");
    overlay.classList.toggle("active");
  };

  window.loadContent = async function (url, title = "") {
    const pageTitle = document.getElementById("pageTitle");
    if (title) pageTitle.textContent = title;

    content.innerHTML = '<div class="loading">Loading...</div>';

    try {
      const response = await fetch(url);
      content.innerHTML = await response.text();
    } catch (error) {
      content.innerHTML = '<div style="color:red;padding:20px;">Error loading content</div>';
    }
  };

  window.confirmLogout = function () {
    if (confirm("Yakin logout?")) location.href = "?logout=1";
  };
});
