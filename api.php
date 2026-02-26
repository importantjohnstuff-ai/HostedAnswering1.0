<?php
// Disable showing errors as HTML to avoid breaking JSON response
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$dataDir = 'data/';
$uploadsDir = 'uploads/';

// Helper to send response
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Global error handler to return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    sendResponse([
        'error' => 'Internal PHP Error',
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ], 500);
});

$action = $_REQUEST['action'] ?? 'get';
$id = $_REQUEST['id'] ?? null;
$user = $_REQUEST['user'] ?? null;
$answer = $_REQUEST['answer'] ?? null;
$options = $_REQUEST['options'] ?? null;
$image = $_REQUEST['image'] ?? null;
$reason = $_REQUEST['reason'] ?? null;
$sourceFile = $_REQUEST['source_file'] ?? null;
$solutionId = $_REQUEST['solution_id'] ?? null;

// ======== Helper: migrate legacy single-solution to solutions[] ========
function migrateProblem(&$p) {
    if (!isset($p['solutions'])) {
        $p['solutions'] = [];
        // Migrate legacy fields if they contain data
        $solvedBy = $p['solved_by'] ?? null;
        if ($solvedBy) {
            $p['solutions'][] = [
                'solution_id' => uniqid('sol_'),
                'user' => $solvedBy,
                'chosen_option' => $p['answer'] ?? '',
                'image' => $p['solution_image'] ?? null
            ];
        }
        // Remove legacy fields
        unset($p['solved_by'], $p['answer'], $p['solution_image']);
    }
    // Ensure top-level fields
    if (!isset($p['locked_by'])) $p['locked_by'] = null;
    if (!isset($p['flagged_by'])) $p['flagged_by'] = null;
    if (!isset($p['flag_reason'])) $p['flag_reason'] = null;
    if (!isset($p['hidden_by'])) $p['hidden_by'] = null;
}

// ======== GET: Merge all JSON files ========
if ($action === 'get') {
    $files = glob($dataDir . '*.json');
    if (empty($files)) {
        sendResponse(['error' => 'No data files found in data/ folder'], 500);
    }
    $allProblems = [];
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $problems = json_decode($content, true);
        if (is_array($problems)) {
            foreach ($problems as &$p) {
                migrateProblem($p);
                $p['source_file'] = basename($file);
            }
            $allProblems = array_merge($allProblems, $problems);
        }
    }
    sendResponse($allProblems);
}

// ======== WRITE actions: target a specific source_file ========
if (!$sourceFile) sendResponse(['error' => 'Missing source_file'], 400);

$targetPath = $dataDir . basename($sourceFile);
if (!file_exists($targetPath)) {
    sendResponse(['error' => 'Source file not found: ' . $sourceFile], 404);
}

$content = file_get_contents($targetPath);
$data = json_decode($content, true);
if (!is_array($data)) {
    sendResponse(['error' => 'Invalid JSON in ' . $sourceFile], 500);
}

// Migrate all problems in this file first
foreach ($data as &$mp) { migrateProblem($mp); }
unset($mp);

$changed = false;

if ($action === 'lock') {
    if (!$id || !$user) sendResponse(['error' => 'Missing ID or user'], 400);
    foreach ($data as &$problem) {
        if ($problem['id'] == $id) {
            $lockedBy = $problem['locked_by'] ?? null;
            if ($lockedBy === null) {
                $problem['locked_by'] = $user;
                $changed = true;
                break;
            } else {
                sendResponse(['error' => 'Problem already locked'], 403);
            }
        }
    }
} elseif ($action === 'flag') {
    if (!$id || !$user) sendResponse(['error' => 'Missing ID or user'], 400);
    foreach ($data as &$problem) {
        if ($problem['id'] == $id) {
            $problem['flagged_by'] = $user;
            $problem['flag_reason'] = $reason;
            $changed = true;
            break;
        }
    }
} elseif ($action === 'unflag') {
    if (!$id || !$user) sendResponse(['error' => 'Missing ID or user'], 400);
    foreach ($data as &$problem) {
        if ($problem['id'] == $id) {
            if (($problem['flagged_by'] ?? null) === $user) {
                $problem['flagged_by'] = null;
                $problem['flag_reason'] = null;
                $changed = true;
            }
            break;
        }
    }
} elseif ($action === 'unlock') {
    if (!$id || !$user) sendResponse(['error' => 'Missing ID or user'], 400);
    foreach ($data as &$problem) {
        if ($problem['id'] == $id) {
            if (($problem['locked_by'] ?? null) === $user) {
                $problem['locked_by'] = null;
                $changed = true;
                break;
            }
        }
    }
} elseif ($action === 'force_unlock') {
    // Force unlock: clears locked_by regardless of who locked it
    if (!$id) sendResponse(['error' => 'Missing ID'], 400);
    foreach ($data as &$problem) {
        if ($problem['id'] == $id) {
            if ($problem['locked_by'] ?? null) {
                $problem['locked_by'] = null;
                $changed = true;
            }
            break;
        }
    }
} elseif ($action === 'save') {
    // Append a new solution to the solutions[] array
    if (!$id || !$user || $answer === null) sendResponse(['error' => 'Missing parameters'], 400);
    foreach ($data as &$problem) {
        if ($problem['id'] == $id) {
            if (($problem['locked_by'] ?? null) === $user) {
                $newSolution = [
                    'solution_id' => uniqid('sol_'),
                    'user' => $user,
                    'chosen_option' => $answer,
                    'image' => null
                ];

                if ($options) {
                    $decodedOptions = json_decode($options, true);
                    if ($decodedOptions) $problem['options'] = $decodedOptions;
                }

                // Image Handling
                if ($image) {
                    $parts = explode(',', $image);
                    if (count($parts) > 1) {
                        $imageData = base64_decode($parts[1]);
                        $filename = 'solution_' . $id . '_' . time() . '.jpg';
                        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);
                        file_put_contents($uploadsDir . $filename, $imageData);
                        $newSolution['image'] = $uploadsDir . $filename;
                    }
                }

                $problem['solutions'][] = $newSolution;
                $problem['locked_by'] = null;
                $problem['flagged_by'] = null;
                $problem['flag_reason'] = null;

                $changed = true;
                break;
            } else {
                sendResponse(['error' => 'You do not have the lock'], 403);
            }
        }
    }
} elseif ($action === 'delete_solution') {
    // Delete a specific solution from a problem
    if (!$id || !$solutionId) sendResponse(['error' => 'Missing problem ID or solution_id'], 400);
    foreach ($data as &$problem) {
        if ($problem['id'] == $id) {
            foreach ($problem['solutions'] as $i => $sol) {
                if ($sol['solution_id'] === $solutionId) {
                    // Delete the image file from disk if it exists
                    if (!empty($sol['image']) && file_exists($sol['image'])) {
                        unlink($sol['image']);
                    }
                    // Remove from array
                    array_splice($problem['solutions'], $i, 1);
                    $changed = true;
                    break 2;
                }
            }
            sendResponse(['error' => 'Solution not found'], 404);
        }
    }
} elseif ($action === 'edit_solution') {
    // Edit the chosen_option on an existing solution
    if (!$id || !$solutionId || $answer === null) sendResponse(['error' => 'Missing parameters'], 400);
    foreach ($data as &$problem) {
        if ($problem['id'] == $id) {
            foreach ($problem['solutions'] as &$sol) {
                if ($sol['solution_id'] === $solutionId) {
                    $sol['chosen_option'] = $answer;
                    $changed = true;
                    break 2;
                }
            }
            sendResponse(['error' => 'Solution not found'], 404);
        }
    }
} elseif ($action === 'edit_options') {
    // Edit the options text on a problem (no lock required)
    if (!$id || !$options) sendResponse(['error' => 'Missing parameters'], 400);
    $decodedOptions = json_decode($options, true);
    if (!$decodedOptions) sendResponse(['error' => 'Invalid options JSON'], 400);
    foreach ($data as &$problem) {
        if ($problem['id'] == $id) {
            $problem['options'] = $decodedOptions;
            $changed = true;
            break;
        }
    }
} elseif ($action === 'toggle_hide') {
    // Toggle the hidden_by field
    if (!$id || !$user) sendResponse(['error' => 'Missing ID or user'], 400);
    foreach ($data as &$problem) {
        if ($problem['id'] == $id) {
            if ($problem['hidden_by']) {
                $problem['hidden_by'] = null;
            } else {
                $problem['hidden_by'] = $user;
            }
            $changed = true;
            break;
        }
    }
} else {
    sendResponse(['error' => 'Invalid action'], 400);
}

if ($changed) {
    foreach ($data as &$p) {
        unset($p['source_file']);
    }
    file_put_contents($targetPath, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

sendResponse(['success' => true]);
?>
