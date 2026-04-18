# Fix Login to Match User Table Passwords

**Status: ✅ Complete**

## Steps:

- [x] **Plan approved** by user
- [x] **Update login.php** with password_verify() + MD5 fallback for legacy users
- [x] **Test login**:
  - Legacy MD5 user (e.g., 'aji') ✅
  - New bcrypt user (create via User.php) ✅
- [x] **Done**: Task complete, login now works with both hash types

**Notes:**

- Supports mixed hashes temporarily
- User table: `user` (username), `password` (bcrypt or MD5)
