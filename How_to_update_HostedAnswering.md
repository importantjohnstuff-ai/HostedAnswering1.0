# How to Update HostedAnswering1.0

This guide ensures a future agent or developer can keep HostedAnswering1.0 (the GitHub Pages-hosted static version) in sync with the main `Answering` folder (the PHP-based working copy).

## Architecture Overview

| Component | Answering (Source) | HostedAnswering1.0 (GitHub Pages) |
|---|---|---|
| **Backend** | `api.php` (PHP server) | None — fully static |
| **Data persistence** | Server writes to JSON files | `localStorage` via `LocalState` manager |
| **Data loading** | `fetch('api.php?action=get')` | `<script src="data/all_problems.js">` |
| **Data files** | `data/*.json` (individual batch files) | Same JSON files + merged `data/all_problems.js` |
| **Uploads** | `uploads/*.jpg` (solution images) | Same images (static copies) |

## Prerequisites

- **Node.js** installed (for running `merge_data.js`)
- Access to both `Answering/` and `HostedAnswering1.0/` folders

## Update Checklist

### Step 1: Sync Data Files

Copy **all** JSON batch files from `Answering/data/` → `HostedAnswering1.0/data/`:

```powershell
Copy-Item -Path "Answering\data\batch_*.json" -Destination "HostedAnswering1.0\data\" -Force
Copy-Item -Path "Answering\data\engineering_*.json" -Destination "HostedAnswering1.0\data\" -Force
Copy-Item -Path "Answering\data\professional_*.json" -Destination "HostedAnswering1.0\data\" -Force
```

**Verification:** The file count in both `data/` folders should match (excluding `all_problems.js` and `data.json` in hosted):
```powershell
(Get-ChildItem "Answering\data\*.json").Count
(Get-ChildItem "HostedAnswering1.0\data\*.json").Count
```

### Step 2: Sync Upload Images

Copy **all** solution images from `Answering/uploads/` → `HostedAnswering1.0/uploads/`:

```powershell
Copy-Item -Path "Answering\uploads\*" -Destination "HostedAnswering1.0\uploads\" -Force
```

**Verification:**
```powershell
(Get-ChildItem "Answering\uploads\*").Count
(Get-ChildItem "HostedAnswering1.0\uploads\*").Count
```

### Step 3: Rebuild `all_problems.js`

This is the critical step. The static site loads all data from a single pre-merged JS file.

```powershell
cd HostedAnswering1.0
node merge_data.js
```

**Expected output:** `Merged N problems from M files → data/all_problems.js`

The counts should match the source data. As of the last update (2026-02-27):
- **126 JSON files** (69 math batches + 17 engineering batches + 40 professional batches)
- **2430 total problems**
- **177 upload images**

### Step 4: Verify No PHP References

The hosted version must be pure HTML/CSS/JS. Verify:

```powershell
Select-String -Path "HostedAnswering1.0\index.html" -Pattern "api\.php|fetchData|\.php"
```

**Expected:** No matches.

### Step 5: Verify `api.php` Does NOT Exist

```powershell
Test-Path "HostedAnswering1.0\api.php"
```

**Expected:** `False`

### Step 6: Test in Browser

Open `HostedAnswering1.0/index.html` in a browser and verify:

- [ ] Problems load and display with correct count
- [ ] Search modal works (🔍 button)
- [ ] GoTo Problem ID works
- [ ] Pagination with page numbers works
- [ ] Solution images load from `uploads/`
- [ ] Lock/Unlock/Save/Flag/Hide/Delete all work (localStorage-based)
- [ ] MathJax renders math expressions

### Step 7: Commit and Push to GitHub

```bash
cd HostedAnswering1.0
git add .
git commit -m "Sync data and uploads from Answering"
git push
```

## Important File Reference

| File | Purpose | Editable? |
|---|---|---|
| `index.html` | Main app (HTML + CSS + JS, all-in-one) | Yes — but convert any PHP to localStorage |
| `merge_data.js` | Node script to build `all_problems.js` from batch JSONs | Rarely |
| `data/all_problems.js` | Auto-generated merged data (DO NOT EDIT) | No — rebuild via `node merge_data.js` |
| `data/batch_*.json` | Math problem batches (20 problems each) | Source of truth is `Answering/data/` |
| `data/engineering_*.json` | Engineering problem batches | Source of truth is `Answering/data/` |
| `data/professional_*.json` | Professional problem batches | Source of truth is `Answering/data/` |
| `uploads/*.jpg` | Solution images | Source of truth is `Answering/uploads/` |
| `.nojekyll` | Tells GitHub Pages not to use Jekyll | Keep as-is |
| `data.json` | Legacy config file | Can be ignored |

## Key Rules for Future Updates

1. **Never add `api.php` or any PHP** — GitHub Pages only serves static files
2. **All write operations use `LocalState`** (localStorage) — there is no server
3. **Always run `node merge_data.js`** after adding/changing any JSON data files
4. **The `Answering/` folder is the source of truth** for data and uploads — always copy FROM it
5. **`index.html` should have feature parity** with `Answering/index.html`, but with PHP calls replaced by `LocalState` methods
6. **New features in `Answering/index.html`** (which use `api.php`) must be converted to use `LocalState.methodName()` + `refreshFromLocal()` instead of `fetch('api.php...') + fetchData()`

### PHP → localStorage Conversion Pattern

When porting a new PHP-based action from `Answering/index.html`:

```javascript
// BEFORE (PHP version in Answering/index.html):
async function someAction(id, sf) {
    const formData = new FormData();
    formData.append('action', 'some_action');
    formData.append('id', id);
    formData.append('source_file', decodeURIComponent(sf));
    await fetch('api.php', { method: 'POST', body: formData });
    fetchData();
}

// AFTER (localStorage version in HostedAnswering1.0/index.html):
function someAction(id, sf) {
    LocalState.someAction(id, currentUser);
    refreshFromLocal();
}
```
