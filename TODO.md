# Perbaiki users.php (Dasboard/sidebar/User.php)

**Status:** Active

**Issues Found:**

- Undefined variable `$isCurrent` line ~204 (PHP template in JS)
- PHP echo breaks JS template literal syntax
- Admin ID=1 hardcoded even if missing from DB
- `$_GET['edit']` data not passed to JS properly
- api/users.php missing (tab ghost)

**Plan Steps:**

1. ✅ Create TODO.md (done)
2. Fix PHP/JS syntax errors in Dasboard/sidebar/User.php
3. Create api/users.php JSON API (GET/POST CRUD)
4. Convert form submits to AJAX calls
5. Test: Add/edit/delete user, check DB
6. Update TODO.md, attempt_completion

**Next:** Edit User.php
