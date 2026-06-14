<?php
    if (!isset($page_title)) {
        $page_title = 'FitPol';
    }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= html_convert($page_title); ?></title>
	<link rel="stylesheet" href="styl.css">
</head>

<body>
    <header>
        <a href="index.php" id="logo">FitPol</a>

        <nav>
            <a href="index.php">Start</a>
            <?php if (is_logged_in()) { ?>
                <a href="kalendarz.php">Kalendarz</a>
                <a href="ustawienia.php">Ustawienia</a>
                <a href="logowanie.php?akcja=wyloguj">Wyloguj</a>
            <?php } else { ?>
                <a href="logowanie.php">Logowanie</a>
            <?php } ?>
            <!-- opcjonalnie if(): ... else: ... endif; -->
        </nav>
    </header>

    <main>
    