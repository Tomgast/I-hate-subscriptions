# 🔧 SYSTEMATIC PATH FIX LIST
## Critical Path Reference Issues Found

### IMMEDIATE FIXES NEEDED

#### 1. Plan Manager ✅ FIXED
- **File:** `includes/plan_manager.php`
- **Issue:** `require_once __DIR__ . '/secure_loader.php';`
- **Fix:** `require_once __DIR__ . '/../config/secure_loader.php';`
- **Status:** ✅ FIXED - Ready for GitHub push

#### 2. Bank Service Files
- **File:** `bank/scan.php` (lines 8-10)
- **Current:** Uses `../config/db_config.php` ✅ CORRECT
- **Current:** Uses `../includes/plan_manager.php` ✅ CORRECT  
- **Current:** Uses `../includes/bank_service.php` ✅ CORRECT
- **Status:** ✅ PATHS LOOK CORRECT

#### 3. Auth Files
- **File:** `auth/google-callback.php`
- **Current:** Uses `../config/db_config.php` ✅ CORRECT
- **Current:** Uses `../config/secure_loader.php` ✅ CORRECT
- **Status:** ✅ PATHS LOOK CORRECT

#### 4. API Files  
- **File:** `api/config.php`
- **Current:** Uses `__DIR__ . '/../config/secure_loader.php'` ✅ CORRECT
- **Status:** ✅ PATHS LOOK CORRECT

### PATTERN ANALYSIS

**✅ CORRECT PATTERNS FOUND:**
- `../config/db_config.php` (from subdirectories)
- `../config/secure_loader.php` (from subdirectories) 
- `config/db_config.php` (from root)
- `__DIR__ . '/../config/secure_loader.php'` (absolute)

**❌ INCORRECT PATTERNS FOUND:**
- `__DIR__ . '/secure_loader.php'` (looking in wrong directory) ✅ FIXED

### NEXT VERIFICATION STEPS

1. **Push the Plan Manager fix to GitHub**
2. **Test emergency diagnostic again**
3. **If still errors, check these files next:**
   - `includes/stripe_service.php` - verify its path references
   - `includes/bank_service.php` - verify its path references  
   - `includes/header.php` - verify its path references

### TESTING SEQUENCE

After GitHub push:
1. Test: `https://123cashcontrol.com/emergency-diagnosis.php`
2. Test: `https://123cashcontrol.com/upgrade.php`
3. Test: `https://123cashcontrol.com/dashboard.php`
4. Test: Login flow

### SYSTEMATIC APPROACH

- ✅ Fix one file at a time
- ✅ Push to GitHub after each fix
- ✅ Test immediately after each push
- ✅ Document results before next fix
- ✅ No parallel changes

---

**CURRENT ACTION NEEDED:** Push Plan Manager fix to GitHub, then test emergency diagnostic manually.
