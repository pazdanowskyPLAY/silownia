        <?php
            require './komponenty/baza_danych.php';

            // wyświetlanie listy klubów
            $conn = db_connect();
            $clubs = [];
            $query = "SELECT Nazwa, Miasto, Adres FROM s_kluby ORDER BY Miasto, Nazwa";
            $result = mysqli_query($conn, $query);

            if ($result) {
                while ($row = mysqli_fetch_array($result)) {
                    $clubs[] = $row;
                }
                mysqli_free_result($result); // po zapisie do tablicy zwolnij pamięć
            }
            
            db_close($conn);

            $page_title = 'FitPol - nowa sieć siłowni';
            require './komponenty/naglowek.php';
        ?>

        <section class="card"> <!-- zaczerpnięte z bootstrapa -->
            <h1>FitPol - nowa sieć siłowni w Polsce!</h1>
            <p>Nowa prosta platforma WWW do rezerwacji zajęć grupowych w naszych klubach.</p>
            <a class="btn" href="logowanie.php">Więc chodź, zaloguj się lub załóż konto!</a>
        </section>

        <section>
            <h2>Dostępne kluby do wybrania:</h2>
            <?php if (count($clubs) > 0) { ?>
                <ul>
                    <?php foreach ($clubs as $club): ?>
                        <li>
                            <strong><?= html_convert($club['Nazwa']); ?></strong><br>
                            <?= html_convert($club['Miasto']); ?>, <?= html_convert($club['Adres']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php } else { ?>
                <p>Ups! Coś poszło nie tak przy pobieraniu listy naszych klubów, prosimy spróbować ponownie.</p>
            <?php } ?>
        </section>

        <section>
            <h2>Jak działa rezerwacja w naszym serwisie?</h2>
            <ol>
                <li>Załóż konto w formularzu logowania.</li>
                <li>Przejdź do kalendarza i wybierz dzień.</li>
                <li>Zapisz się na nasze zajęcia i zarządzaj nimi bezproblemowo w ustawieniach.</li>
                <li>I to tyle, prościej się nie da!</li>
            </ol>
        </section>

        <?php include './komponenty/stopka.php'; ?>
