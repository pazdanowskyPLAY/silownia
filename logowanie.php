		<?php
			require './komponenty/baza_danych.php';

			if (isset($_GET['akcja']) && $_GET['akcja'] === 'wyloguj') {
				session_unset(); session_destroy();
				header('Location: index.php');
				exit;
			}

			$errors = [];
			$info = null;

			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				$conn 	= db_connect();
				$akcja 	= $_POST['akcja'] ?? '';

				if ($akcja === 'logowanie') {
					$email = trim($_POST['email'] ?? '');
					$haslo = $_POST['haslo'] ?? '';

					if ($email === '' || $haslo === '') $errors[] = 'Uzupełnij email i haslo.';
					else {
						$stmt = mysqli_prepare($conn, 'SELECT Id, Imie, Nazwisko, Haslo_hash FROM s_uzytkownicy WHERE Email = ?;');
						mysqli_stmt_bind_param($stmt, 's', $email);
						mysqli_stmt_execute($stmt);
						$result = mysqli_stmt_get_result($stmt);
						$row = $result ? mysqli_fetch_array($result) : null;
						mysqli_stmt_close($stmt);

						// czy hash wpisanego hasła zgadza się z tym hashem z bazy danych?
						if ($row && password_verify($haslo, $row['Haslo_hash'])) {
							$_SESSION['user_id'] = $row['Id'];
							$_SESSION['user_name'] = $row['Imie']. " " .$row['Nazwisko'];
							db_close($conn);
							header('Location: kalendarz.php');
							exit;
						}

						$errors[] = 'Nieprawidłowe dane logowania.';
					}
				}

				if ($akcja === 'rejestracja') {
					$imie 		= trim($_POST['imie'] ?? '');
					$nazwisko 	= trim($_POST['nazwisko'] ?? '');
					$rok 		= $_POST['rok_urodzenia'] ?? 0;
					$email 		= trim($_POST['email'] ?? '');
					$telefon 	= trim($_POST['telefon'] ?? '');
					$haslo 		= $_POST['haslo'] ?? '';
					$haslo2 	= $_POST['haslo2'] ?? '';

					if ($imie === '' || $nazwisko === '' || $email === '' || $haslo === '' || $haslo2 === '') 	$errors[] = 'Uzupełnij wszystkie wymagane pola.';
					if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 											$errors[] = 'Podaj poprawny email.';
					if ($rok < 1900 || $rok > date('Y')) 														$errors[] = 'Podaj poprawny rok urodzenia.';
					if ($haslo !== $haslo2) 																	$errors[] = 'Hasła nie są takie same.';
					if (strlen($haslo) < 6) 																	$errors[] = 'Hasło musi mieć minimum 6 znaków.';

					if (empty($errors)) {
						$stmt = mysqli_prepare($conn, 'SELECT Id FROM s_uzytkownicy WHERE Email = ?;');
						mysqli_stmt_bind_param($stmt, 's', $email);
						mysqli_stmt_execute($stmt);
						$result = mysqli_stmt_get_result($stmt);
						$exists = $result ? mysqli_fetch_array($result) : null;
						mysqli_stmt_close($stmt);

						if ($exists) $errors[] = 'Konto z takim emailem już istnieje.';
						else {
							$hash = password_hash($haslo, PASSWORD_DEFAULT);
							$telefonValue = $telefon !== '' ? $telefon : null;
							$stmt = mysqli_prepare($conn, 'INSERT INTO s_uzytkownicy (Imie, Nazwisko, Rok_urodzenia, Email, Telefon, Haslo_hash) VALUES (?, ?, ?, ?, ?, ?);');
							mysqli_stmt_bind_param($stmt, 'ssisss', $imie, $nazwisko, $rok, $email, $telefonValue, $hash);
							if (mysqli_stmt_execute($stmt)) {
								$_SESSION['user_id'] = mysqli_insert_id($conn);
								$_SESSION['user_name'] = $imie . ' ' . $nazwisko;
								mysqli_stmt_close($stmt);
								db_close($conn);
								header('Location: kalendarz.php');
								exit;
							}
							mysqli_stmt_close($stmt);
							$errors[] = 'Nie udało się utworzyć konta.';
						}
					}
				}

				db_close($conn);
			}

			if (is_logged_in()) {
				$info = 'Jesteś już zalogowany.';
			}

			$page_title = 'FitPol - Formularz logowania';
			require './komponenty/naglowek.php';
		?>

		<section>
			<?php if ($info): ?>
				<div class="alert notice"><?= html_convert($info); ?></div>
				<p><a class="btn" href="kalendarz.php">Przejdź do kalendarza</a></p>
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
					<h2>Zaloguj się do serwisu</h2>
					<form method="post" action="logowanie.php">
						<input type="hidden" name="akcja" value="logowanie">
						<label>Email
							<input type="email" name="email" required></label>
						<label>Hasło
							<input type="password" name="haslo" required></label>

						<button class="btn" type="submit">Zaloguj</button>
					</form>
				</div>

				<div class="card">
					<h2>Rejestracja do serwisu</h2>
					<form method="post" action="logowanie.php">
						<input type="hidden" name="akcja" value="rejestracja">
						<label>Imię
							<input type="text" name="imie" required></label>
						<label>Nazwisko
							<input type="text" name="nazwisko" required></label>
						<label>Rok urodzenia
							<input type="number" name="rok_urodzenia" min="1900" max="<?= html_convert(date('Y')); ?>" required></label>
						<label>Email
							<input type="email" name="email" required></label>
						<label>Telefon (opcjonalnie)
							<input type="text" name="telefon"></label>
						<label>Hasło
							<input type="password" name="haslo" required></label>
						<label>Powtórz hasło
							<input type="password" name="haslo2" required></label>

						<button class="btn" type="submit">Utwórz konto</button>
					</form>
				</div>
			</div>
		</section>

		<?php require './komponenty/stopka.php'; ?>
