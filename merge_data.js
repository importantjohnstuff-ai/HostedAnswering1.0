/**
 * merge_data.js — Build script to merge all data/*.json into data/all_problems.js
 * Run: node merge_data.js
 */
const fs = require('fs');
const path = require('path');

const dataDir = path.join(__dirname, 'data');
const outputFile = path.join(dataDir, 'all_problems.js');

function migrateProblem(p, sourceFile) {
    // Migrate legacy format (solved_by/answer/solution_image → solutions[])
    if (!Array.isArray(p.solutions)) {
        p.solutions = [];
        const solvedBy = p.solved_by || null;
        if (solvedBy) {
            p.solutions.push({
                solution_id: 'sol_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                user: solvedBy,
                chosen_option: p.answer || '',
                image: p.solution_image || null
            });
        }
        delete p.solved_by;
        delete p.answer;
        delete p.solution_image;
    }
    // Ensure required fields
    if (p.locked_by === undefined) p.locked_by = null;
    if (p.flagged_by === undefined) p.flagged_by = null;
    if (p.flag_reason === undefined) p.flag_reason = null;
    if (p.hidden_by === undefined) p.hidden_by = null;
    // Tag source file
    p.source_file = sourceFile;
    return p;
}

// Read all JSON files from data/
const files = fs.readdirSync(dataDir)
    .filter(f => f.endsWith('.json') && f !== 'all_problems.json')
    .sort();

let allProblems = [];

for (const file of files) {
    const filePath = path.join(dataDir, file);
    try {
        const content = fs.readFileSync(filePath, 'utf8');
        const problems = JSON.parse(content);
        if (Array.isArray(problems)) {
            problems.forEach(p => migrateProblem(p, file));
            allProblems = allProblems.concat(problems);
        }
    } catch (err) {
        console.error(`Error reading ${file}:`, err.message);
    }
}

// Output as a JS file that sets a global variable (avoids CORS issues with file:// protocol)
const jsContent = 'window.ALL_PROBLEMS = ' + JSON.stringify(allProblems, null, 2) + ';\n';
fs.writeFileSync(outputFile, jsContent);
console.log(`Merged ${allProblems.length} problems from ${files.length} files → data/all_problems.js`);

