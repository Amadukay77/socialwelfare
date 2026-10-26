
<?php
session_start();

require_once "../includes/db.php";
require_once "../includes/permissions.php";

// Require permission to view client records
require_permission("clients_view");


/* =========================
   SEARCH
========================= */

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

/* =========================
   GET CLIENTS
========================= */

if ($search !== "") {

    $stmt = $conn->prepare("
        SELECT *
        FROM clients
        WHERE client_number LIKE ?
        OR full_name LIKE ?
        OR phone LIKE ?
        OR chiefdom LIKE ?
        OR section LIKE ?
        ORDER BY client_id DESC
    ");

    $search_value = "%" . $search . "%";

    $stmt->bind_param(
        "sssss",
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value
    );

    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $result = $conn->query("
        SELECT *
        FROM clients
        ORDER BY client_id DESC
    ");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Clients - Social Welfare System</title>

<!-- FONT AWESOME -->

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


<style>

/* =========================
   GENERAL
========================= */

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

    width: 235px;

    height: 100vh;

    background: #171717;

    color: white;

    overflow-y: auto;

    box-shadow:
        3px 0 12px rgba(0,0,0,0.15);
}


/* LOGO */

.logo {

    text-align: center;

    padding:
        25px 15px 22px;

    border-bottom:
        1px solid #333;
}

.logo-circle {

    width: 58px;
    height: 58px;

    margin: auto;

    border-radius: 50%;

    background: white;

    color: #222;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;

    font-weight: bold;
}

.logo h2 {

    margin:
        12px 0 3px;

    font-size: 18px;
}

.logo p {

    margin: 0;

    font-size: 11px;

    color: #aaa;
}


/* MENU TITLE */

.menu-title {

    padding:
        20px 18px 8px;

    font-size: 10px;

    color: #888;

    text-transform: uppercase;

    letter-spacing: 1px;
}


/* SIDEBAR LINKS */

.sidebar a {

    display: flex;

    align-items: center;

    gap: 13px;

    padding:
        12px 18px;

    color: #ddd;

    text-decoration: none;

    font-size: 13px;

    transition: 0.2s;
}

.sidebar a i {

    width: 20px;

    text-align: center;

    font-size: 15px;
}

.sidebar a:hover {

    background: #292929;

    color: white;
}

.sidebar a.active {

    background: #292929;

    color: white;

    border-left:
        4px solid #e53935;

    padding-left: 14px;
}


/* LOGOUT */

.logout-link {

    margin-top: 15px;

    border-top:
        1px solid #333;
}


/* =========================
   MAIN
========================= */

.main {

    margin-left: 235px;

    min-height: 100vh;
}


/* =========================
   TOP BAR
========================= */

.topbar {

    height: 65px;

    background: white;

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    padding:
        0 25px;

    border-bottom:
        1px solid #e5e5e5;
}

.topbar h1 {

    margin: 0;

    font-size: 21px;

    color: #333;
}

.topbar h1 i {

    color: #e53935;

    margin-right: 8px;
}


/* USER */

.user-info {

    display: flex;

    align-items: center;

    gap: 9px;
}

.user-circle {

    width: 38px;
    height: 38px;

    border-radius: 50%;

    background: #333;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: bold;
}

.user-text {

    font-size: 12px;
}

.user-text strong {

    display: block;

    color: #333;
}

.user-text span {

    color: #888;
}


/* =========================
   CONTENT
========================= */

.content {

    padding: 25px;
}


/* =========================
   PAGE HEADER
========================= */

.page-header {

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    margin-bottom: 20px;
}

.page-header h2 {

    margin: 0;

    font-size: 22px;

    color: #333;
}

.page-header h2 i {

    color: #e53935;

    margin-right: 8px;
}

.page-header p {

    margin:
        5px 0 0;

    color: #888;

    font-size: 13px;
}


/* ADD BUTTON */

.add-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    background: #e53935;

    color: white;

    padding:
        11px 16px;

    border-radius: 6px;

    text-decoration: none;

    font-size: 13px;

    font-weight: bold;

    transition: 0.2s;
}

.add-btn:hover {

    background: #c62828;

    transform:
        translateY(-1px);
}


/* =========================
   SEARCH
========================= */

.search-box {

    background: white;

    padding: 18px;

    border-radius: 8px;

    border:
        1px solid #e5e5e5;

    margin-bottom: 18px;

    box-shadow:
        0 3px 10px rgba(0,0,0,0.05);
}

.search-form {

    display: flex;

    gap: 10px;
}

.search-input {

    flex: 1;

    padding:
        11px 13px;

    border:
        1px solid #ddd;

    border-radius: 6px;

    font-size: 13px;

    outline: none;
}

.search-input:focus {

    border-color: #e53935;
}

.search-btn {

    border: none;

    background: #333;

    color: white;

    padding:
        0 18px;

    border-radius: 6px;

    cursor: pointer;

    font-size: 13px;
}

.search-btn:hover {

    background: #111;
}

.clear-btn {

    display: flex;

    align-items: center;

    padding:
        0 14px;

    border-radius: 6px;

    background: #eee;

    color: #555;

    text-decoration: none;

    font-size: 13px;
}


/* =========================
   TABLE PANEL
========================= */

.table-panel {

    background: white;

    border-radius: 8px;

    border:
        1px solid #e5e5e5;

    box-shadow:
        0 3px 10px rgba(0,0,0,0.06);

    overflow: hidden;
}


/* TABLE HEADER */

.table-title {

    padding:
        17px 20px;

    border-bottom:
        1px solid #eee;

    display: flex;

    justify-content:
        space-between;

    align-items: center;
}

.table-title h3 {

    margin: 0;

    font-size: 16px;

    color: #333;
}

.table-title h3 i {

    color: #1e88e5;

    margin-right: 7px;
}

.client-count {

    font-size: 12px;

    color: #888;
}


/* TABLE */

.table-container {

    overflow-x: auto;
}

table {

    width: 100%;

    border-collapse:
        collapse;

    min-width: 950px;
}

thead {

    background: #f8f8f8;
}

th {

    padding:
        13px 12px;

    text-align: left;

    font-size: 11px;

    text-transform:
        uppercase;

    color: #777;

    border-bottom:
        1px solid #eee;

    white-space: nowrap;
}

td {

    padding:
        13px 12px;

    font-size: 13px;

    border-bottom:
        1px solid #eee;

    color: #444;
}

tbody tr:hover {

    background: #fafafa;
}


/* CLIENT NUMBER */

.client-number {

    font-weight: bold;

    color: #1e88e5;
}


/* GENDER */

.gender {

    display: inline-block;

    padding:
        4px 8px;

    border-radius: 12px;

    font-size: 10px;

    font-weight: bold;
}

.gender-male {

    background: #eaf3ff;

    color: #1e88e5;
}

.gender-female {

    background: #fdecec;

    color: #e53935;
}

.gender-other {

    background: #f4eafd;

    color: #8e24aa;
}


/* ACTION BUTTONS */

.actions {

    display: flex;

    gap: 5px;
}

.action-btn {

    width: 31px;
    height: 31px;

    border-radius: 5px;

    display: flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    font-size: 12px;
}


/* VIEW */

.view-btn {

    background: #eaf3ff;

    color: #1e88e5;
}

.view-btn:hover {

    background: #1e88e5;

    color: white;
}


/* EDIT */

.edit-btn {

    background: #fff8df;

    color: #f9a825;
}

.edit-btn:hover {

    background: #f9a825;

    color: white;
}


/* DELETE */

.delete-btn {

    background: #fdecec;

    color: #e53935;
}

.delete-btn:hover {

    background: #e53935;

    color: white;
}


/* =========================
   EMPTY STATE
========================= */

.empty {

    padding:
        55px 20px;

    text-align: center;

    color: #888;
}

.empty i {

    font-size: 45px;

    color: #ccc;

    margin-bottom: 15px;
}

.empty h3 {

    margin:
        0 0 7px;

    color: #555;

    font-size: 17px;
}

.empty p {

    margin: 0;

    font-size: 13px;
}


/* =========================
   MOBILE
========================= */

@media (max-width: 800px) {

    .sidebar {

        width: 210px;
    }

    .main {

        margin-left: 210px;
    }
}


@media (max-width: 650px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
    }

    .main {

        margin-left: 0;
    }

    .content {

        padding: 15px;
    }

    .page-header {

        align-items: flex-start;

        gap: 15px;

        flex-direction: column;
    }

    .search-form {

        flex-wrap: wrap;
    }

    .search-input {

        min-width: 100%;
    }

    .search-btn,
    .clear-btn {

        height: 38px;
    }

    .user-text {

        display: none;
    }
}

</style>

</head>
<?php require_once __DIR__ . '/../includes/appearance.php'; ?>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">


    <div class="logo">

        <div class="logo-circle">
            SW
        </div>

        <h2>
            Social Welfare
        </h2>

        <p>
            Tonkolili District
        </p>

    </div>


    <div class="menu-title">
        Main Menu
    </div>


    <a href="../index.php">

        <i class="fas fa-tachometer-alt"></i>

        Dashboard

    </a>


    <a href="index.php"
       class="active">

        <i class="fas fa-users"></i>

        Clients

    </a>


    <a href="../cases/">

        <i class="fas fa-folder-open"></i>

        Cases

    </a>


    <a href="../documents/">

        <i class="fas fa-file-alt"></i>

        Documents

    </a>


    <a href="../social_workers/">

        <i class="fas fa-user-tie"></i>

        Social Workers

    </a>


    <a href="../households/">

        <i class="fas fa-home"></i>

        Households

    </a>


    <a href="../activities/">

        <i class="fas fa-tasks"></i>

        Activities

    </a>


    <a href="../appointments/">

        <i class="fas fa-calendar-alt"></i>

        Appointments

    </a>


    <div class="menu-title">
        Management
    </div>


    <a href="../reports/">

        <i class="fas fa-chart-bar"></i>

        Reports

    </a>


    <a href="../settings/">

        <i class="fas fa-cog"></i>

        Settings

    </a>


    <a href="../auth/logout.php"
       class="logout-link">

        <i class="fas fa-sign-out-alt"></i>

        Logout

    </a>

</div>


<!-- =========================
     MAIN
========================= -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">

        <h1>

            <i class="fas fa-users"></i>

            Clients

        </h1>


        <div class="user-info">

            <div class="user-circle">

                <?php

                echo strtoupper(
                    substr(
                        $_SESSION["full_name"],
                        0,
                        1
                    )
                );

                ?>

            </div>


            <div class="user-text">

                <strong>

                    <?php

                    echo htmlspecialchars(
                        $_SESSION["full_name"]
                    );

                    ?>

                </strong>


                <span>

                    <?php

                    echo htmlspecialchars(
                        $_SESSION["role"]
                    );

                    ?>

                </span>

            </div>

        </div>

    </div>


    <!-- =========================
         CONTENT
    ========================= -->

    <div class="content">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div>

                <h2>

                    <i class="fas fa-user-friends"></i>

                    Client Records

                </h2>

                <p>
                    Manage registered social welfare
                    beneficiaries and client records.
                </p>

            </div>


            <a href="add.php"
               class="add-btn">

                <i class="fas fa-user-plus"></i>

                Register New Client

            </a>

        </div>


        <!-- =========================
             SEARCH
        ========================= -->

        <div class="search-box">

            <form method="GET"
                  class="search-form">

                <input
                    type="text"
                    name="search"
                    class="search-input"
                    placeholder="Search by client number, name, phone, chiefdom or section..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >


                <button
                    type="submit"
                    class="search-btn">

                    <i class="fas fa-search"></i>

                    Search

                </button>


                <?php if ($search !== ""): ?>

                    <a href="index.php"
                       class="clear-btn">

                        <i class="fas fa-times"></i>

                        Clear

                    </a>

                <?php endif; ?>

            </form>

        </div>


        <!-- =========================
             CLIENT TABLE
        ========================= -->

        <div class="table-panel">


            <div class="table-title">

                <h3>

                    <i class="fas fa-list"></i>

                    Registered Clients

                </h3>


                <span class="client-count">

                    <?php

                    if ($result) {

                        echo $result->num_rows;

                    } else {

                        echo "0";

                    }

                    ?>

                    record(s)

                </span>

            </div>


            <?php if ($result && $result->num_rows > 0): ?>


                <div class="table-container">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Client No.
                                </th>

                                <th>
                                    Full Name
                                </th>

                                <th>
                                    Gender
                                </th>

                                <th>
                                    Date of Birth
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Chiefdom
                                </th>

                                <th>
                                    Section
                                </th>

                                <th>
                                    Disability
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php while ($client = $result->fetch_assoc()): ?>


                            <tr>


                                <td>

                                    <span class="client-number">

                                        <?php

                                        echo htmlspecialchars(
                                            $client["client_number"]
                                        );

                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $client["full_name"]
                                        );

                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <?php

                                    $gender_class =
                                        "gender-other";

                                    if ($client["gender"] === "Male") {

                                        $gender_class =
                                            "gender-male";

                                    } elseif (
                                        $client["gender"] === "Female"
                                    ) {

                                        $gender_class =
                                            "gender-female";

                                    }

                                    ?>

                                    <span class="gender <?php echo $gender_class; ?>">

                                        <?php

                                        echo htmlspecialchars(
                                            $client["gender"]
                                        );

                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php

                                    if (!empty(
                                        $client["date_of_birth"]
                                    )) {

                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $client["date_of_birth"]
                                            )
                                        );

                                    } else {

                                        echo "-";

                                    }

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo !empty(
                                        $client["phone"]
                                    )

                                    ? htmlspecialchars(
                                        $client["phone"]
                                    )

                                    : "-";

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo !empty(
                                        $client["chiefdom"]
                                    )

                                    ? htmlspecialchars(
                                        $client["chiefdom"]
                                    )

                                    : "-";

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo !empty(
                                        $client["section"]
                                    )

                                    ? htmlspecialchars(
                                        $client["section"]
                                    )

                                    : "-";

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo !empty(
                                        $client["disability_status"]
                                    )

                                    ? htmlspecialchars(
                                        $client["disability_status"]
                                    )

                                    : "-";

                                    ?>

                                </td>


                                <td>

                                    <div class="actions">


                                        <a
                                            href="view.php?id=<?php echo $client["client_id"]; ?>"
                                            class="action-btn view-btn"
                                            title="View Client"
                                        >

                                            <i class="fas fa-eye"></i>

                                        </a>


                                        <a
                                            href="edit.php?id=<?php echo $client["client_id"]; ?>"
                                            class="action-btn edit-btn"
                                            title="Edit Client"
                                        >

                                            <i class="fas fa-edit"></i>

                                        </a>


                                        <a
                                            href="delete.php?id=<?php echo $client["client_id"]; ?>"
                                            class="action-btn delete-btn"
                                            title="Delete Client"
                                            onclick="return confirm('Are you sure you want to delete this client?');"
                                        >

                                            <i class="fas fa-trash"></i>

                                        </a>


                                    </div>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty">

                    <i class="fas fa-users-slash"></i>

                    <h3>
                        No Clients Found
                    </h3>

                    <p>

                        <?php

                        if ($search !== "") {

                            echo "No client records matched your search.";

                        } else {

                            echo "No clients have been registered yet.";

                        }

                        ?>

                    </p>

                </div>


            <?php endif; ?>


        </div>


    </div>

</div>


</body>

</html>