# How Solution Uploads Work on GitHub Pages

## Key Difference: PHP vs Static Hosting

| | Answering (PHP Server) | HostedAnswering1.0 (GitHub Pages) |
|---|---|---|
| **Where images go** | Saved as `.jpg` files in `uploads/` folder on server | Stored as **base64 data URLs** in the browser's `localStorage` |
| **Persistence** | Permanent — files exist on disk | **Per-browser only** — clearing browser data loses them |
| **Visible to others** | Yes — all users see the same uploads | **No** — only visible to the user who uploaded |
| **File size** | Actual JPG files (typically 100KB–5MB) | Base64 strings (~33% larger than original, stored in localStorage) |

## How It Works Step-by-Step

### 1. User Takes/Selects a Photo
The user clicks "Upload Solution Image" and picks a photo or takes one with their camera.

### 2. Cropper.js Processes the Image
The image is loaded into Cropper.js for optional cropping/rotating. When confirmed, it is converted to a **base64 data URL** string:
```javascript
const dataUrl = cropperInstance.getCroppedCanvas().toDataURL('image/jpeg', 0.7);
// Result: "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAA..."
```
The `0.7` quality setting compresses the image to reduce size.

### 3. Saved to localStorage
When the user clicks "Save & Solve", the `LocalState.saveSolution()` method stores the entire base64 string inside `localStorage` under the key `collab_overrides`:

```javascript
LocalState.saveSolution(id, currentUser, answer, options, imageDataUrl);
```

The data structure in localStorage looks like:
```json
{
  "collab_overrides": {
    "42": {
      "added_solutions": [
        {
          "solution_id": "sol_local_1709012345_abc123",
          "user": "John",
          "chosen_option": "b",
          "image": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
        }
      ]
    }
  }
}
```

### 4. Displayed from localStorage
When the page renders, solutions added locally are merged with the base data. The `<img>` tag's `src` attribute is set directly to the base64 data URL — no file download needed.

## What About the Pre-Existing `uploads/` Folder?

The `uploads/` folder contains **historical images** that were saved when the app was running on PHP. These are real `.jpg` files that were uploaded to the server before the static conversion.

- Solutions in the JSON data files reference these as `"image": "uploads/solution_42_1772088748.jpg"`
- The browser loads them as normal image files
- **New** uploads on GitHub Pages do NOT create files in this folder — they go to localStorage instead

## Limitations

| Limitation | Details |
|---|---|
| **localStorage size limit** | Browsers typically allow ~5–10MB. Each compressed image is ~50–500KB as base64, so you can store roughly 10–50 images before hitting the limit |
| **Not shared between users** | Each person's uploads are only in their own browser |
| **Lost if browser data is cleared** | Clearing localStorage/cookies removes all locally-added solutions |
| **Not backed up** | Unlike server uploads, these are not in the git repo |

## Summary

> **Pre-existing solution images** (from the PHP era) → stored as `.jpg` files in the `uploads/` folder, visible to everyone.
>
> **New solution images** (uploaded via GitHub Pages) → stored as base64 strings in the browser's `localStorage`, visible only to that user on that browser.
