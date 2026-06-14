        <?php
            require './komponenty/baza_danych.php';
            require_login();

            $conn       = db_connect();
            $user_id    = current_user_id();
            $errors     = [];
            $message    = null;

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $akcja = $_POST['akcja'] ?? '';

                if ($akcja === 'anuluj') {
                    $rezerwacja_id = (int)($_POST['rezerwacja_id'] ?? 0);
                    if ($rezerwacja_id > 0) {
                        // https://pasja-informatyki.pl/programowanie-webowe/php-mysqli-wariant-proceduralny/
                        $stmt = mysqli_prepare($conn, 'DELETE FROM s_rezerwacje WHERE Id = ? AND Uzytkownicy_id = ?;');
                        mysqli_stmt_bind_param($stmt, 'ii', $rezerwacja_id, $user_id);
                        mysqli_stmt_execute($stmt);
                        if (mysqli_stmt_affected_rows($stmt) > 0) {
                            $message = 'Anulowano rezerwację.';
                        } else {
                            $errors[] = 'Nie udało się anulować rezerwacji, spróbuj ponownie.';
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $errors[] = 'Nieprawidłowa rezerwacja, należy skontaktować się z administratorem strony.';
                    }
                }

                if ($akcja === 'aktualizuj') {
                    $imie       = trim($_POST['imie'] ?? '');
                    $nazwisko   = trim($_POST['nazwisko'] ?? '');
                    $rok        = $_POST['rok_urodzenia'] ?? 0;
                    $email      = trim($_POST['email'] ?? '');
                    $telefon    = trim($_POST['telefon'] ?? '');

                    if ($imie === '' || $nazwisko === '' || $email === '')  $errors[] = 'Uzupełnij wymagane dane konta.';
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL))         $errors[] = 'Podaj poprawny email.';
                    if ($rok < 1900 || $rok > (int)date('Y'))               $errors[] = 'Podaj poprawny rok urodzenia.';

                    if (empty($errors)) {
                        $stmt = mysqli_prepare($conn, 'SELECT Id FROM s_uzytkownicy WHERE Email = ? AND Id <> ?;');
                        mysqli_stmt_bind_param($stmt, 'si', $email, $user_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        $exists = $result ? mysqli_fetch_array($result) : null;
                        mysqli_stmt_close($stmt);

                        if ($exists) {
                            $errors[] = 'Inne konto już używa tego emaila.';
                        } else {
                            $telefonValue = $telefon !== '' ? $telefon : null;
                            $stmt = mysqli_prepare($conn, 'UPDATE s_uzytkownicy SET Imie = ?, Nazwisko = ?, Rok_urodzenia = ?, Email = ?, Telefon = ? WHERE Id = ?;');
                            mysqli_stmt_bind_param($stmt, 'ssissi', $imie, $nazwisko, $rok, $email, $telefonValue, $user_id);
                            if (mysqli_stmt_execute($stmt)) {
                                $_SESSION['user_name'] = $imie . ' ' . $nazwisko;
                                $message = 'Zaktualizowano dane konta.';
                            } else {
                                $errors[] = 'Nie udało sie zaktualizować danych, spróbuj ponownie później.';
                            }
                            mysqli_stmt_close($stmt);
                        }
                    }
                }

                // https://learnxinyminutes.com/php/
                if ($akcja === 'usun') {
                    $potwierdzenie = trim($_POST['potwierdzenie'] ?? '');
                    if ($potwierdzenie !== 'USUŃ') {
                        $errors[] = 'Aby usunąć konto wpisz USUŃ.';
                    } else {
                        $stmt = mysqli_prepare($conn, 'DELETE FROM s_rezerwacje WHERE Uzytkownicy_id = ?;');
                        mysqli_stmt_bind_param($stmt, 'i', $user_id);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);

                        $stmt = mysqli_prepare($conn, 'DELETE FROM s_uzytkownicy WHERE Id = ?;');
                        mysqli_stmt_bind_param($stmt, 'i', $user_id);
                        mysqli_stmt_execute($stmt);
                        $deleted = mysqli_stmt_affected_rows($stmt) > 0;
                        mysqli_stmt_close($stmt);

                        if ($deleted) {
                            session_unset(); session_destroy();
                            db_close($conn);
                            header('Location: index.php');
                            exit;
                        }

                        $errors[] = 'Nie udało się usunąć konta, proszę skontaktować się z administratorem strony.';
                    }
                }
            }

            $stmt = mysqli_prepare($conn, 'SELECT Imie, Nazwisko, Rok_urodzenia, Email, Telefon FROM s_uzytkownicy WHERE Id = ?;');
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = $result ? mysqli_fetch_array($result) : null;
            mysqli_stmt_close($stmt);

            $reservations = [];
            $stmt = mysqli_prepare(
                $conn,
                'SELECT r.Id, z.Data_zajec, z.Czas_zajec,
                k.Nazwa AS Klasa, kat.Nazwa AS Kategoria, kl.Nazwa AS Klub, s.Nazwa AS Sala, t.Imie AS Trener_imie, t.Nazwisko AS Trener_nazwisko
                FROM s_rezerwacje AS r

                JOIN s_zajecia AS z ON z.Id = r.Zajecia_id
                JOIN s_kluby AS kl ON kl.Id = z.Kluby_id
                JOIN s_sale AS s ON s.Id = z.Sale_id
                JOIN s_kategorie AS kat ON kat.Id = z.Kategorie_id
                JOIN s_klasy AS k ON k.Id = z.Klasy_id
                JOIN s_trenerzy AS t ON t.Id = z.Trenerzy_id

                WHERE r.Uzytkownicy_id = ?
                ORDER BY z.Data_zajec, z.Czas_zajec;'
            );
            // KOLEJNA KWERENDA, CHRYSTE PANIE!
            mysqli_stmt_bind_param($stmt, 'i', $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                while ($row = mysqli_fetch_array($result)) {
                    $reservations[] = $row;
                }
            }
            mysqli_stmt_close($stmt);

            db_close($conn);

            $page_title = 'Ustawienia';
            include __DIR__ . '/komponenty/naglowek.php'; // zamiast wpisywania ścieżki względnej pliku, alternatywa
        ?>

        <section>
            <h1>Ustawienia konta</h1>

            <?php if ($message): ?>
                <div class="alert success"><?= html_convert($message); ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= html_convert($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="grid-two">
                <div class="card">
                    <h2>Dane konta</h2>
                    <form method="post" action="ustawienia.php">
                        <input type="hidden" name="akcja" value="aktualizuj">
                        <label>Imię
                            <input type="text" name="imie" value="<?= html_convert($user['Imie'] ?? ''); ?>" required></label>
                        <label>Nazwisko
                            <input type="text" name="nazwisko" value="<?= html_convert($user['Nazwisko'] ?? ''); ?>" required></label>
                        <label>Rok urodzenia
                            <input type="number" name="rok_urodzenia" min="1900" max="<?= html_convert(date('Y')); ?>"
                            value="<?= html_convert($user['Rok_urodzenia'] ?? ''); ?>" required></label>
                        <label>Email
                            <input type="email" name="email" value="<?= html_convert($user['Email'] ?? ''); ?>" required></label>
                        <label>Telefon
                            <input type="text" name="telefon" value="<?= html_convert($user['Telefon'] ?? ''); ?>"></label>

                        <button class="btn" type="submit">Zapisz zmiany</button>
                    </form>
                </div>
                <div class="card danger">
                    <h2>Usunięcie konta</h2>
                    <form method="post" action="ustawienia.php">
                        <input type="hidden" name="akcja" value="usun">
                        <label>
                            Wpisz USUŃ, aby potwierdzić
                            <input type="text" name="potwierdzenie" placeholder="USUŃ" required>
                        </label>
                        <button class="btn danger" type="submit">Usun konto</button>
                    </form>
                </div>
            </div>
        </section>

        <section>
            <h2>Twoje rezerwacje</h2>
            <?php if (empty($reservations)): ?>
                <p>Nie masz jeszcze rezerwacji.</p>
            <?php else: ?>
                <table class="schedule">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Godzina</th>
                            <th>Zajecia</th>
                            <th>Trener</th>
                            <th>Klub / Sala</th>
                            <th>Akcja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?= html_convert($reservation['Data_zajec']); ?></td>
                                <td><?= html_convert(substr($reservation['Czas_zajec'], 0, 5)); ?></td>
                                <td><?= html_convert($reservation['Klasa']); ?> (<?= html_convert($reservation['Kategoria']); ?>)</td>
                                <td><?= html_convert($reservation['Trener_imie']); ?> <?= html_convert($reservation['Trener_nazwisko']); ?></td>
                                <td><?= html_convert($reservation['Klub']); ?>, <?= html_convert($reservation['Sala']); ?></td>
                                <!-- jednak to ładniej wygląda w kodzie jak się zrobi jedno wielkie "echo '...';" -->
                                <td>
                                    <form method="post" action="ustawienia.php">
                                        <input type="hidden" name="akcja" value="anuluj">
                                        <input type="hidden" name="rezerwacja_id" value="<?= html_convert($reservation['Id']); ?>">
                                        <button class="btn" type="submit">Anuluj</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <?php require './komponenty/stopka.php'; ?>
