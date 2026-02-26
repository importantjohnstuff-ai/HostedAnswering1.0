# Blueprint: Advanced Image Editing, Multi-File Support, & Filtering

## 1. Multi-File Backend Support (`api.php`)
- [ ] Create a new folder named `data` inside your project directory (e.g., `htdocs/math-collab/data/`).
- [ ] Move all 50+ of your JSON files into this `data/` folder.
- [ ] **Update the `get` action:** - Use PHP's `glob('data/*.json')` to find all files.
  - Loop through each file, `json_decode` its contents, and append a temporary `"source_file": $filename` key to each problem object so the backend knows where it came from.
  - Merge them all into one massive array and `json_encode` it to send to the frontend.
- [ ] **Update `lock`, `unlock`, `flag`, and `save` actions:**
  - Since the frontend will now send back the `source_file` alongside the problem `id`, update your PHP script to open *only* that specific file, modify the problem, and save it using `file_put_contents()`.

## 2. Image Cropping & Rotation (`index.html`)
- [ ] **Include Cropper.js:** Add the CDN links for Cropper.js to your `<head>`:
  ```html
  <link href="[https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css](https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css)" rel="stylesheet">
  <script src="[https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js](https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js)"></script>
[ ] Create the Editing Modal: Build a new hidden modal <div> in your HTML for the image editor. It should contain an <img> tag (where the raw photo will load), a "Rotate 90°" button, a "Confirm Crop" button, and a "Cancel" button.

[ ] Update Camera Input Logic: - Change handleCameraInput so that instead of just previewing the image, it opens the Editing Modal, loads the image file into the modal's <img> tag, and initializes new Cropper(imageElement, { viewMode: 1 }).

[ ] Process the Edit: - When the user clicks "Rotate", call cropper.rotate(90).

When the user clicks "Confirm Crop", call cropper.getCroppedCanvas().toDataURL('image/jpeg', 0.7) to get the compressed, edited base64 string.

Close the modal and display this final image in the preview area. (You no longer need your custom HTML5 canvas compression function, as Cropper.js handles it!).

3. UI Filters & Display Limits (index.html)
[ ] Build the Control Panel: Add a container fixed or floating in the top right corner of your UI.

[ ] Add Filter Dropdowns:

Status: "All", "Solved Only", "Unsolved Only".

Category: Dynamically populate this dropdown based on the unique categories in your loaded JSON data.

Subcategory: Dynamically populate based on the selected category.

[ ] Add Limit Dropdown: - Show: 10, 20, 50, 100 (Default to 20).

[ ] Store Global Data: In your JS, when fetchData() receives the massive array from api.php, save it to a global variable (e.g., let allProblems = []).

4. Frontend Filtering Logic (index.html JS)
[ ] Create an applyFilters() function: This function will run right before renderProblems().

[ ] Filter Status: If "Solved Only" is selected, filter the array for p.solved_by !== null. If "Unsolved", filter for p.solved_by === null.

[ ] Filter Categories: Filter the array where p.category === selectedCategory and p.sub_category === selectedSubcategory.

[ ] Apply the Limit (Pagination): - After all filters are applied, slice the array using the dropdown value: let problemsToDisplay = filteredArray.slice(0, parseInt(limitDropdown.value)).

[ ] Pass problemsToDisplay into your existing renderProblems(data) function.

[ ] Add event listeners to all your dropdowns so that whenever a user changes a filter or limit, applyFilters() runs immediately to update the screen.