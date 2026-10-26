 <?php

session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$error = "";
$success = "";

/* =========================
   UPLOAD DIRECTORY
========================= */

$upload_dir = "../uploads/documents/";

/* Create folder if it does not exist */

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}


/* =========================
   GET CLIENTS
========================= */

$clients = $conn->query("
    SELECT
        client_id,
        client_number,
        full_name
    FROM clients
    ORDER BY full_name ASC
");


/* =========================
   GET CASES
========================= */

$cases = $conn->query("
    SELECT
        case_id,
        case_number,
        case_type,
        client_id
    FROM cases
    ORDER BY case_id DESC
");


/* =========================
   UPLOAD DOCUMENT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $client_id = (int) ($_POST["client_id"] ?? 0);

    $case_id = !empty($_POST["case_id"])
        ? (int) $_POST["case_id"]
        : null;

    $document_name = trim(
        $_POST["document_name"] ?? ""
    );

    $document_type = trim(
        $_POST["document_type"] ?? ""
    );


    /* =========================
       VALIDATION
    ========================= */

    if (
        $client_id <= 0 ||
        empty($document_name) ||
        empty($document_type)
    ) {

        $error = "Please fill in all required fields.";

    } elseif (
        !isset($_FILES["document_file"]) ||
        $_FILES["document_file"]["error"] !== UPLOAD_ERR_OK
    ) {

        $error = "Please select a document to upload.";

    } else {

        $file = $_FILES["document_file"];

        $original_name = $file["name"];
        $tmp_name = $file["tmp_name"];
        $file_size = $file["size"];


        /* =========================
           FILE EXTENSION
        ========================= */

        $extension = strtolower(
            pathinfo(
                $original_name,
                PATHINFO_EXTENSION
            )
        );


        /* =========================
           ALLOWED FILE TYPES
        ========================= */

        $allowed_extensions = [
            "pdf",
            "doc",
            "docx",
            "jpg",
            "jpeg",
            "png"
        ];


        if (!in_array(
            $extension,
            $allowed_extensions
        )) {

            $error =
                "Invalid file type. Allowed files: PDF, DOC, DOCX, JPG, JPEG and PNG.";

        }


        /* =========================
           FILE SIZE
        ========================= */

        elseif ($file_size > 10 * 1024 * 1024) {

            $error =
                "File is too large. Maximum file size is 10MB.";

        }


        /* =========================
           SAVE FILE
        ========================= */

        else {

            /*
             * Generate unique filename
             */

            $new_filename =
                time()
                . "_"
                . uniqid()
                . "."
                . $extension;


            $file_path =
                $upload_dir
                . $new_filename;


            if (move_uploaded_file(
                $tmp_name,
                $file_path
            )) {


                /*
                 * Database path
                 */

                $database_file_path =
                    "uploads/documents/"
                    . $new_filename;


                /* =========================
                   INSERT DOCUMENT
                ========================= */

                $stmt = $conn->prepare("
                    INSERT INTO documents
                    (
                        client_id,
                        case_id,
                        document_name,
                        document_type,
                        file_path,
                        uploaded_by
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ");


                $stmt->bind_param(
                    "iisssi",
                    $client_id,
                    $case_id,
                    $document_name,
                    $document_type,
                    $database_file_path,
                    $_SESSION["user_id"]
                );


                if ($stmt->execute()) {

                    $stmt->close();

                    header(
                        "Location: index.php?success=Document uploaded successfully"
                    );

                    exit;

                } else {

                    /*
                     * Remove uploaded file
                     * if database insertion fails.
                     */

                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }

                    $error =
                        "Failed to save document information: "
                        . $stmt->error;

                    $stmt->close();
                }

            } else {

                $error =
                    "Failed to upload the file.";
            }
        }
    }
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
Upload Document - Social Welfare System
</title>


<!-- FONT AWESOME -->

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
>


<style>

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


/* =========================
   SIDEBAR
========================= */

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

    transition: 0.2s;
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


/* ICON COLORS */

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


/* =========================
   MAIN
========================= */

.main {

    margin-left: 250px;

    padding: 30px;

    min-height: 100vh;
}


/* =========================
   HEADER
========================= */

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


.page-header h1 i {

    color: #ab47bc;

    margin-right: 8px;
}


.page-header p {

    margin:
        7px 0 0;

    color: #777;
}


/* =========================
   BACK BUTTON
========================= */

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
}


.back-btn:hover {

    background: #333;
}


/* =========================
   CARD
========================= */

.card {

    background: white;

    padding: 30px;

    border-radius: 10px;

    box-shadow:
        0 2px 8px rgba(0,0,0,.08);

    max-width: 950px;
}


/* =========================
   ERROR
========================= */

.error {

    background: #f8d7da;

    color: #842029;

    padding: 13px;

    border-radius: 6px;

    margin-bottom: 20px;

    border:
        1px solid #f5c2c7;

    display: flex;

    align-items: center;

    gap: 9px;
}


/* =========================
   FORM
========================= */

.form-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 20px;
}


.form-group {

    display: flex;

    flex-direction: column;
}


.form-group.full {

    grid-column:
        1 / -1;
}


label {

    font-weight: bold;

    margin-bottom: 8px;

    color: #333;
}


.required {

    color: #e53935;
}


/* =========================
   INPUTS
========================= */

input,
select {

    width: 100%;

    padding: 13px;

    border:
        1px solid #ccc;

    border-radius: 6px;

    font-size: 15px;

    background: white;
}


input:focus,
select:focus {

    outline: none;

    border-color: #8e24aa;

    box-shadow:
        0 0 0 2px rgba(142,36,170,.1);
}


/* =========================
   FILE UPLOAD
========================= */

.file-box {

    border:
        2px dashed #ccc;

    padding: 25px;

    border-radius: 8px;

    text-align: center;

    background: #fafafa;

    transition: 0.2s;
}


.file-box:hover {

    border-color: #ab47bc;

    background: #fcf7fd;
}


.file-box i {

    font-size: 40px;

    color: #ab47bc;

    margin-bottom: 12px;
}


.file-box input {

    border: none;

    padding: 10px;

    background: transparent;
}


.file-help {

    margin-top: 8px;

    font-size: 12px;

    color: #777;
}


/* =========================
   BUTTONS
========================= */

.buttons {

    margin-top: 25px;

    display: flex;

    gap: 12px;
}


.upload-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    background: #198754;

    color: white;

    border: none;

    padding:
        13px 24px;

    border-radius: 6px;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;
}


.upload-btn:hover {

    background: #146c43;
}


.cancel-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    background: #555;

    color: white;

    text-decoration: none;

    padding:
        13px 24px;

    border-radius: 6px;

    font-weight: bold;
}


.cancel-btn:hover {

    background: #333;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width: 800px) {

    .sidebar {

        width: 210px;
    }


    .main {

        margin-left: 210px;

        padding: 20px;
    }


    .form-grid {

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


    .card {

        padding: 20px;
    }


    .buttons {

        flex-direction: column;
    }

}

</style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

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


<!-- =========================
     MAIN
========================= -->

<div class="main">


<div class="page-header">


<div>

<h1>

<i class="fas fa-upload"></i>

Upload Document

</h1>


<p>

Add and store a social welfare document

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


<!-- CARD -->

<div class="card">


<?php if (!empty($error)): ?>

<div class="error">

<i class="fas fa-exclamation-circle"></i>

<?php

echo htmlspecialchars($error);

?>

</div>

<?php endif; ?>


<form
method="POST"
enctype="multipart/form-data"
>


<div class="form-grid">


<!-- CLIENT -->

<div class="form-group full">

<label>

Client

<span class="required">*</span>

</label>


<select
name="client_id"
required
>

<option value="">

Select Client

</option>


<?php if ($clients && $clients->num_rows > 0): ?>


<?php while (
    $client = $clients->fetch_assoc()
): ?>


<option
value="<?php
echo $client["client_id"];
?>"
>

<?php

echo htmlspecialchars(
    $client["client_number"]
    . " - "
    . $client["full_name"]
);

?>

</option>


<?php endwhile; ?>


<?php else: ?>


<option value="" disabled>

No clients available

</option>


<?php endif; ?>


</select>

</div>


<!-- CASE -->

<div class="form-group full">

<label>

Case

<span
style="
font-weight:normal;
color:#777;
"
>
(Optional)
</span>

</label>


<select
name="case_id"
>

<option value="">

No Case

</option>


<?php if ($cases && $cases->num_rows > 0): ?>


<?php while (
    $case_row = $cases->fetch_assoc()
): ?>


<option
value="<?php
echo $case_row["case_id"];
?>"
>

<?php

echo htmlspecialchars(
    $case_row["case_number"]
    . " - "
    . $case_row["case_type"]
);

?>

</option>


<?php endwhile; ?>


<?php else: ?>


<option value="" disabled>

No cases available

</option>


<?php endif; ?>


</select>

</div>


<!-- DOCUMENT NAME -->

<div class="form-group">

<label>

Document Name

<span class="required">*</span>

</label>


<input
type="text"
name="document_name"
placeholder="e.g. Medical Report"
value="<?php
echo htmlspecialchars(
    $_POST["document_name"] ?? ""
);
?>"
required
>

</div>


<!-- DOCUMENT TYPE -->

<div class="form-group">

<label>

Document Type

<span class="required">*</span>

</label>


<select
name="document_type"
required
>

<option value="">

Select Document Type

</option>


<?php

$document_types = [

    "Case File",
    "Medical Report",
    "Birth Certificate",
    "Identity Document",
    "Court Document",
    "Assessment Report",
    "Referral Letter",
    "Social Investigation Report",
    "Other"

];


foreach (
    $document_types
    as $type
):

?>


<option
value="<?php
echo htmlspecialchars($type);
?>"

<?php

if (
    ($_POST["document_type"] ?? "")
    === $type
) {

    echo "selected";
}

?>

>

<?php

echo htmlspecialchars($type);

?>

</option>


<?php endforeach; ?>


</select>

</div>


<!-- FILE -->

<div class="form-group full">

<label>

Document File

<span class="required">*</span>

</label>


<div class="file-box">


<i class="fas fa-cloud-upload-alt"></i>


<br>


<strong>

Choose a document to upload

</strong>


<input
type="file"
name="document_file"
accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
required
>


<div class="file-help">

Allowed: PDF, DOC, DOCX, JPG, JPEG, PNG

<br>

Maximum file size: 10MB

</div>


</div>

</div>


</div>


<!-- BUTTONS -->

<div class="buttons">


<button
type="submit"
class="upload-btn"
>

<i class="fas fa-upload"></i>

Upload Document

</button>


<a
href="index.php"
class="cancel-btn"
>

<i class="fas fa-times"></i>

Cancel

</a>


</div>


</form>


</div>


</div>


</body>

</html>