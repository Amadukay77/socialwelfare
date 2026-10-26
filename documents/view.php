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

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {

    header("Location: index.php");

    exit;
}


$document_id = (int) $_GET["id"];


/* =========================================================
   GET DOCUMENT
========================================================= */

$stmt = $conn->prepare("
    SELECT

        d.*,

        c.client_number,
        c.full_name AS client_name,
        c.gender AS client_gender,

        ca.case_number,
        ca.case_type,
        ca.status AS case_status,

        u.username

    FROM documents d

    INNER JOIN clients c
        ON d.client_id = c.client_id

    LEFT JOIN cases ca
        ON d.case_id = ca.case_id

    LEFT JOIN users u
        ON d.uploaded_by = u.user_id

    WHERE d.document_id = ?

    LIMIT 1
");


$stmt->bind_param(
    "i",
    $document_id
);


$stmt->execute();


$result = $stmt->get_result();


if ($result->num_rows === 0) {

    $stmt->close();

    header("Location: index.php");

    exit;
}


$document = $result->fetch_assoc();


$stmt->close();



/* =========================================================
   FILE INFORMATION
========================================================= */

/*
    The database may contain values such as:

    uploads/file.pdf

    documents/uploads/file.pdf

    \uploads\file.pdf

    C:\Program Files\xamp2\htdocs\
    social_welfare\documents\uploads\file.pdf

    We handle all of these.
*/


$db_file_path = trim(
    $document["file_path"] ?? ""
);


/* Convert Windows backslashes to slashes */

$normalized_path = str_replace(
    "\\",
    "/",
    $db_file_path
);


/* Remove unnecessary leading slash */

$normalized_path = ltrim(
    $normalized_path,
    "/"
);


/* =========================================================
   GET ACTUAL FILE NAME
========================================================= */

$actual_file_name = basename(
    $normalized_path
);


/* =========================================================
   SERVER FILE PATH
========================================================= */

$file_exists = false;

$server_file_path = "";


/*
    Possible locations of uploaded files.
*/

$possible_paths = [];


/* 1. Direct path from documents folder */

$possible_paths[] =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    $normalized_path;


/* 2. Path from social_welfare folder */

$possible_paths[] =
    dirname(__DIR__) .
    DIRECTORY_SEPARATOR .
    $normalized_path;


/* 3. Standard uploads folder */

$possible_paths[] =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    "uploads" .
    DIRECTORY_SEPARATOR .
    $actual_file_name;


/* 4. social_welfare/documents/uploads */

$possible_paths[] =
    dirname(__DIR__) .
    DIRECTORY_SEPARATOR .
    "documents" .
    DIRECTORY_SEPARATOR .
    "uploads" .
    DIRECTORY_SEPARATOR .
    $actual_file_name;


/* 5. Absolute path stored in database */

$possible_paths[] = $db_file_path;


/* =========================================================
   FIND THE FILE
========================================================= */

foreach ($possible_paths as $possible_path) {

    if (
        !empty($possible_path) &&
        file_exists($possible_path) &&
        is_file($possible_path)
    ) {

        $file_exists = true;

        $server_file_path = $possible_path;

        break;
    }
}


/* =========================================================
   BROWSER FILE URL
========================================================= */

/*
    Since view.php is inside:

    social_welfare/documents/

    the uploads folder is:

    documents/uploads/

    Therefore the browser URL is:

    uploads/filename.pdf
*/


$file_url = "";


if (!empty($actual_file_name)) {

    $file_url =
        "uploads/" .
        rawurlencode($actual_file_name);
}


/* =========================================================
   FILE EXTENSION
========================================================= */

/*
    IMPORTANT:

    We get the extension from the actual uploaded
    file name, NOT from document_name.

    Example:

    document_name = ggg

    uploaded file = document_12345.pdf

    Result = PDF
*/


$file_extension = strtolower(
    pathinfo(
        $actual_file_name,
        PATHINFO_EXTENSION
    )
);


/* =========================================================
   FILE MIME TYPE
========================================================= */

$file_type = "Unknown";


if ($file_exists) {

    $mime_type = "";


    if (function_exists("mime_content_type")) {

        $mime_type = mime_content_type(
            $server_file_path
        );
    }


    if (!empty($mime_type)) {

        $file_type = $mime_type;

    } elseif (!empty($file_extension)) {

        $file_type =
            strtoupper($file_extension);
    }
}


/* =========================================================
   FILE ICON
========================================================= */

$file_icon = "fa-file";


if (
    in_array(
        $file_extension,
        ["pdf"]
    )
) {

    $file_icon = "fa-file-pdf";


} elseif (
    in_array(
        $file_extension,
        ["doc", "docx"]
    )
) {

    $file_icon = "fa-file-word";


} elseif (
    in_array(
        $file_extension,
        ["xls", "xlsx", "csv"]
    )
) {

    $file_icon = "fa-file-excel";


} elseif (
    in_array(
        $file_extension,
        [
            "jpg",
            "jpeg",
            "png",
            "gif",
            "webp"
        ]
    )
) {

    $file_icon = "fa-file-image";


} elseif (
    in_array(
        $file_extension,
        [
            "ppt",
            "pptx"
        ]
    )
) {

    $file_icon = "fa-file-powerpoint";


} elseif (
    in_array(
        $file_extension,
        [
            "zip",
            "rar",
            "7z"
        ]
    )
) {

    $file_icon = "fa-file-archive";


} elseif (
    in_array(
        $file_extension,
        [
            "txt"
        ]
    )
) {

    $file_icon = "fa-file-alt";
}


/* =========================================================
   DISPLAY FILE TYPE
========================================================= */

$display_file_type =
    !empty($file_extension)
        ? strtoupper($file_extension)
        : "Unknown";


/* =========================================================
   FILE SIZE
========================================================= */

$file_size = "";


if ($file_exists) {

    $size = filesize(
        $server_file_path
    );


    if ($size < 1024) {

        $file_size =
            $size . " B";

    } elseif ($size < 1048576) {

        $file_size =
            round($size / 1024, 2) .
            " KB";

    } else {

        $file_size =
            round($size / 1048576, 2) .
            " MB";
    }
}


/* =========================================================
   ESCAPE HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>
View Document - Social Welfare System
</title>


<!-- FONT AWESOME -->

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
>


<style>

/* =========================================================
   GENERAL
========================================================= */

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f4f5f7;

    color: #222;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    left: 0;
    top: 0;

    width: 250px;

    height: 100vh;

    background: #171717;

    color: white;

    padding-top: 20px;

    overflow-y: auto;
}


.sidebar h2 {

    text-align: center;

    margin:
        0 0 30px;

    font-size: 20px;
}


.sidebar h2 i {

    margin-right: 7px;
}


.sidebar a {

    display: flex;

    align-items: center;

    gap: 14px;

    color: #ddd;

    text-decoration: none;

    padding:
        14px 20px;

    font-size: 14px;

    transition: .2s;
}


.sidebar a:hover {

    background: #242424;

    color: white;
}


.sidebar a.active {

    background: #242424;

    border-left:
        4px solid #e53935;

    color: white;
}


.sidebar a i {

    width: 22px;

    text-align: center;

    font-size: 16px;
}


/* =========================================================
   SIDEBAR ICON COLORS
========================================================= */

.icon-dashboard {
    color: #42a5f5;
}

.icon-clients {
    color: #66bb6a;
}

.icon-cases {
    color: #ffa726;
}

.icon-documents {
    color: #ab47bc;
}

.icon-households {
    color: #ec407a;
}

.icon-activities {
    color: #ffee58;
}

.icon-appointments {
    color: #29b6f6;
}

.icon-reports {
    color: #26a69a;
}

.icon-settings {
    color: #bdbdbd;
}

.icon-logout {
    color: #ef5350;
}


/* =========================================================
   MAIN
========================================================= */

.main {

    margin-left: 250px;

    padding: 30px;

    min-height: 100vh;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    margin-bottom: 25px;
}


.page-header h1 {

    margin: 0;

    font-size: 26px;
}


.page-header p {

    margin:
        7px 0 0;

    color: #777;
}


.page-header h1 i {

    color: #ab47bc;

    margin-right: 8px;
}


/* =========================================================
   BACK BUTTON
========================================================= */

.back-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    background: #171717;

    color: white;

    text-decoration: none;

    padding:
        11px 18px;

    border-radius: 6px;

    font-weight: bold;
}


.back-btn:hover {

    background: #333;
}


/* =========================================================
   CARD
========================================================= */

.card {

    background: white;

    border-radius: 10px;

    padding: 30px;

    box-shadow:
        0 2px 8px rgba(0,0,0,.08);

    margin-bottom: 20px;
}


.card h2 {

    margin-top: 0;

    margin-bottom: 20px;
}


.card h2 i {

    margin-right: 8px;

    color: #8e24aa;
}


/* =========================================================
   DOCUMENT HEADER
========================================================= */

.document-header {

    display: flex;

    align-items: center;

    gap: 20px;

    padding-bottom: 25px;

    border-bottom:
        1px solid #eee;

    margin-bottom: 25px;
}


.document-icon {

    width: 75px;

    height: 75px;

    border-radius: 10px;

    background: #f3e5f5;

    color: #8e24aa;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 34px;

    flex-shrink: 0;
}


.document-header h2 {

    margin:
        0 0 8px;

    font-size: 23px;

    word-break: break-word;
}


.document-type {

    display: inline-block;

    padding:
        6px 12px;

    border-radius: 20px;

    background: #e8def8;

    color: #6a1b9a;

    font-size: 12px;

    font-weight: bold;
}


/* =========================================================
   INFORMATION GRID
========================================================= */

.info-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 18px;
}


.info-box {

    border:
        1px solid #eee;

    background: #fafafa;

    border-radius: 8px;

    padding: 17px;
}


.info-label {

    color: #777;

    font-size: 13px;

    margin-bottom: 7px;
}


.info-value {

    font-size: 16px;

    font-weight: bold;

    word-break: break-word;
}


/* =========================================================
   CLIENT BOX
========================================================= */

.client-box {

    display: flex;

    align-items: center;

    gap: 15px;

    padding: 18px;

    background: #f8f9fa;

    border:
        1px solid #eee;

    border-radius: 8px;
}


.client-icon {

    width: 50px;

    height: 50px;

    border-radius: 50%;

    background: #e8f5e9;

    color: #43a047;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    flex-shrink: 0;
}


.client-name {

    font-weight: bold;

    font-size: 17px;
}


.client-number {

    color: #777;

    font-size: 13px;

    margin-top: 4px;
}


/* =========================================================
   CASE BOX
========================================================= */

.case-box {

    display: flex;

    align-items: center;

    gap: 15px;

    padding: 18px;

    background: #fff8e1;

    border:
        1px solid #ffe082;

    border-radius: 8px;
}


.case-icon {

    width: 50px;

    height: 50px;

    border-radius: 50%;

    background: #fff3cd;

    color: #f57c00;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    flex-shrink: 0;
}


.case-number {

    font-weight: bold;

    font-size: 16px;
}


.case-type {

    color: #777;

    font-size: 13px;

    margin-top: 4px;
}


/* =========================================================
   FILE SECTION
========================================================= */

.file-section {

    text-align: center;

    padding: 30px;

    border:
        2px dashed #ddd;

    border-radius: 10px;

    background: #fafafa;
}


.file-section > i {

    font-size: 55px;

    color: #8e24aa;

    margin-bottom: 15px;
}


.file-section h3 {

    margin:
        0 0 8px;

    word-break: break-word;
}


.file-section p {

    color: #777;

    margin:
        0 0 10px;
}


.file-info {

    color: #777;

    font-size: 13px;

    margin-bottom: 20px;
}


/* =========================================================
   FILE SUCCESS
========================================================= */

.file-found {

    background: #e8f5e9;

    border:
        1px solid #c8e6c9;

    color: #2e7d32;

    padding: 12px;

    border-radius: 7px;

    margin-bottom: 20px;
}


/* =========================================================
   FILE WARNING
========================================================= */

.file-warning {

    background: #fff3cd;

    color: #664d03;

    border:
        1px solid #ffecb5;

    padding: 15px;

    border-radius: 7px;

    margin-top: 15px;

    line-height: 1.6;
}


/* =========================================================
   ACTIONS
========================================================= */

.actions {

    display: flex;

    gap: 10px;

    flex-wrap: wrap;

    margin-top: 20px;
}


.action-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    color: white;

    text-decoration: none;

    padding:
        12px 18px;

    border-radius: 6px;

    font-weight: bold;

    transition: .2s;
}


.action-btn:hover {

    opacity: .88;

    transform: translateY(-1px);
}


.open-btn {

    background: #1976d2;
}


.download-btn {

    background: #198754;
}


.edit-btn {

    background: #f9a825;
}


.delete-btn {

    background: #e53935;
}


.back-action {

    background: #555;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width: 800px) {

    .sidebar {

        width: 210px;
    }


    .main {

        margin-left: 210px;

        padding: 20px;
    }


    .info-grid {

        grid-template-columns: 1fr;
    }

}


@media(max-width: 600px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
    }


    .main {

        margin-left: 0;

        padding: 15px;
    }


    .page-header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }


    .document-header {

        flex-direction: column;

        align-items: flex-start;
    }


    .card {

        padding: 20px;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">


<h2>

<i class="fas fa-hands-helping"></i>

Social Welfare

</h2>


<a href="../index.php">

<i class="fas fa-tachometer-alt icon-dashboard"></i>

Dashboard

</a>


<a href="../clients/index.php">

<i class="fas fa-users icon-clients"></i>

Clients

</a>


<a href="../cases/index.php">

<i class="fas fa-folder-open icon-cases"></i>

Cases

</a>


<a href="index.php" class="active">

<i class="fas fa-file-alt icon-documents"></i>

Documents

</a>


<a href="../households/index.php">

<i class="fas fa-home icon-households"></i>

Households

</a>


<a href="../activities/index.php">

<i class="fas fa-tasks icon-activities"></i>

Activities

</a>


<a href="../appointments/index.php">

<i class="fas fa-calendar-alt icon-appointments"></i>

Appointments

</a>


<a href="../reports/index.php">

<i class="fas fa-chart-bar icon-reports"></i>

Reports

</a>


<a href="../settings/index.php">

<i class="fas fa-cog icon-settings"></i>

Settings

</a>


<a href="../auth/logout.php">

<i class="fas fa-sign-out-alt icon-logout"></i>

Logout

</a>


</div>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div class="page-header">


<div>

<h1>

<i class="fas fa-file-alt"></i>

Document Details

</h1>


<p>

View complete information about this document

</p>

</div>


<a
href="index.php"
class="back-btn"
>

<i class="fas fa-arrow-left"></i>

Back to Documents

</a>


</div>



<!-- =====================================================
     DOCUMENT INFORMATION
===================================================== -->

<div class="card">


<div class="document-header">


<div class="document-icon">

<i class="fas <?php echo e($file_icon); ?>"></i>

</div>


<div>

<h2>

<?php

echo e(
    $document["document_name"]
);

?>

</h2>


<span class="document-type">

<?php

echo e(
    $document["document_type"]
    ?: "Other"
);

?>

</span>

</div>

</div>



<div class="info-grid">


<div class="info-box">

<div class="info-label">

Document ID

</div>


<div class="info-value">

#<?php echo $document_id; ?>

</div>

</div>



<div class="info-box">

<div class="info-label">

Document Type

</div>


<div class="info-value">

<?php

echo e(
    $document["document_type"]
    ?: "Other"
);

?>

</div>

</div>



<div class="info-box">

<div class="info-label">

Uploaded By

</div>


<div class="info-value">

<?php

echo !empty(
    $document["username"]
)

    ? e(
        $document["username"]
      )

    : "Unknown";

?>

</div>

</div>



<div class="info-box">

<div class="info-label">

Upload Date

</div>


<div class="info-value">

<?php

echo date(
    "d M Y, h:i A",
    strtotime(
        $document["uploaded_at"]
    )
);

?>

</div>

</div>


</div>

</div>



<!-- =====================================================
     CLIENT INFORMATION
===================================================== -->

<div class="card">


<h2>

<i class="fas fa-user"></i>

Client Information

</h2>


<div class="client-box">


<div class="client-icon">

<i class="fas fa-user"></i>

</div>


<div>


<div class="client-name">

<?php

echo e(
    $document["client_name"]
);

?>

</div>


<div class="client-number">

Client No:

<?php

echo e(
    $document["client_number"]
);

?>

&nbsp; | &nbsp;

Gender:

<?php

echo e(
    $document["client_gender"]
    ?? "Not specified"
);

?>

</div>


</div>


</div>

</div>



<!-- =====================================================
     CASE INFORMATION
===================================================== -->

<div class="card">


<h2>

<i class="fas fa-folder-open"></i>

Case Information

</h2>


<?php if (!empty($document["case_id"])): ?>


<div class="case-box">


<div class="case-icon">

<i class="fas fa-folder-open"></i>

</div>


<div>


<div class="case-number">

<?php

echo e(
    $document["case_number"]
);

?>

</div>


<div class="case-type">

<?php

echo e(
    $document["case_type"]
);

?>

&nbsp; | &nbsp;

Status:

<?php

echo e(
    $document["case_status"]
);

?>

</div>


</div>


</div>


<?php else: ?>


<div class="file-warning">

<i class="fas fa-info-circle"></i>

This document is not currently linked to a case.

</div>


<?php endif; ?>


</div>



<!-- =====================================================
     DOCUMENT FILE
===================================================== -->

<div class="card">


<h2>

<i class="fas fa-paperclip"></i>

Document File

</h2>


<div class="file-section">


<i class="fas <?php echo e($file_icon); ?>"></i>


<h3>

<?php

echo e(
    $document["document_name"]
);

?>

</h3>


<p>

File type:

<strong>

<?php

echo e(
    $display_file_type
);

?>

</strong>

</p>


<?php if ($file_exists): ?>


<div class="file-found">

<i class="fas fa-check-circle"></i>

Document file is available on the server.

</div>


<?php if (!empty($file_size)): ?>

<div class="file-info">

<i class="fas fa-file"></i>

File size:

<strong>

<?php echo e($file_size); ?>

</strong>

</div>

<?php endif; ?>


<div class="actions">


<a
href="open.php?id=<?php echo $document_id; ?>"
target="_blank"
class="action-btn open-btn"
>

<i class="fas fa-external-link-alt"></i>

Open File

</a>


<a
href="<?php echo e($file_url); ?>"
download
class="action-btn download-btn"
>

<i class="fas fa-download"></i>

Download

</a>


</div>


<?php else: ?>


<div class="file-warning">


<i class="fas fa-exclamation-triangle"></i>


<strong>
The document file could not be found on the server.
</strong>


<br>


The system expected the uploaded file to be inside:

<br>


<strong>

documents/uploads/

</strong>


<br><br>


Database file path:

<br>


<code>

<?php

echo e(
    $document["file_path"]
);

?>

</code>


</div>


<?php endif; ?>


</div>

</div>



<!-- =====================================================
     DOCUMENT ACTIONS
===================================================== -->

<div class="card">


<h2>

<i class="fas fa-cogs"></i>

Document Actions

</h2>


<div class="actions">


<a
href="edit.php?id=<?php echo $document_id; ?>"
class="action-btn edit-btn"
>

<i class="fas fa-edit"></i>

Edit Document

</a>


<a
href="delete.php?id=<?php echo $document_id; ?>"
class="action-btn delete-btn"
onclick="return confirm('Are you sure you want to delete this document? This action cannot be undone.');"
>

<i class="fas fa-trash"></i>

Delete Document

</a>


<a
href="index.php"
class="action-btn back-action"
>

<i class="fas fa-arrow-left"></i>

Back to Documents

</a>


</div>

</div>


</div>


</body>

</html>