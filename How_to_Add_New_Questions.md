# How to Add New Questions to the Data Folder

## Overview

The application loads all `*.json` files from the `data/` folder automatically via `api.php`. Each file is a JSON array of question objects with **sequential IDs** and a **batch naming convention**.

---

## Current State (as of Feb 25, 2026)

| Prefix | Range | Category |
|---|---|---|
| `batch_` | 001–045 | Math |
| `engineering_batch_` | 001–017 | Engineering Sciences |
| `professional_batch_` | 001–040 | Professional Electrical Engineering |

- **Latest ID used:** 1988
- **Latest batch file:** `batch_045.json`
- **Items per batch file:** 20 (last file in a series may have fewer)

---

## Required JSON Schema

Each question must follow this format:

```json
{
    "id": 1989,
    "question": "Your question text here",
    "options": {
        "a": "Option A",
        "b": "Option B",
        "c": "Option C",
        "d": "Option D"
    },
    "answer": "a",
    "correct_answer_text": "Option A",
    "category": "Math",
    "sub_category": "Advanced Math"
}
```

### Field Details

| Field | Type | Required | Description |
|---|---|---|---|
| `id` | number | ✅ | Unique, sequential integer. Must continue from the last used ID. |
| `question` | string | ✅ | The question text. |
| `options` | object | ✅ | Object with keys `a`, `b`, `c`, `d`. |
| `answer` | string | ✅ | Correct answer key (`"a"`, `"b"`, `"c"`, or `"d"`). Can be `""` if unknown. |
| `correct_answer_text` | string | ✅ | Text of the correct answer. Can be `""` if unknown. |
| `category` | string | ✅ | One of: `Math`, `Engineering Sciences`, `Professional Electrical Engineering`. |
| `sub_category` | string | ✅ | A topic within the category (see list below). |

### Known Sub-Categories

- **Math:** Advanced Math, Differential Calculus, Integral Calculus, Differential Equation, Laplace Transform, Vector Analysis, Numerical Methods, Probability and Statistics, Geometry, Calculator Technique, Algebra, Trigonometry
- **Engineering Sciences:** Chemistry, Physics, Thermodynamics, Fluid Mechanics, Mechanics, Strength of Materials, Economy
- **Professional Electrical Engineering:** (various EE topics)

---

## Step-by-Step: Adding New Files

### 1. Find the Current Max ID

Open the **last batch file** in the `data/` folder (sorted alphabetically) and check the last item's `id`. Or run:

```bash
node -e "const fs=require('fs'),p=require('path'),d='data';let mx=0;fs.readdirSync(d).filter(f=>f.endsWith('.json')).forEach(f=>{JSON.parse(fs.readFileSync(p.join(d,f),'utf8')).forEach(q=>{if(q.id>mx)mx=q.id})});console.log('Max ID:',mx)"
```

### 2. Find the Next Batch Number

Check which batch files already exist for your category prefix:

| Category | Prefix | Example |
|---|---|---|
| Math | `batch_` | `batch_046.json` |
| Engineering Sciences | `engineering_batch_` | `engineering_batch_018.json` |
| Professional EE | `professional_batch_` | `professional_batch_041.json` |

### 3. Prepare Your Questions

- Ensure each question has all required fields.
- Assign **sequential IDs** starting from `maxID + 1`.
- Group into batches of **20 questions** per file.
- The last file may have fewer than 20.

### 4. Save to the Data Folder

Name files using the correct prefix and zero-padded three-digit numbering:

```
data/batch_046.json
data/batch_047.json
...
```

### 5. Validate

Confirm each file is **valid JSON** (array of objects), IDs are sequential with no gaps or duplicates, and all required fields are present.

---

## Quick Reference: Naming Convention

```
{prefix}_{three-digit-number}.json
```

- Pad the number with leading zeros: `001`, `002`, ..., `045`, `046`
- Always use lowercase filenames
- Each file should be a JSON array `[ {...}, {...}, ... ]`

---

## Common Mistakes to Avoid

1. **Duplicate IDs** — Always check the current max ID before assigning new ones.
2. **Invalid JSON** — Validate with a JSON linter or `JSON.parse()` before saving.
3. **Missing fields** — Every question must have all 7 fields, even if `answer` or `correct_answer_text` is empty (`""`).
4. **Wrong batch size** — Keep batches at 20 items max for consistency.
5. **Inconsistent category names** — Use exact category strings (case-sensitive).
