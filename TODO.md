# TODO: Enable Manual Input + RS232 Scale Log for Input.php

## Status: Selesai ✅

### Step 1: [✅] Edit `Dasboard/sidebar/Input.php`

- [x] Add hidden input fields for manual text: `nopol_manual`, `sopir_manual`, `material_manual`, `supplier_manual`, `customer_manual`
- [x] Update POST handler to auto-insert new records into `kendaraan`, `material`, `supplier`, `customers` when manual text is provided but ID is missing
- [x] Remove mandatory validation for kendaraan, material, and weight fields
- [x] Remove fallback that forces bruto/tara/netto to 0 when fewer than 2 fields filled - now preserves NULL
- [x] Update `edit` JSON endpoint to return manual text values when FK IDs are null
- [x] Clean up inline HTML - remove broken/duplicate JavaScript, rely on Input_FIXED.js
- [x] **Add RS232 Scale Panel** - Added HTML panel with `scaleStatus`, `latestWeight`, `scaleTime`, `logsTable`, and `useLatestWeight` button

### Step 2: [✅] Edit `Dasboard/js/Input_FIXED.js`

- [x] Update nopol listener: when no datalist match, store value in `nopol_manual` hidden field
- [x] Update sopir listener: when kendaraan is manual, store value in `sopir_manual` hidden field
- [x] Update datalist listeners (`customer-input`, `supplier-input`, `material-input`): when no match, store value in corresponding `*_manual` hidden field
- [x] Update `validateForm()` to allow all fields empty (return true always)
- [x] Update `loadEdit()` to restore manual text values when IDs are null
- [x] Fix form reset to also clear manual hidden fields
- [x] **Add RS232 scale polling functions** - `startPolling()`, `refreshLogs()`, `useLatestWeight()` with 3-second interval to `api/scale_logs.php`

### Step 3: [✅] Testing

- [x] PHP syntax validated: `php -l Dasboard/sidebar/Input.php` = No syntax errors detected
- [x] File structure verified correct
- [x] RS232 panel elements present in Input.php
- [x] JavaScript polling logic present in Input_FIXED.js

## Summary

Input.php sekarang mendukung:

1. **Input manual** - ketik nopol/material/supplier/customer baru yang belum ada di database → auto-insert
2. **Simpan kosong** - data bisa disimpan meskipun semua field kosong (kecuali no_record & waktu yang auto-generated)
3. **RS232 Timbangan Digital** - panel live menampilkan berat terakhir, status koneksi, log 20 data terakhir, dan tombol "Ambil ke Bruto"

Logika bekerja:

- Jika user memilih dari datalist → ID ter-set, manual field kosong
- Jika user mengetik teks yang tidak cocok datalist → ID kosong, manual field terisi → auto-insert saat submit
- Jika user tidak mengisi apa pun → semua NULL/0 tersimpan ke database sebagai "unfinished record"
- Panel timbangan polling tiap 3 detik ke `api/scale_logs.php` untuk menampilkan data dari tabel `scale_logs`
- Tombol "Ambil ke Bruto" mengisi field bruto dari berat timbangan terakhir dan otomatis menghitung netto/tara

## Backend Requirements

- `penimbangan.py` - script Python untuk baca serial RS232 (COM3) dan simpan ke DB
- `scale_logs.php` - API PHP yang serve data log timbangan
- Jalankan: `python penimbangan.py --flask` atau `python penimbangan.py` untuk monitoring
