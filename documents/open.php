<?php

session_start();

require_once "../includes/db.php";

/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}


/* =========================================================
   CHECK DOCUMENT ID
========================================================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid document ID.");
}

$document_id = (int) $_GET["id"];


/* =========================================================
   GET DOCUMENT
========================================================= */

$stmt = $conn->prepare("
    SELECT
        document_id,
        document_name,
        file_path
    FROM documents
    WHERE document_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $document_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    die("Document not found.");
}

$document = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   GET FILE PATH
========================================================= */

$db_file_path = trim($document["file_path"] ?? "");

$normalized_path = str_replace("\\", "/", $db_file_path);

$normalized_path = ltrim($normalized_path, "/");

$actual_file_name = basename($normalized_path);


/* =========================================================
   FIND FILE ON SERVER
========================================================= */

$possible_paths = [];


/* documents/uploads/filename */

$possible_paths[] =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    "uploads" .
    DIRECTORY_SEPARATOR .
    $actual_file_name;


/* social_welfare/documents/uploads/filename */

$possible_paths[] =
    dirname(__DIR__) .
    DIRECTORY_SEPARATOR .
    "documents" .
    DIRECTORY_SEPARATOR .
    "uploads" .
    DIRECTORY_SEPARATOR .
    $actual_file_name;


/* Database path */

$possible_paths[] = $db_file_path;


/* Try to find file */

$server_file_path = "";

foreach ($possible_paths as $possible_path) {

    if (
        !empty($possible_path) &&
        file_exists($possible_path) &&
        is_file($possible_path)
    ) {

        $server_file_path = $possible_path;

        break;
    }
}


/* =========================================================
   FILE NOT FOUND
========================================================= */

if (empty($server_file_path)) {

    http_response_code(404);

    echo "<h2>Document file not found</h2>";

    echo "<p>The uploaded file could not be found on the server.</p>";

    exit;
}


/* =========================================================
   FILE TYPE
========================================================= */

$extension = strtolower(
    pathinfo(
        $server_file_path,
        PATHINFO_EXTENSION
    )
);


/* =========================================================
   MIME TYPES
========================================================= */

$mime_types = [

    "pdf" =>
        "application/pdf",

    "jpg" =>
        "image/jpeg",

    "jpeg" =>
        "image/jpeg",

    "png" =>
        "image/png",

    "gif" =>
        "image/gif",

    "webp" =>
        "image/webp",

    "txt" =>
        "text/plain",

    "csv" =>
        "text/csv",

    "doc" =>
        "application/msword",

    "docx" =>
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",

    "xls" =>
        "application/vnd.ms-excel",

    "xlsx" =>
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",

    "ppt" =>
        "application/vnd.ms-powerpoint",

    "pptx" =>
        "application/vnd.openxmlformats-officedocument.presentationml.presentation",

    "zip" =>
        "application/zip"

];


$mime_type =
    $mime_types[$extension]
    ?? "application/octet-stream";


/* =========================================================
   FILE NAME
========================================================= */

$download_name =
    basename($document["document_name"]);


/*
 * If document name has no extension,
 * use the actual uploaded filename.
 */

if (
    strpos(
        $download_name,
        "."
    ) === false
) {

    $download_name =
        $actual_file_name;
}


/* =========================================================
   SEND FILE
========================================================= */

header(
    "Content-Type: " . $mime_type
);

header(
    "Content-Length: " .
    filesize($server_file_path)
);


/*
 * Browser-supported files such as PDF/images
 * can be displayed directly.
 *
 * Office documents are sent as attachment because
 * normal browsers cannot directly display DOCX files.
 */

if (
    in_array(
        $extension,
        [
            "pdf",
            "jpg",
            "jpeg",
            "png",
            "gif",
            "webp",
            "txt"
        ]
    )
) {

    header(
        'Content-Disposition: inline; filename="' .
        basename($download_name) .
        '"'
    );

} else {

    header(
        'Content-Disposition: attachment; filename="' .
        basename($download_name) .
        '"'
    );
}


header("Cache-Control: private");

readfile($server_file_path);

exit;

?>