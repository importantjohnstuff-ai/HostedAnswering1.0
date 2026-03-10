# How Data Storage Works on HostedAnswering (Supabase Edition)

Now that the app has been migrated from Firebase/Local Storage to **Supabase**, data storage works in two main ways: **Database (PostgreSQL)** for text/state and **Storage Buckets** for images.

---

## 1. Structured Data (JSON)

Every change users make—adding a solution, flagging a question, locking a problem, or hiding it—is stored as a JSON object in the Supabase PostgreSQL database.

### The `overrides` Table
The database has a single table named `overrides`. Each row represents **one math problem** that has some sort of user-generated change applied to it. 

The primary key is the problem's ID (e.g., `"42"`).

**Columns in the table:**
- `id` (text): The problem ID.
- `locked_by` (text): The name of the user currently editing/locking the problem.
- `flagged_by` (text): Who flagged the problem (if anyone).
- `flag_reason` (text): Why they flagged it.
- `hidden_by` (text): Who hid the problem.
- `options` (jsonb): Overrides for A, B, C, D text.
- `category_override` (jsonb): Overrides to change the problem's category/subcategory.
- `added_solutions` (jsonb): An array of new solution objects added by users.
- `edited_solutions` (jsonb): Dictionary of edits to existing solutions.
- `deleted_solutions` (jsonb): Array of solution IDs that have been deleted.

### How it Syncs
When the `index.html` page loads, it fetches all rows from the `overrides` table. It then merges these overrides on top of the base JSON data loaded from `all_problems.js`. 

Supabase Realtime (`supabase.channel('custom-all-channel')`) keeps an open WebSocket connection. Whenever any user changes data, Supabase broadcasts that exact row change to everyone else viewing the page instantly.

---

## 2. Image Uploads

Because images are too large to efficiently store directly inside the database as base64 text strings, they are stored in **Supabase Storage**.

### The `solutions` Bucket
You have a public storage bucket named exactly `solutions`. 

### The Upload Process
When a user crops an image and hits "Save":
1. Cropper.js converts the visible image into a **`Blob`** (binary data).
2. The Supabase SDK uploads this Blob directly to the `solutions` bucket.
3. The uploaded file is given a unique path: `{problem_id}/sol_{timestamp}_{random}.jpg` (e.g., `42/sol_17091234567_abc123.jpg`).
4. Supabase immediately returns a **Public URL** for that image (e.g., `https://oyorurrghhybphdmhixv.supabase.co/storage/v1/object/public/solutions/42/sol_17091234567_abc123.jpg`).
5. This URL string is then saved into the `added_solutions` JSON block in the `overrides` database table.

### Security and CORS
Because the `solutions` bucket is set to **Public**, the browser can load the images in `<img>` tags without needing authentication headers. 

Supabase handles CORS (Cross-Origin Resource Sharing) automatically out of the box for domains accessing its public APIs, which is why you no longer get the CORS errors that Firebase Storage threw.

---

## Fallback (Local Storage)
If for any reason Supabase goes down, or you remove the API keys from `supabase-config.js`, the app will automatically fall back to **`localStorage`**. 

In offline/local mode:
- JSON overrides are saved to the browser's local memory.
- Image uploads are saved as massive **Base64** strings inside that local memory instead of as files.
- Nobody else can see your changes except you on that specific browser.
