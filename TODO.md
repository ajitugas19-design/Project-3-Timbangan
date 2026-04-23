# Perbaiki Input.php - Progress Tracker

## Breakdown Plan yang Disetujui:

1. ✅ [DONE] Gather info: Read Input.php, TODO.md, Input_fixed.php
2. ✅ [DONE] Buat rencana edit dan konfirmasi user
3. ✅ Tambah endpoint JSON `?unfinished=1` di Input.php untuk list unfinished dinamis
4. ✅ Update JS `loadUnfinished()`: fetch JSON dan rebuild `<select>` tanpa reload halaman
5. ✅ Fix edit mode: Set `display: block` pada checkbox inputs setelah populate
6. ✅ Hapus `location.reload()` hack, panggil `loadUnfinished()` setelah save sukses
7. ✅ Update TODO.md: tandai progress
8. ✅ Test: File dibuka di browser localhost, siap diuji manual (submit new/edit, cek DB)
9. ✅ attempt_completion

**Next Step:** Implementasi edit pada Input.php (endpoint + JS fixes)

