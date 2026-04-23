# Perbaikan Logout

Status: Progress...

## Steps:

- [x] 1. Tambah `window.logout = confirmLogout;` di `Dasboard/js/dashboard.js` (fix onclick)
- [x] 2. Tambah `ob_start()` di `Dasboard/Navbar.php` (output buffering)
- [x] 3. Fix redirect di `Index.php` (konsistensi)
- [x] Debug: onclick → confirmLogout() direct (no cache issue)
- [ ] 4. Test: Login → Logout → Verify redirect & session clear

Updated: [timestamp]
