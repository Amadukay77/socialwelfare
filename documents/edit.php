<?php

session_start();

require_once "../includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}


/* =========================
   CHECK DOCUMENT ID
========================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$document_id = (int) $_GET["id"];

$error = "";
$success = "";


/* =========================
   GET DOCUMENT
========================= */

$stmt = $conn->prepare("
    SELECT *
    FROM documents
    WHERE document_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $document_id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();

    header("Location: index.php");
    exit;
}

$document = $result->fetch_assoc();

$stmt->close();


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
        client_id,
        case_number,
        case_type,
        status
    FROM cases
    ORDER BY case_id DESC
");


/* =========================
   UPDATE DOCUMENT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $client_id = (int) ($_POST["client_id"] ?? 0);

    $case_id = !empty($_POST["case_id"])
        ? (int) $_POST["case_id"]
        : null;

    $document_name =
        trim($_POST["document_name"] ?? "");

    $document_type =
        trim($_POST["document_type"] ?? "");


    /* =========================
       VALIDATION
    ========================= */

    if (
        $client_id <= 0 ||
        empty($document_name)
    ) {

        $error =
            "Please fill in all required fields.";

    } else {


        /* =========================
           FILE VARIABLES
        ========================= */

        $new_file_uploaded = false;

        $new_file_path = "";

        $upload_directory =
            __DIR__ . "/uploads/";


        /*
         * Create uploads folder
         * if it does not exist.
         */

        if (!is_dir($upload_directory)) {

            mkdir(
                $upload_directory,
                0777,
                true
            );
        }


        /* =========================
           HANDLE NEW FILE
        ========================= */

        if (
            isset($_FILES["document_file"]) &&
            $_FILES["document_file"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES["document_file"]["error"]
                !== UPLOAD_ERR_OK
            ) {

                $error =
                    "There was an error uploading the new file.";

            } else {

                $original_name =
                    $_FILES["document_file"]["name"];

                $tmp_name =
                    $_FILES["document_file"]["tmp_name"];

                $file_size =
                    $_FILES["document_file"]["size"];


                /*
                 * Maximum file size:
                 * 10 MB
                 */

                if ($file_size > 10 * 1024 * 1024) {

                    $error =
                        "File is too large. Maximum size is 10 MB.";

                } else {

                    $extension =
                        strtolower(
                            pathinfo(
                                $original_name,
                                PATHINFO_EXTENSION
                            )
                        );


                    /*
                     * Allowed file types
                     */

                    $allowed_extensions = [
                        "pdf",
                        "doc",
                        "docx",
                        "xls",
                        "xlsx",
                        "jpg",
                        "jpeg",
                        "png",
                        "gif",
                        "webp"
                    ];


                    if (
                        !in_array(
                            $extension,
                            $allowed_extensions
                        )
                    ) {

                        $error =
                            "This file type is not allowed.";

                    } else {

                        /*
                         * Generate unique filename
                         */

                        $new_filename =
                            time() .
                            "_" .
                            bin2hex(
                                random_bytes(8)
                            ) .
                            "." .
                            $extension;


                        $destination =
                            $upload_directory .
                            $new_filename;


                        if (
                            move_uploaded_file(
                                $tmp_name,
                                $destination
                            )
                        ) {

                            $new_file_uploaded = true;

                            $new_file_path =
                                "uploads/" .
                                $new_filename;

                        } else {

                            $error =
                                "Failed to save the uploaded file.";
                        }
                    }
                }
            }
        }


        /* =========================
           UPDATE DATABASE
        ========================= */

        if (empty($error)) {

            if ($new_file_uploaded) {

                /*
                 * Delete old file
                 */

                $old_file =
                    $document["file_path"];

                if (!empty($old_file)) {

                    $old_physical_path =
                        __DIR__ .
                        "/" .
                        ltrim(
                            str_replace(
                                "\\",
                                "/",
                                $old_file
                            ),
                            "/"
                        );

                    if (
                        file_exists(
                            $old_physical_path
                        )
                    ) {

                        unlink(
                            $old_physical_path
                        );
                    }
                }


                /* =========================
                   UPDATE WITH NEW FILE
                ========================= */

                $stmt = $conn->prepare("
                    UPDATE documents
                    SET
                        client_id = ?,
                        case_id = ?,
                        document_name = ?,
                        document_type = ?,
                        file_path = ?
                    WHERE document_id = ?
                ");

                $stmt->bind_param(
                    "iisssi",
                    $client_id,
                    $case_id,
                    $document_name,
                    $document_type,
                    $new_file_path,
                    $document_id
                );

            } else {

                /* =========================
                   UPDATE WITHOUT NEW FILE
                ========================= */

                $stmt = $conn->prepare("
                    UPDATE documents
                    SET
                        client_id = ?,
                        case_id = ?,
                        document_name = ?,
                        document_type = ?
                    WHERE document_id = ?
                ");

                $stmt->bind_param(
                    "iissi",
                    $client_id,
                    $case_id,
                    $document_name,
                    $document_type,
                    $document_id
                );
            }


            if ($stmt->execute()) {

                $stmt->close();

                header(
                    "Location: view.php?id=" .
                    $document_id .
                    "&success=updated"
                );

                exit;

            } else {

                $error =
                    "Failed to update document: " .
                    $stmt->error;

                $stmt->close();
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
Edit Document - Social Welfare System
</title>

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


/* SIDEBAR */

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

.sidebar a {

    display: flex;

    align-items: center;

    gap: 14px;

    color: #ddd;

    text-decoration: none;

    padding:
        14px 20px;

    font-size: 14px;
}

.sidebar a:hover {

    background: #242424;
}

.sidebar a.active {

    background: #242424;

    border-left:
        4px solid #e53935;
}

.sidebar a i {

    width: 22px;

    text-align: center;
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


/* MAIN */

.main {

    margin-left: 250px;

    padding: 30px;

    min-height: 100vh;
}


/* HEADER */

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


/* BACK */

.back-btn {

    background: #171717;

    color: white;

    text-decoration: none;

    padding:
        11px 18px;

    border-radius: 6px;
}


/* CARD */

.card {

    background: white;

    max-width: 950px;

    padding: 30px;

    border-radius: 10px;

    box-shadow:
        0 2px 8px rgba(0,0,0,.08);
}


/* ERROR */

.error {

    background: #f8d7da;

    color: #842029;

    border:
        1px solid #f5c2c7;

    padding: 13px;

    border-radius: 6px;

    margin-bottom: 20px;
}


/* FORM */

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

.full {

    grid-column:
        1 / -1;
}

label {

    font-weight: bold;

    margin-bottom: 8px;
}

.required {

    color: #e53935;
}

input,
select {

    padding: 13px;

    border:
        1px solid #ccc;

    border-radius: 6px;

    font-size: 15px;
}

input:focus,
select:focus {

    outline: none;

    border-color: #198754;
}

.current-file {

    background: #f5f5f5;

    padding: 12px;

    border-radius: 6px;

    margin-top: 8px;

    color: #555;
}


/* BUTTONS */

.buttons {

    margin-top: 25px;

    display: flex;

    gap: 10px;

    flex-wrap: wrap;
}

.save-btn {

    background: #198754;

    color: white;

    border: none;

    padding:
        13px 22px;

    border-radius: 6px;

    font-weight: bold;

    cursor: pointer;
}

.cancel-btn {

    background: #555;

    color: white;

    text-decoration: none;

    padding:
        13px 22px;

    border-radius: 6px;

    font-weight: bold;
}

@media(max-width: 700px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
    }

    .main {

        margin-left: 0;

        padding: 15px;
    }

    .form-grid {

        grid-template-columns: 1fr;
    }
}

</style>

</head>

<body>


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


<div class="main">

<div class="page-header">

<h1>

<i class="fas fa-edit"></i>

Edit Document

</h1>

<a
href="view.php?id=<?php echo $document_id; ?>"
class="back-btn"
>

<i class="fas fa-arrow-left"></i>

Back to Document

</a>

</div>


<div class="card">


<?php if (!empty($error)): ?>

<div class="error">

<i class="fas fa-exclamation-circle"></i>

<?php echo htmlspecialchars($error); ?>

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

<select name="client_id" required>

<option value="">
Select Client
</option>

<?php while ($client = $clients->fetch_assoc()): ?>

<option
value="<?php echo $client["client_id"]; ?>"

<?php

if (
    $client["client_id"]
    ==
    $document["client_id"]
) {

    echo "selected";
}

?>
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

</select>

</div>


<!-- CASE -->

<div class="form-group full">

<label>

Case

</label>

<select name="case_id">

<option value="">
No Case
</option>

<?php while ($case = $cases->fetch_assoc()): ?>

<option
value="<?php echo $case["case_id"]; ?>"

<?php

if (
    !empty($document["case_id"]) &&
    $document["case_id"]
    ==
    $case["case_id"]
) {

    echo "selected";
}

?>
>

<?php

echo htmlspecialchars(
    $case["case_number"]
    . " - "
    . $case["case_type"]
);

?>

</option>

<?php endwhile; ?>

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
value="<?php
echo htmlspecialchars(
    $document["document_name"]
);
?>"
required
>

</div>


<!-- DOCUMENT TYPE -->

<div class="form-group">

<label>

Document Type

</label>

<select name="document_type">

<?php

$types = [
    "Case File",
    "Medical Report",
    "Birth Certificate",
    "Death Certificate",
    "Identification",
    "Assessment Report",
    "Referral Letter",
    "Court Document",
    "Other"
];

foreach ($types as $type):

?>

<option
value="<?php echo htmlspecialchars($type); ?>"

<?php

if (
    $document["document_type"]
    ===
    $type
) {

    echo "selected";
}

?>
>

<?php echo htmlspecialchars($type); ?>

</option>

<?php endforeach; ?>

</select>

</div>


<!-- CURRENT FILE -->

<div class="form-group full">

<label>

Current File

</label>

<div class="current-file">

<i class="fas fa-file"></i>

<?php

echo htmlspecialchars(
    basename(
        $document["file_path"]
    )
);

?>

</div>

</div>


<!-- NEW FILE -->

<div class="form-group full">

<label>

Replace File

</label>

<input
type="file"
name="document_file"
>

<small>

Leave this empty if you do not want
to replace the current file.

Maximum file size: 10 MB.

</small>

</div>


</div>


<div class="buttons">

<button
type="submit"
class="save-btn"
>

<i class="fas fa-save"></i>

Save Changes

</button>


<a
href="view.php?id=<?php echo $document_id; ?>"
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