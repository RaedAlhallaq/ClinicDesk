<?php
// ============================================================
// core/helpers.php
// Global helper functions used throughout the application.
// Loaded once in index.php to be accessible everywhere.
// ============================================================


// ------------------------------------------------------------
// redirect()
// Redirect the user to a specific page and stop execution.
// ------------------------------------------------------------
function redirect(string $page, string $action = '', int $id = 0): void
{
    $url = BASE_URL . '/index.php?page=' . $page;

    if (!empty($action)) {
        $url .= '&action=' . $action;
    }

    if ($id > 0) {
        $url .= '&id=' . $id;
    }

    header('Location: ' . $url);
    exit();
}


// ------------------------------------------------------------
// redirectTo()
// Redirect directly to a full URL.
// ------------------------------------------------------------
function redirectTo(string $url): void
{
    header('Location: ' . $url);
    exit();
}


// ------------------------------------------------------------
// e()
// A shorthand for htmlspecialchars. This is a critical security function.
// Always use it when displaying user input to prevent XSS (Cross-Site Scripting).
// ------------------------------------------------------------
function e(mixed $value): string
{
    // Convert the value to a string (handles ints and nulls safely)
    $value = (string) $value;

    // Convert dangerous characters to HTML entities:
    // <  ->  &lt;
    // >  ->  &gt;
    // "  ->  &quot;
    // '  ->  &#039;
    // &  ->  &amp;
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}


// ------------------------------------------------------------
// flashMessage()
// Save a temporary message in the session to show on the next page.
// Available types: 'success', 'error', 'warning', 'info'
// ------------------------------------------------------------
function flashMessage(string $message, string $type = 'info'): void
{
    // Ensure the session is active before saving the message
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type
        ];
    }
}


// ------------------------------------------------------------
// getFlashMessage()
// Retrieve the temporary message and delete it immediately.
// Used in views/partials/alerts.php to display alerts.
// ------------------------------------------------------------
function getFlashMessage(): ?array
{
    // If a message exists in the session
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];

        // Delete it so it only appears once
        unset($_SESSION['flash']);

        return $flash;
    }

    // No message found
    return null;
}


// ------------------------------------------------------------
// sanitizeString()
// Clean a raw string from user input before processing.
// Note: This is for general cleaning. SQL is secured via Prepared Statements.
// ------------------------------------------------------------
function sanitizeString(string $value): string
{
    // strip_tags() removes HTML/PHP tags
    // trim() removes extra whitespace from the beginning and end
    return trim(strip_tags($value));
}


// ------------------------------------------------------------
// sanitizeEmail()
// Clean and validate an email address safely.
// ------------------------------------------------------------
function sanitizeEmail(string $email): string
{
    return filter_var(
        trim($email),
        FILTER_SANITIZE_EMAIL
    );
}


// ------------------------------------------------------------
// formatDate()
// Convert a database date string to a human-readable format.
// Input:  '2025-12-25'   (MySQL format)
// Output: 'Dec 25, 2025' (Readable format)
// ------------------------------------------------------------
function formatDate(string $date): string
{
    if (empty($date) || $date === '0000-00-00') {
        return 'N/A';
    }

    // Create a DateTime object
    $dateObj = date_create($date);

    // Return the original value if parsing fails
    if (!$dateObj) {
        return $date;
    }

    // Return the formatted date string
    return date_format($dateObj, 'M d, Y');
}


// ------------------------------------------------------------
// formatTime()
// Convert 24-hour time to 12-hour format with AM/PM.
// Input:  '14:30:00' (MySQL format)
// Output: '02:30 PM' (Readable format)
// ------------------------------------------------------------
function formatTime(string $time): string
{
    if (empty($time) || $time === '00:00:00') {
        return 'N/A';
    }

    $timeObj = date_create($time);

    if (!$timeObj) {
        return $time;
    }

    return date_format($timeObj, 'h:i A');
}


// ------------------------------------------------------------
// isPost()
// Helper to check if the current request is a POST request.
// ------------------------------------------------------------
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}


// ------------------------------------------------------------
// isGet()
// Helper to check if the current request is a GET request.
// ------------------------------------------------------------
function isGet(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}


// ------------------------------------------------------------
// truncate()
// Shorten a long string and append '...' at the end.
// Useful for displaying long text in tables neatly.
// ------------------------------------------------------------
function truncate(string $text, int $length = 50): string
{
    // Return immediately if the text is short enough
    if (mb_strlen($text) <= $length) {
        return $text;
    }

    // mb_substr supports multi-byte characters like Arabic
    return mb_substr($text, 0, $length) . '...';
}


// ------------------------------------------------------------
// generateFilename()
// Generate a unique and secure filename for uploaded files.
// Example Output: 'prescription_42_1716900000.pdf'
// ------------------------------------------------------------
function generateFilename(string $prefix, int $id, string $extension): string
{
    // time() provides the current Unix timestamp
    return $prefix . '_' . $id . '_' . time() . '.' . $extension;
}


// ------------------------------------------------------------
// assetUrl()
// Build the absolute URL for AdminLTE assets (CSS, JS, images).
// ------------------------------------------------------------
function assetUrl(string $path): string
{
    return BASE_URL . '/public/assets/adminlte/' . ltrim($path, '/');
}


// ------------------------------------------------------------
// uploadUrl()
// Build the absolute URL to access an uploaded file.
// ------------------------------------------------------------
function uploadUrl(string $path): string
{
    return BASE_URL . '/public/uploads/' . ltrim($path, '/');
}


// ------------------------------------------------------------
// handleImageUpload()
// Upload an image securely with real MIME-type verification.
//
// Only accepts JPEG and PNG files within the maximum allowed size.
// Returns an array containing any errors and the saved file path.
// ------------------------------------------------------------
function handleImageUpload(
    string $fieldName,
    string $destinationDir,
    string $relativeDir,
    int    $maxSize,
    string $prefix,
    ?string $oldRelativePath = null
): array {
    // If no file is attached, return success with no path
    if (
        empty($_FILES[$fieldName]) ||
        ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
    ) {
        return ['error' => null, 'path' => null];
    }

    $file = $_FILES[$fieldName];

    // Check for standard PHP upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Image upload failed.', 'path' => null];
    }

    // Validate the file size against the limit
    if ($file['size'] > $maxSize) {
        return ['error' => 'Image is too large. Max 1MB allowed.', 'path' => null];
    }

    // ===== Critical Security Check =====
    // Use getimagesize() to read the actual file header.
    // This prevents attackers from uploading PHP files disguised as images.
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['error' => 'Uploaded file is not a valid image.', 'path' => null];
    }

    // Define allowed MIME types securely
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
    ];

    $mime = $imageInfo['mime'] ?? '';
    if (!isset($allowedMimes[$mime])) {
        return ['error' => 'Only JPEG and PNG images are allowed.', 'path' => null];
    }

    // Create the destination directory if it does not exist
    if (!is_dir($destinationDir) && !mkdir($destinationDir, 0755, true)) {
        return ['error' => 'Upload directory is not writable.', 'path' => null];
    }

    // Generate a unique and highly secure filename
    $filename    = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowedMimes[$mime];
    $destination = rtrim($destinationDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    // Move the file from PHP's temporary folder to the final destination
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['error' => 'Failed to save uploaded image.', 'path' => null];
    }

    // Delete the user's old image if one existed
    if ($oldRelativePath && str_starts_with($oldRelativePath, trim($relativeDir, '/') . '/')) {
        $oldPath = UPLOAD_PATH . $oldRelativePath;
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    // Return the relative path so it can be saved in the database
    return ['error' => null, 'path' => trim($relativeDir, '/') . '/' . $filename];
}


// ------------------------------------------------------------
// handlePrescriptionUpload()
// Securely upload a PDF prescription file.
//
// Validates file size and relies on finfo_file for strict MIME checking.
// Returns an array containing any errors and the saved file path.
// ------------------------------------------------------------
function handlePrescriptionUpload(int $appointmentId): array
{
    $file = $_FILES['prescription_file'];

    // Check for standard PHP upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload failed.', 'path' => null];
    }

    // Validate the file size
    if ($file['size'] > MAX_PRESCRIPTION_SIZE) {
        return ['error' => 'File too large. Max 3MB allowed.', 'path' => null];
    }

    // Use finfo to securely validate the real MIME type, ignoring the extension
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if ($mimeType !== 'application/pdf') {
        return ['error' => 'Only PDF files are allowed.', 'path' => null];
    }

    // Generate a unique filename using the appointment ID
    $filename = generateFilename('prescription', $appointmentId, 'pdf');
    $destPath = PRESCRIPTION_PATH . $filename;

    // Move the file to its final destination
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['error' => 'Failed to save file.', 'path' => null];
    }

    return ['error' => null, 'path' => $filename];
}


// ------------------------------------------------------------
// deleteUploadedFile()
// Delete a file from the server securely given its relative path.
// Used primarily for cleanup when a database operation fails after an upload.
// ------------------------------------------------------------
function deleteUploadedFile(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }

    $path = UPLOAD_PATH . ltrim($relativePath, '/');
    
    // Ensure it's a valid file before attempting deletion
    if (is_file($path)) {
        @unlink($path);
    }
}
