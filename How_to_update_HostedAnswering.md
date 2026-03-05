# How to Update HostedAnswering

This guide ensures a future agent or developer can keep HostedAnswering (the GitHub Pages-hosted static version) in sync with the main `Answering` folder (the PHP-based working copy).

## Architecture Overview

| Component | Answering (Source) | HostedAnswering (GitHub Pages) |
|---|---|---|
| **Backend** | `api.php` (PHP server) | None — fully static |
| **Data persistence** | Server writes to JSON files | `localStorage` via `LocalState` manager |
| **Data loading** | `fetch('api.php?action=get')` | `<script src="data/all_problems.js">` |
| **Data files** | `data/*.json` (individual batch files) | Same JSON files + merged `data/all_problems.js` |
| **Uploads** | `uploads/*.jpg` (solution images) | Same images (static copies) |

## Prerequisites

- **Node.js** installed (for running `merge_data.js`)
- Access to both `Answering/` and `HostedAnswering/` folders

## Update Checklist

### Step 1: Sync Data Files

Copy **all** JSON batch files from `Answering/data/` → `HostedAnswering/data/`:

```powershell
Copy-Item -Path "Answering\data\batch_*.json" -Destination "HostedAnswering\data\" -Force
Copy-Item -Path "Answering\data\engineering_*.json" -Destination "HostedAnswering\data\" -Force
Copy-Item -Path "Answering\data\professional_*.json" -Destination "HostedAnswering\data\" -Force
```

**Verification:** The file count in both `data/` folders should match (excluding `all_problems.js` and `data.json` in hosted):
```powershell
(Get-ChildItem "Answering\data\*.json").Count
(Get-ChildItem "HostedAnswering\data\*.json").Count
```

### Step 2: Sync Upload Images

Copy **all** solution images from `Answering/uploads/` → `HostedAnswering/uploads/`:

```powershell
Copy-Item -Path "Answering\uploads\*" -Destination "HostedAnswering\uploads\" -Force
```

**Verification:**
```powershell
(Get-ChildItem "Answering\uploads\*").Count
(Get-ChildItem "HostedAnswering\uploads\*").Count
```

### Step 3: Rebuild `all_problems.js`

This is the critical step. The static site loads all data from a single pre-merged JS file.

```powershell
cd HostedAnswering
node merge_data.js
```

**Expected output:** `Merged N problems from M files → data/all_problems.js`

The counts should match the source data. As of the last update (2026-03-03):
- **126 JSON files** (69 math batches + 17 engineering batches + 40 professional batches)
- **2430 total problems**
- **177 upload images**

### Step 4: Verify No PHP References

The hosted version must be pure HTML/CSS/JS. Verify:

```powershell
Select-String -Path "HostedAnswering\index.html" -Pattern "api\.php|fetchData|\.php"
```

**Expected:** No matches.

### Step 5: Verify `api.php` Does NOT Exist

```powershell
Test-Path "HostedAnswering\api.php"
```

**Expected:** `False`

### Step 6: Test in Browser

Open `HostedAnswering/index.html` in a browser and verify:

- [ ] Problems load and display with correct count
- [ ] Search modal works (🔍 button)
- [ ] GoTo Problem ID works
- [ ] Pagination with page numbers works
- [ ] Solution images load from `uploads/`
- [ ] Lock/Unlock/Save/Flag/Hide/Delete all work (localStorage-based)
- [ ] MathJax renders math expressions
- [ ] **Generate Quiz** button opens the quiz modal and produces a randomized sub-set of problems
- [ ] **Exit Quiz** button returns to normal view
- [ ] **Edit Category** button (✏️) on unlocked cards opens the inline category/sub-category editor and saves correctly

### Step 7: Commit and Push to GitHub

```bash
cd HostedAnswering
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

// AFTER (localStorage version in HostedAnswering/index.html):
function someAction(id, sf) {
    LocalState.someAction(id, currentUser);
    refreshFromLocal();
}
```

---

## Features Added Since Last Sync — Porting Guide

The following features exist in `Answering/index.html` and need to be carried over (or verified as already present) whenever `HostedAnswering/index.html` is updated.

---

### Feature 1: Generate Quiz (📝)

**Added:** 2026-03-03 | **Conversation:** Adding Quiz Generation Feature

**What it does:**
A "📝 Generate Quiz" button opens a modal where the user selects subtopics from three category columns (MATH, Engineering Sciences, Professional) and specifies a total question count. The app picks questions evenly distributed across selected subtopics and enters "quiz mode". An "❌ Exit Quiz" button returns to normal view.

**Key functions in `Answering/index.html`:**

| Function | Purpose |
|---|---|
| `openQuizModal()` | Shows the `#quiz-modal` overlay |
| `generateQuiz()` | Reads selected `.quiz-subtopic` checkboxes + total count, samples problems, sets `quizMode = true`, calls `renderProblems()` |
| `exitQuizMode()` | Clears `quizMode`, restores normal render |

**HTML elements required:**
- `<button onclick="openQuizModal()">📝 Generate Quiz</button>` in the toolbar
- `<button id="exit-quiz-btn" onclick="exitQuizMode()">❌ Exit Quiz</button>` (hidden by default, shown in quiz mode)
- `<div id="quiz-modal">` overlay with three `.quiz-column` divs (one per category) containing `.quiz-subtopic` checkboxes

**CSS classes required:** `.quiz-modal`, `.quiz-columns`, `.quiz-column`, `.quiz-actions`

**Porting note:** The quiz feature is **purely read-only** — it only filters the in-memory `allProblems` array and does not write to `api.php`. Copy the modal HTML, CSS, and JS functions verbatim from `Answering/index.html`; no localStorage conversion needed.

---

### Feature 2: Edit Category / Sub-Category (✏️)

**Added:** 2026-03-05 | **Conversation:** Implement Category Edit

**What it does:**
An "✏️ Edit Category" button appears on every unlocked problem card (next to "✏️ Edit Choices"). Clicking it reveals an inline panel with two select/text inputs pre-filled with the question's current `category` and `sub_category`. Saving posts to `api.php?action=edit_category` in Answering, but **must use `LocalState` in HostedAnswering**.

**Key functions in `Answering/index.html`:**

| Function | Purpose |
|---|---|
| `toggleEditCategory(probId, sf)` | Toggles `#edit-cat-{id}` div visibility |
| `saveEditCategory(probId, sf)` | Posts `edit_category` action to `api.php`, calls `fetchData()` |

**PHP → localStorage conversion for HostedAnswering:**

```javascript
// BEFORE (Answering/index.html — uses api.php):
async function saveEditCategory(probId, sf) {
    const category = document.getElementById(`edit-cat-cat-${probId}`).value.trim();
    const sub_category = document.getElementById(`edit-cat-sub-${probId}`).value.trim();
    const formData = new FormData();
    formData.append('action', 'edit_category');
    formData.append('id', probId);
    formData.append('category', category);
    formData.append('sub_category', sub_category);
    formData.append('source_file', decodeURIComponent(sf));
    await fetch('api.php', { method: 'POST', body: formData });
    fetchData();
}

// AFTER (HostedAnswering/index.html — uses LocalState):
function saveEditCategory(probId, sf) {
    const category = document.getElementById(`edit-cat-cat-${probId}`).value.trim();
    const sub_category = document.getElementById(`edit-cat-sub-${probId}`).value.trim();
    LocalState.editCategory(probId, category, sub_category);
    refreshFromLocal();
}
```

> **Note:** You must also add a `LocalState.editCategory(id, category, subCategory)` method that writes the updated values into `collab_overrides[id]` in localStorage, so they persist across page reloads.

**Inline panel HTML** (generated inside `renderProblems()`, appended to each card):
```html
<div id="edit-cat-{id}" style="display:none">
  <div class="inline-edit-options" style="grid-template-columns: auto 1fr;">
    <label>Category</label>
    <select id="edit-cat-cat-{id}"><!-- options from CATEGORIES --></select>
    <label>Sub-Category</label>
    <select id="edit-cat-sub-{id}"><!-- options from SUB_CATEGORIES --></select>
  </div>
  <button onclick="saveEditCategory({id},'{sf}')">💾 Save Category</button>
  <button onclick="...hide panel...">Cancel</button>
</div>
```
