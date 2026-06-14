CREATE DATABASE IF NOT EXISTS silownia;
USE silownia;

DROP TABLE IF EXISTS s_rezerwacje;
DROP TABLE IF EXISTS s_zajecia;
DROP TABLE IF EXISTS s_klasy;
DROP TABLE IF EXISTS s_trenerzy;
DROP TABLE IF EXISTS s_kategorie;
DROP TABLE IF EXISTS s_sale;
DROP TABLE IF EXISTS s_kluby;
DROP TABLE IF EXISTS s_uzytkownicy;





-- TWORZENIE TABEL

CREATE TABLE s_uzytkownicy (
    Id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Imie VARCHAR(128) NOT NULL,
    Nazwisko VARCHAR(128) NOT NULL,
    Rok_urodzenia SMALLINT UNSIGNED NOT NULL,
    Email VARCHAR(128) NOT NULL,
    Telefon VARCHAR(32),
    Haslo_hash VARCHAR(255) NOT NULL,
    Data_rejestracji DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY UQ_Uzytkownicy_Email (Email)
);
-- żadnego powtarzania się emaili albo bęcki za blokami

CREATE TABLE s_kluby (
    Id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Nazwa VARCHAR(128) NOT NULL,
    Miasto VARCHAR(128) NOT NULL,
    Adres VARCHAR(128) NOT NULL
);

CREATE TABLE s_sale (
    Id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Nazwa VARCHAR(128) NOT NULL
);

CREATE TABLE s_kategorie (
    Id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Nazwa VARCHAR(128) NOT NULL
);

CREATE TABLE s_klasy (
    Id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Kategorie_id INT UNSIGNED NOT NULL,
    Nazwa VARCHAR(128) NOT NULL,

    CONSTRAINT FK_Klasy_Kategorie_id FOREIGN KEY (Kategorie_id) REFERENCES s_kategorie (Id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE s_trenerzy (
    Id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Imie VARCHAR(128) NOT NULL,
    Nazwisko VARCHAR(128) NOT NULL
);

CREATE TABLE s_zajecia (
    Id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Kluby_id INT UNSIGNED NOT NULL,
    Sale_id INT UNSIGNED NOT NULL,
    Kategorie_id INT UNSIGNED NOT NULL,
    Klasy_id INT UNSIGNED NOT NULL,
    Trenerzy_id INT UNSIGNED NOT NULL,
    Data_zajec DATE NOT NULL,
    Czas_zajec TIME NOT NULL,
    Czas_trwania SMALLINT UNSIGNED NOT NULL DEFAULT 45,
    Ocena DECIMAL(2,1) NOT NULL DEFAULT 5.0,
    Liczba_miejsc SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    Rezerwacja SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    CONSTRAINT FK_Zajecia_Kluby_id FOREIGN KEY (Kluby_id) REFERENCES s_kluby (Id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_Zajecia_Sale_id FOREIGN KEY (Sale_id) REFERENCES s_sale (Id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_Zajecia_Kategorie_id FOREIGN KEY (Kategorie_id) REFERENCES s_kategorie (Id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_Zajecia_Klasy_id FOREIGN KEY (Klasy_id) REFERENCES s_klasy (Id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_Zajecia_Trenerzy_id FOREIGN KEY (Trenerzy_id) REFERENCES s_trenerzy (Id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE s_rezerwacje (
    Id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Zajecia_id INT UNSIGNED NOT NULL,
    Uzytkownicy_id INT UNSIGNED NOT NULL,

    UNIQUE KEY UQ_Rezerwacje (Zajecia_id, Uzytkownicy_id),
    CONSTRAINT FK_Rezerwacje_Zajecia_id FOREIGN KEY (Zajecia_id) REFERENCES s_zajecia (Id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT FK_Rezerwacje_Uzytkownicy_id FOREIGN KEY (Uzytkownicy_id) REFERENCES s_uzytkownicy (Id) ON UPDATE CASCADE ON DELETE RESTRICT
);
-- rezerwacja jedna jedyna w swym rodzaju





-- WARTOŚCI

INSERT INTO s_uzytkownicy (Imie, Nazwisko, Rok_urodzenia, Email, Telefon, Haslo_hash) VALUES
('Jan', 'Kowalski', 1990, 'jan.kowalski@mail.pl', '+48500600701', '$2y$10$6DlTxUgIXkyk6V.01M6Yc.RmgqPdDStZtn6i7u09NbtUxdbCcwAoK'); -- qwerty123

INSERT INTO s_kluby (Nazwa, Miasto, Adres) VALUES
('FitPol Centrum Pawłowska', 'Warszawa', 'ul. Pawłowska 1'),
('FitPol Rondo Mogilskie', 'Kraków', 'ul. Nowa 12'),
('FitPol Portowe', 'Gdańsk', 'ul. Portowa 5');

INSERT INTO s_sale (Nazwa) VALUES
('Sala A'), ('Sala B'), ('Studio Cycling');

INSERT INTO s_kategorie (Nazwa) VALUES
('Siłowe'), ('Fitness'), ('Relaks');

INSERT INTO s_klasy (Kategorie_id, Nazwa) VALUES
(1, 'Trening siłowy'),
(2, 'HIIT'),
(2, 'Zumba'),
(3, 'Joga'),
(2, 'Cycling'),
(3, 'Stretching');

INSERT INTO s_trenerzy (Imie, Nazwisko) VALUES
('Kazimierz', 'Rydz'),
('Agnieszka', 'Nowacka'),
('Piotr', 'Zieliński'),
('Jan Maria', 'Lewandowski');

INSERT INTO s_zajecia (Kluby_id, Sale_id, Kategorie_id, Klasy_id, Trenerzy_id, Data_zajec, Czas_zajec, Czas_trwania, Ocena, Liczba_miejsc) VALUES
(1, 1, 1, 1, 1, CURDATE(), '08:00:00', 60, 4.8, 20),
(1, 2, 2, 2, 2, CURDATE(), '12:00:00', 55, 4.4, 25),
(2, 1, 2, 3, 3, CURDATE(), '16:00:00', 55, 4.7, 30),
(3, 3, 2, 5, 4, CURDATE(), '20:00:00', 45, 4.9, 18),
(1, 1, 3, 4, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', 40, 4.8, 20),
(1, 1, 3, 4, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '16:00:00', 30, 4.9, 10),
(2, 2, 3, 6, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '20:00:00', 50, 4.5, 25),
(3, 2, 2, 2, 4, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:00:00', 55, 4.6, 15),
(3, 1, 1, 1, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '18:00:00', 60, 4.4, 10),
(2, 3, 2, 5, 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '13:00:00', 45, 5.0, 13),
(1, 2, 3, 6, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '19:00:00', 50, 4.9, 40),
(2, 1, 1, 1, 4, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '09:00:00', 30, 4.6, 10),
(3, 3, 2, 5, 1, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '17:00:00', 45, 4.7, 27),
(1, 1, 3, 4, 2, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '09:00:00', 60, 4.8, 21),
(1, 1, 3, 4, 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '14:00:00', 40, 4.5, 13),
(2, 2, 3, 6, 3, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '20:00:00', 50, 4.5, 24),
(3, 1, 1, 1, 1, DATE_ADD(CURDATE(), INTERVAL 6 DAY), '07:00:00', 60, 4.7, 15),
(3, 2, 2, 2, 4, DATE_ADD(CURDATE(), INTERVAL 6 DAY), '19:00:00', 55, 4.6, 20),
(2, 3, 2, 5, 2, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '15:00:00', 30, 4.8, 30),
(1, 2, 3, 6, 3, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '16:00:00', 50, 4.9, 20);

-- Zajęcia na cały tydzień!