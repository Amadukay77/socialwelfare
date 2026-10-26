
<?php
session_start();

require_once "../includes/db.php";
require_once "../includes/permissions.php";

// Only logged-in administrators can upload images.
require_login();
require_permission("settings_manage");

// Allow uploads only through POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

// Verify CSRF token.
if (
    empty($_SESSION["csrf_token"]) ||
    empty($_POST["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    exit("Security check failed. Please return to Settings and try again.");
}

// Check that an image was selected.
if (
    !isset($_FILES["background_image"]) ||
    $_FILES["background_image"]["error"] !== UPLOAD_ERR_OK
) {
    exit("Please select an image and try again.");
}

$file = $_FILES["background_image"];

// Maximum upload size: 5 MB.
if ($file["size"] > 5 * 1024 * 1024) {
    exit("Image is too large. Maximum size is 5 MB.");
}

// Check the real image type.
$imageInfo = getimagesize($file["tmp_name"]);

if ($imageInfo === false) {
    exit("The uploaded file is not a valid image.");
}

$allowedTypes = [
    IMAGETYPE_JPEG => "jpg",
    IMAGETYPE_PNG  => "png",
    IMAGETYPE_WEBP => "webp"
];

$imageType = $imageInfo[2];

if (!isset($allowedTypes[$imageType])) {
    exit("Only JPG, PNG, and WebP images are allowed.");
}

$extension = $allowedTypes[$imageType];

// Save inside the existing protected upload directory.
$uploadDirectory = dirname(__DIR__) . "/uploads/system/";

if (!is_dir($uploadDirectory)) {
    if (!mkdir($uploadDirectory, 0755, true)) {
        exit("Could not create the upload directory.");
    }
}

if (!is_writable($uploadDirectory)) {
    exit("The uploads/system folder is not writable.");
}

// Generate a random filename.
$filename = bin2hex(random_bytes(16)) . "." . $extension;
$destination = $uploadDirectory . $filename;

if (!move_uploaded_file($file["tmp_name"], $destination)) {
    exit("Image upload failed. Please try again.");
}

// Store a web-relative path in the database.
$relativePath = "uploads/system/" . $filename;

$stmt = $conn->prepare(
    "UPDATE system_settings
     SET login_background = ?
     WHERE setting_id = 1"
);

if (!$stmt) {
    @unlink($destination);
    exit("Could not prepare the database update.");
}

$stmt->bind_param("s", $relativePath);

if (!$stmt->execute() || $stmt->affected_rows < 0) {
    @unlink($destination);
    exit("Could not save the image path in the database.");
}

$stmt->close();

header("Location: index.php?upload=success");
exit;
