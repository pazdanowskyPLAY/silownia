		<?php
			require './komponenty/baza_danych.php';
			require_login();

			$conn 		= db_connect();
			$user_id 	= current_user_id();
			$errors 	= []; 					// tablica, jakby się nawaliło więcej niż jeden błąd
			$message 	= null; 				// informacja, że wszystko poszło OK bez błędów

			$date = $_GET['data'] ?? date('Y-m-d'); // instrukcja ?? else
			if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { // regex, XXXX-XX-XX dla X = dowolna cyfra
				$date = date('Y-m-d');
			}

			if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['akcja'] ?? '') === 'rezerwuj') {
				$zajecia_id = $_POST['zajecia_id'] ?? 0;
				// $zajecia_id = (int)($_POST['zajecia_id'] ?? 0);
				// (typ_danych)(...) - trochę jak parseInt czy parseFloat, ale z nieco dziwniejszą składnią

				if ($zajecia_id <= 0) { // na wszelki wypadek
					$errors[] = 'Numer ID zajęcia jest nieprawidłowy, proszę skontaktować się z administratorem strony.';
				}
				else {
					$stmt = mysqli_prepare($conn, 'SELECT Id FROM s_rezerwacje WHERE Zajecia_id = ? AND Uzytkownicy_id = ?;');
					// należy przygotować kwerendę SQL, aby ...
					mysqli_stmt_bind_param($stmt, 'ii', $zajecia_id, $user_id);
					// https://www.tutorialspoint.com/php/php_function_mysqli_stmt_bind_param.htm
					// i=integer, d=double, s=string, b=blob
					// ... można byłoby wsadzić odpowiednie zmienne wewnątrz tego tekstu (znaki zapytania), ...
					mysqli_stmt_execute($stmt);
					// ... następnie tak spreparowaną kwerendę wykonujemy przez mysqli_stmt_execute, ...
					mysqli_stmt_store_result($stmt);
					// ... wynik przechowujemy, ...
					if (mysqli_stmt_num_rows($stmt) > 0) {
						$errors[] = 'Masz już rezerwację na te zajęcia.';
					}
					// ... cośtam sobie wykonujemy ...
					mysqli_stmt_close($stmt);
					// ... i kończymy wykonywanie kwerendy!
					// trochę przekomplikowane, ale fajne, może przydać się w przyszłości

					if (empty($errors)) {
						$stmt = mysqli_prepare($conn,
							'SELECT z.Liczba_miejsc, COUNT(r.Id) AS Zajete
							FROM s_zajecia AS z LEFT JOIN s_rezerwacje AS r ON r.Zajecia_id = z.Id
							WHERE z.Id = ? GROUP BY z.Id, z.Liczba_miejsc;');
						mysqli_stmt_bind_param($stmt, 'i', $zajecia_id);
						mysqli_stmt_execute($stmt);
						$result = mysqli_stmt_get_result($stmt);
						$row = $result ? mysqli_fetch_array($result) : null;
						mysqli_stmt_close($stmt);

						if (!$row)											$errors[] = 'Nie znaleziono zajęć.';
						elseif ($row['Zajete'] >= $row['Liczba_miejsc']) 	$errors[] = 'Brak wolnych miejsc.';
						else {
							$stmt = mysqli_prepare($conn, 'INSERT INTO s_rezerwacje (Zajecia_id, Uzytkownicy_id) VALUES (?, ?);');
							mysqli_stmt_bind_param($stmt, 'ii', $zajecia_id, $user_id);
								if (mysqli_stmt_execute($stmt)) $message = 'Pomyślnie zapisano na zajęcia.';
								else 							$errors[] = 'Nie udało się zapisać na zajęcia, proszę spróbować później.';
							mysqli_stmt_close($stmt);
						}
					}
				}
			}

			$classes = [];
			$stmt = mysqli_prepare(
				$conn,
			    'SELECT z.Id, z.Czas_zajec, z.Liczba_miejsc,
				 k.Nazwa AS Klasa, kat.Nazwa AS Kategoria, t.Imie AS Trener_imie, t.Nazwisko AS Trener_nazwisko, kl.Nazwa AS Klub, s.Nazwa AS Sala,
				 COUNT(r.Id) AS Zajete, MAX(CASE WHEN r.Uzytkownicy_id = ? THEN 1 ELSE 0 END) AS Czy_moje
				 
				 FROM s_zajecia AS z
				 JOIN s_kluby AS kl ON kl.Id = z.Kluby_id
				 JOIN s_sale AS s ON s.Id = z.Sale_id
				 JOIN s_kategorie AS kat ON kat.Id = z.Kategorie_id
				 JOIN s_klasy AS k ON k.Id = z.Klasy_id
				 JOIN s_trenerzy AS t ON t.Id = z.Trenerzy_id
				 LEFT JOIN s_rezerwacje AS r ON r.Zajecia_id = z.Id
				 
				 WHERE z.Data_zajec = ?
				 GROUP BY z.Id, z.Czas_zajec, z.Liczba_miejsc, k.Nazwa, kat.Nazwa, t.Imie, t.Nazwisko, kl.Nazwa, s.Nazwa
				 ORDER BY z.Czas_zajec;'
			); // Powodzenia z układaniem takiej kwerendy na własną rękę - po godzinie testowania w końcu się udało, ale nigdy więcej
			// CASE WHEN warunek THEN instrukcja ELSE instrukcja_else END -- mamy IFy w SQL, nice

			mysqli_stmt_bind_param($stmt, 'is', $user_id, $date);
			mysqli_stmt_execute($stmt);
				$result = mysqli_stmt_get_result($stmt);
				if ($result) {
					while ($row = mysqli_fetch_array($result)) {
						$timeKey = substr($row['Czas_zajec'], 0, 5);
						if (!isset($classes[$timeKey])) {
							$classes[$timeKey] = [];
						}
						$classes[$timeKey][] = $row;
					}
				}
			mysqli_stmt_close($stmt);

			db_close($conn);

			$page_title = 'FitPol - Kalendarz zajęć';
			require './komponenty/naglowek.php';
		?>

		<section>
			<h1>Kalendarz zajęć</h1>
			<form method="GET" class="filtry" action="kalendarz.php">
				<label>
					Dzień:
					<input type="date" name="data" value="<?= html_convert($date); ?>">
				</label>
				<button class="btn" type="submit">Pokaż</button>
			</form>

			<?php if ($message): ?>
				<div class="alert success"><?= html_convert($message); ?></div>
			<?php endif; ?>
			<!-- zamiast < ?php if(arg){ ?> ... < ?php } ?> , lepsza składnia -->

			<?php if (!empty($errors)): ?>
				<div class="alert error">
					<ul><?php
						foreach ($errors as $error) {
							echo "<li>". html_convert($error) ."</li>";
						}
					?></ul>
				</div>
			<?php endif; ?>

			<table class="schedule">
				<thead>
					<tr>
						<th class="time-col">Godzina</th>
						<th><?= html_convert($date); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php for ($hour = 6; $hour <= 21; $hour++): ?>
						<tr>
							<td class="time-col">
								<?php $time = sprintf('%02d:00', $hour); echo html_convert($time); ?>
							</td>
							<td>
								<?php if (!empty($classes[$time])): ?>
									<?php foreach ($classes[$time] as $class): ?>
										<?php
											$zajete 	= $class['Zajete'];
											$limit 		= $class['Liczba_miejsc'];
											$wolne 		= $limit - $zajete;
											$czy_moje 	= $class['Czy_moje'] === 1;
										?>
										<div class="class-card">
											<?php
												echo "
													<strong>" .html_convert($class['Klasa']). "</strong> (" .html_convert($class['Kategoria']). ")<br>
													Trener: " .html_convert($class['Trener_imie']). " " .html_convert($class['Trener_nazwisko']). "<br>
													Klub: " .html_convert($class['Klub']). ", " .html_convert($class['Sala']). "<br>
													Miejsca: " .html_convert($zajete). " / " .html_convert($limit). "
													<div class='class-actions'>
												";
												// można się trochę pogubić, to prawda, ale to chyba najczytelniejsza wersja od wszystkich pozostałych (moim zdaniem)
												if ($czy_moje) 			echo "<span class='status'>Zapisano</span>";
												elseif ($wolne <= 0) 	echo "<span class='status'>Brak miejsc</span>";
												else echo "
													<form method='POST' action='kalendarz.php?data=" .html_convert($date). "'>
														<input type='hidden' name='akcja' value='rezerwuj'>
														<input type='hidden' name='zajecia_id' value='" .html_convert($class['Id']). "'>
														<button class='btn' type='submit'>Zapisz się</button>
													</form>
												";
												echo "</div>";
											?>
										</div>
									<?php endforeach; /* <3 */ ?>
								<?php else: ?>
									<span class="muted">Brak zajęć</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endfor; ?>
				</tbody>
			</table>
		</section>

		<?php require './komponenty/stopka.php'; ?>
