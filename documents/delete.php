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

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {

    header("Location: index.php");

    exit;
}

$document_id = (int) $_GET["id"];


/* =========================
   GET DOCUMENT
========================= */

$stmt = $conn->prepare("
    SELECT
        document_id,
        file_path
    FROM documents
    WHERE document_id = ?
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


/* =========================
   DELETE DATABASE RECORD
========================= */

$stmt = $conn->prepare("
    DELETE FROM documents
    WHERE document_id = ?
");

$stmt->bind_param(
    "i",
    $document_id
);


if ($stmt->execute()) {

    $stmt->close();


    /* =========================
       DELETE PHYSICAL FILE
    ========================= */

    $file_path =
        trim(
            $document["file_path"]
        );


    if (!empty($file_path)) {

        $physical_path =
            __DIR__ .
            "/" .
            ltrim(
                str_replace(
                    "\\",
                    "/",
                    $file_path
                ),
                "/"
            );


        if (
            file_exists(
                $physical_path
            )
        ) {

            unlink(
                $physical_path
            );
        }
    }


    /* =========================
       SUCCESS
    ========================= */

    header(
        "Location: index.php?success=deleted"
    );

    exit;


} else {

    $error =
        $stmt->error;

    $stmt->close();


    header(
        "Location: view.php?id=" .
        $document_id .
        "&error=" .
        urlencode($error)
    );

    exit;
}

?>