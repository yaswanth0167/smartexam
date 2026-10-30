<?php

session_start();

function require_login()
{
    if (!isset($_SESSION["user_id"])) {

        header("Location: ../login.php");

        exit();
    }
}

function require_student()
{
    require_login();

    if ($_SESSION["role"] !== "student") {

        header("Location: ../login.php");

        exit();
    }
}

function require_admin()
{
    require_login();

    if ($_SESSION["role"] !== "admin") {

        header("Location: ../login.php");

        exit();
    }
}

?>