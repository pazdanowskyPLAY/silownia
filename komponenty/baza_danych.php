<?php

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        // jak ni ma sesji to mi robić sesję!
    }

    function db_connect() {
        $conn = mysqli_connect('localhost', 'root', '', 'silownia');
        if (!$conn) die('Błąd połączenia z bazą danych: ' . mysqli_connect_error());

        // mysqli_set_charset($conn, 'utf8mb4');
        // fajna rzecz, może przydać się kiedyś w przyszłości

        return $conn;
    }

    function db_close($conn) {
        if ($conn) {
            mysqli_close($conn);
        }
        // nie wiem ¯\_(ツ)_/¯
    }

    function html_convert($value) {
        return htmlspecialchars(($value ?? ''), ENT_QUOTES, 'UTF-8');
        // w przypadku ataków XSS
        // https://sekurak.pl/czym-jest-xss/
    }

    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }

    function current_user_id() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        // warunek ? jeśli_prawda : jeśli_fałsz
        // https://www.geeksforgeeks.org/php/php-ternary-operator/
    }

    function require_login() {
        if (!is_logged_in()) {
            // hasta la vista, baby
            header('Location: logowanie.php');
            exit;
        }
    }

?>