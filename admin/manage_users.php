<?php

require_once "../includes/auth.php";
require_once "../config/database.php";

require_admin();

date_default_timezone_set("Asia/Kolkata");


// ============================================================
// DELETE USER
// ============================================================

if (
    isset($_GET["delete"]) &&
    is_numeric($_GET["delete"])
) {

    $delete_id = intval($_GET["delete"]);


    // Prevent admin from deleting himself
    if (
        isset($_SESSION["user_id"]) &&
        $delete_id == $_SESSION["user_id"]
    ) {

        header("Location: manage_users.php?error=self_delete");

        exit();

    }


    // Delete user
    $delete_sql = "

        DELETE FROM users

        WHERE id = ?

        AND role = 'student'

    ";


    $delete_stmt =
        $conn->prepare($delete_sql);


    if ($delete_stmt) {

        $delete_stmt->bind_param(
            "i",
            $delete_id
        );

        $delete_stmt->execute();

        $delete_stmt->close();

    }


    header(
        "Location: manage_users.php?success=deleted"
    );

    exit();

}


// ============================================================
// FETCH USER STATISTICS
// ============================================================


// TOTAL USERS

$total_users_sql = "

    SELECT COUNT(*) AS total

    FROM users

";

$total_users_result =
    $conn->query($total_users_sql);


$total_users = 0;


if ($total_users_result) {

    $total_users = intval(

        $total_users_result
            ->fetch_assoc()["total"]

    );

}


// TOTAL STUDENTS

$total_students_sql = "

    SELECT COUNT(*) AS total

    FROM users

    WHERE role = 'student'

";

$total_students_result =
    $conn->query($total_students_sql);


$total_students = 0;


if ($total_students_result) {

    $total_students = intval(

        $total_students_result
            ->fetch_assoc()["total"]

    );

}


// TOTAL ADMINS

$total_admins_sql = "

    SELECT COUNT(*) AS total

    FROM users

    WHERE role = 'admin'

";

$total_admins_result =
    $conn->query($total_admins_sql);


$total_admins = 0;


if ($total_admins_result) {

    $total_admins = intval(

        $total_admins_result
            ->fetch_assoc()["total"]

    );

}


// ============================================================
// SEARCH
// ============================================================

$search = "";


if (
    isset($_GET["search"])
) {

    $search =
        trim(
            $_GET["search"]
        );

}


// ============================================================
// FETCH USERS
// ============================================================

if (
    $search !== ""
) {


    $user_sql = "

        SELECT

            id,

            name,

            email,

            role,

            created_at

        FROM users

        WHERE

            name LIKE ?

            OR

            email LIKE ?

            OR

            role LIKE ?

        ORDER BY

            created_at DESC

    ";


    $user_stmt =
        $conn->prepare($user_sql);


    $search_value =
        "%" . $search . "%";


    $user_stmt->bind_param(

        "sss",

        $search_value,

        $search_value,

        $search_value

    );


    $user_stmt->execute();


    $user_result =
        $user_stmt->get_result();

} else {


    $user_sql = "

        SELECT

            id,

            name,

            email,

            role,

            created_at

        FROM users

        ORDER BY

            created_at DESC

    ";


    $user_result =
        $conn->query($user_sql);

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
        Manage Users - SmartExam
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>


        .admin-container {

            max-width: 1400px;

            margin: 0 auto;

        }


        .page-header {

            margin-bottom: 30px;

        }


        .page-header h1 {

            font-size: 32px;

            margin-bottom: 8px;

        }


        .page-header p {

            color: var(--text-muted);

        }


        .stats-grid {

            display: grid;

            grid-template-columns:

                repeat(

                    auto-fit,

                    minmax(

                        210px,

                        1fr

                    )

                );

            gap: 20px;

            margin-bottom: 30px;

        }


        .stat-card {

            padding: 22px;

            border-radius: 16px;

            background:

                linear-gradient(

                    145deg,

                    rgba(

                        30,

                        41,

                        59,

                        0.95

                    ),

                    rgba(

                        15,

                        23,

                        42,

                        0.95

                    )

                );

            border:

                1px solid

                rgba(

                    148,

                    163,

                    184,

                    0.15

                );

        }


        .stat-icon {

            font-size: 28px;

            margin-bottom: 10px;

        }


        .stat-value {

            font-size: 30px;

            font-weight: bold;

            margin-bottom: 5px;

        }


        .stat-label {

            color: var(--text-muted);

            font-size: 13px;

        }


        .toolbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            flex-wrap: wrap;

            margin-bottom: 20px;

        }


        .search-form {

            display: flex;

            gap: 10px;

            flex: 1;

            max-width: 500px;

        }


        .search-form input {

            flex: 1;

            padding: 12px 15px;

            border-radius: 9px;

            border: 1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.12

                );

            background:

                rgba(

                    255,

                    255,

                    255,

                    0.06

                );

            color: white;

            outline: none;

        }


        .search-form input:focus {

            border-color:

                var(--accent);

        }


        .search-form button {

            padding: 12px 18px;

            border: none;

            border-radius: 9px;

            background:

                var(--accent);

            color: white;

            font-weight: bold;

            cursor: pointer;

        }


        .clear-search {

            padding: 12px 15px;

            border-radius: 9px;

            text-decoration: none;

            background:

                rgba(

                    255,

                    255,

                    255,

                    0.08

                );

            color: white;

        }


        .table-card {

            padding: 20px;

            border-radius: 16px;

            background:

                rgba(

                    255,

                    255,

                    255,

                    0.04

                );

            border:

                1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.08

                );

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 850px;

        }


        th {

            text-align: left;

            padding: 15px;

            color: var(--text-muted);

            font-size: 12px;

            text-transform: uppercase;

            border-bottom:

                1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.1

                );

        }


        td {

            padding: 16px 15px;

            border-bottom:

                1px solid

                rgba(

                    255,

                    255,

                    255,

                    0.06

                );

        }


        tr:hover {

            background:

                rgba(

                    255,

                    255,

                    255,

                    0.03

                );

        }


        .user-name {

            font-weight: bold;

        }


        .user-email {

            color: var(--text-muted);

            font-size: 13px;

            margin-top: 4px;

        }


        .role-badge {

            display: inline-block;

            padding: 6px 11px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: bold;

            text-transform: uppercase;

        }


        .role-student {

            background:

                rgba(

                    59,

                    130,

                    246,

                    0.15

                );

            color: #93c5fd;

        }


        .role-admin {

            background:

                rgba(

                    168,

                    85,

                    247,

                    0.15

                );

            color: #d8b4fe;

        }


        .delete-btn {

            display: inline-block;

            padding: 7px 12px;

            border-radius: 7px;

            background:

                rgba(

                    239,

                    68,

                    68,

                    0.15

                );

            color: #fca5a5;

            text-decoration: none;

            font-size: 12px;

            font-weight: bold;

        }


        .delete-btn:hover {

            background:

                rgba(

                    239,

                    68,

                    68,

                    0.3

                );

        }


        .protected-user {

            color: var(--text-muted);

            font-size: 12px;

        }


        .alert {

            padding: 14px 18px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;

        }


        .alert-success {

            background:

                rgba(

                    34,

                    197,

                    94,

                    0.15

                );

            color: #86efac;

        }


        .alert-error {

            background:

                rgba(

                    239,

                    68,

                    68,

                    0.15

                );

            color: #fca5a5;

        }


        .empty-state {

            text-align: center;

            padding: 50px;

            color: var(--text-muted);

        }


        @media (max-width: 700px) {

            .page-header h1 {

                font-size: 26px;

            }

        }


    </style>

</head>


<body>


<div class="dashboard-layout">


    <!-- SIDEBAR -->

    <aside class="sidebar">


        <div class="sidebar-logo">

            Smart<span>Exam</span>

        </div>


        <nav class="sidebar-nav">


            <a

                href="dashboard.php"

                class="sidebar-link"

            >

                <span>📊</span>

                Dashboard

            </a>


            <a

                href="exams.php"

                class="sidebar-link"

            >

                <span>📝</span>

                Manage Exams

            </a>


            <a

                href="questions.php"

                class="sidebar-link"

            >

                <span>❓</span>

                Question Bank

            </a>


            <a

                href="manage_users.php"

                class="sidebar-link active"

            >

                <span>👥</span>

                Manage Users

            </a>


            <a

                href="manage_rooms.php"

                class="sidebar-link"

            >

                <span>🏠</span>

                Manage Rooms

            </a>


            <a

                href="results.php"

                class="sidebar-link"

            >

                <span>📈</span>

                Results

            </a>


        </nav>


        <div class="sidebar-footer">


            <a

                href="../logout.php"

                class="btn btn-danger"

                style="width:100%;"

            >

                🚪 Logout

            </a>


        </div>


    </aside>


    <!-- MAIN CONTENT -->

    <main class="main-content">


        <div class="admin-container">


            <!-- HEADER -->

            <div class="page-header">


                <h1>

                    👥 Manage Users

                </h1>


                <p>

                    View and manage registered SmartExam users.

                </p>


            </div>


            <!-- ALERTS -->

            <?php if (

                isset($_GET["success"])

                &&

                $_GET["success"] === "deleted"

            ): ?>


                <div

                    class="alert alert-success"

                >

                    ✅ User deleted successfully.

                </div>


            <?php endif; ?>


            <?php if (

                isset($_GET["error"])

                &&

                $_GET["error"] === "self_delete"

            ): ?>


                <div

                    class="alert alert-error"

                >

                    ⚠️ You cannot delete your own admin account.

                </div>


            <?php endif; ?>


            <!-- STATISTICS -->

            <div class="stats-grid">


                <div class="stat-card">


                    <div class="stat-icon">

                        👥

                    </div>


                    <div class="stat-value">

                        <?php

                        echo $total_users;

                        ?>

                    </div>


                    <div class="stat-label">

                        Total Users

                    </div>


                </div>


                <div class="stat-card">


                    <div class="stat-icon">

                        🎓

                    </div>


                    <div class="stat-value">

                        <?php

                        echo $total_students;

                        ?>

                    </div>


                    <div class="stat-label">

                        Students

                    </div>


                </div>


                <div class="stat-card">


                    <div class="stat-icon">

                        🛡️

                    </div>


                    <div class="stat-value">

                        <?php

                        echo $total_admins;

                        ?>

                    </div>


                    <div class="stat-label">

                        Administrators

                    </div>


                </div>


            </div>


            <!-- SEARCH -->

            <div class="toolbar">


                <form

                    method="GET"

                    class="search-form"

                >


                    <input

                        type="text"

                        name="search"

                        placeholder="Search by name, email or role..."

                        value="<?php

                            echo htmlspecialchars(

                                $search

                            );

                        ?>"

                    >


                    <button

                        type="submit"

                    >

                        🔍 Search

                    </button>


                    <?php if (

                        $search !== ""

                    ): ?>


                        <a

                            href="manage_users.php"

                            class="clear-search"

                        >

                            Clear

                        </a>


                    <?php endif; ?>


                </form>


            </div>


            <!-- USERS TABLE -->

            <div class="table-card">


                <?php if (

                    $user_result

                    &&

                    $user_result->num_rows > 0

                ): ?>


                    <table>


                        <thead>


                            <tr>


                                <th>

                                    #

                                </th>


                                <th>

                                    User

                                </th>


                                <th>

                                    Role

                                </th>


                                <th>

                                    Registered On

                                </th>


                                <th>

                                    Action

                                </th>


                            </tr>


                        </thead>


                        <tbody>


                            <?php

                            $counter = 1;


                            while (

                                $user =

                                $user_result

                                    ->fetch_assoc()

                            ):

                            ?>


                                <tr>


                                    <td>

                                        <?php

                                        echo $counter++;

                                        ?>

                                    </td>


                                    <td>


                                        <div

                                            class="user-name"

                                        >

                                            <?php

                                            echo htmlspecialchars(

                                                $user["name"]

                                            );

                                            ?>

                                        </div>


                                        <div

                                            class="user-email"

                                        >

                                            <?php

                                            echo htmlspecialchars(

                                                $user["email"]

                                            );

                                            ?>

                                        </div>


                                    </td>


                                    <td>


                                        <span

                                            class="role-badge role-<?php

                                            echo htmlspecialchars(

                                                strtolower(

                                                    $user["role"]

                                                )

                                            );

                                            ?>"

                                        >

                                            <?php

                                            echo htmlspecialchars(

                                                $user["role"]

                                            );

                                            ?>

                                        </span>


                                    </td>


                                    <td>


                                        <?php

                                        if (

                                            !empty(

                                                $user["created_at"]

                                            )

                                        ) {

                                            echo date(

                                                "d M Y, h:i A",

                                                strtotime(

                                                    $user["created_at"]

                                                )

                                            );

                                        } else {

                                            echo "N/A";

                                        }

                                        ?>


                                    </td>


                                    <td>


                                        <?php if (

                                            strtolower(

                                                $user["role"]

                                            )

                                            ===

                                            "student"

                                        ): ?>


                                            <a

                                                href="manage_users.php?delete=<?php

                                                echo intval(

                                                    $user["id"]

                                                );

                                                ?>"

                                                class="delete-btn"

                                                onclick="return confirm(

                                                    'Are you sure you want to delete this student? This action cannot be undone.'

                                                );"

                                            >

                                                🗑️ Delete

                                            </a>


                                        <?php else: ?>


                                            <span

                                                class="protected-user"

                                            >

                                                🔒 Protected

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        </tbody>


                    </table>


                <?php else: ?>


                    <div class="empty-state">


                        📭 No users found.

                    </div>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


</body>

</html>