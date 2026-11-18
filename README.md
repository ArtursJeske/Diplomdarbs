# Noliktavas uzskaites sistēma

Diplomdarbs – tīmekļa lietotne noliktavas preču uzskaitei. Rakstīta PHP valodā ar MariaDB/MySQL datubāzi, bez ārējām bibliotēkām un ietvariem.

## Funkcionalitāte

- **Pieslēgšanās** – lietotāji ar lomām `admin` un `darbinieks`. Paroles glabājas tikai kā `password_hash()` rezultāts.
- **Sākumlapa** – noliktavas kopsavilkums un preces, kuru atlikums sasniedzis minimumu.
- **Preces** – saraksts ar meklēšanu un filtriem (kategorija, zems atlikums), pievienošana, labošana un dzēšana.
- **Kustības** – preču saņemšana no piegādātāja un izsniegšana klientam. Preces atlikums mainās tikai šeit, un katra izmaiņa tiek saglabāta vēsturē.
- **Kategorijas, piegādātāji, klienti** – saraksts, pievienošana, labošana un dzēšana.
- **Lietotāji** (tikai administratoram) – jauna lietotāja izveide, paroles maiņa un dzēšana.

Dzēst var tikai administrators. Ierakstus, kuriem ir kustību vēsture (preces, piegādātāji, klienti, lietotāji), dzēst nevar, lai vēsture saglabātos.

## Nepabeigtais

> **Izīrēšanas funkcija vēl nedarbojas.** Šobrīd sistēmā ir tikai preču saņemšana un izsniegšana. Preču izīrēšana (izsniegšana uz laiku ar atgriešanu) vēl nav izstrādāta.

## Projekta struktūra

```
config.php            Datubāzes pieslēguma dati un laika josla (jāmaina katrā datorā)
index.php             Sākumlapa
login.php, logout.php Pieslēgšanās un iziešana
products.php          Preču saraksts
product_edit.php      Preces pievienošana / labošana
product_delete.php    Preces dzēšana
movements.php         Saņemšana un izsniegšana
categories.php        Kategorijas
suppliers.php         Piegādātāji
clients.php           Klienti
users.php             Lietotāju pārvaldība
includes/             db.php, auth.php, functions.php, header.php, footer.php
assets/style.css      Stils
sql/schema.sql        Datubāzes struktūra (izveido datubāzi "noliktava")
sql/example_data.sql  Testa dati
```

## Prasības

- PHP 7.4 vai jaunāks ar `pdo_mysql` paplašinājumu
- MariaDB 10.2+ vai MySQL 8.0.16+ (vajadzīgs `CHECK` ierobežojumu atbalsts)
- Tīmekļa serveris (Apache)

## Testa lietotāji

Pēc `sql/example_data.sql` ielādes:

| Lietotājvārds | Parole    | Loma       |
|---------------|-----------|------------|
| admin         | admin123  | admin      |
| janis         | darbs123  | darbinieks |

## Palaišana uz Windows (XAMPP)

1. Ielikt projektu mapē `C:\xampp\htdocs\Diplomdarbs`.
2. XAMPP Control Panel palaist **Apache** un **MySQL**.
3. phpMyAdmin (`http://localhost/phpmyadmin`) sadaļā *Import* ielādēt vispirms `sql/schema.sql`, tad `sql/example_data.sql`.
4. `config.php` ierakstīt savu datubāzes lietotāju un paroli.
5. Atvērt `http://localhost/Diplomdarbs/login.php`.

## Palaišana uz Linux (Ubuntu)

Pārbaudīts tipiskai Ubuntu 22.04 / 24.04 instalācijai.

### 1. Uzinstalēt Apache, PHP un MariaDB

```bash
sudo apt update
sudo apt install apache2 mariadb-server php libapache2-mod-php php-mysql
sudo systemctl enable --now apache2 mariadb
```

### 2. Nokopēt projektu

```bash
sudo cp -r Diplomdarbs /var/www/html/
# vai: sudo git clone <repozitorija-adrese> /var/www/html/Diplomdarbs
```

### 3. Izveidot datubāzi un ielādēt datus

```bash
cd /var/www/html/Diplomdarbs
sudo mysql < sql/schema.sql
sudo mysql < sql/example_data.sql
```

### 4. Izveidot datubāzes lietotāju

Ubuntu MariaDB lietotājs `root` pēc noklusējuma pieslēdzas caur `unix_socket` (bez paroles, tikai ar `sudo`), tāpēc PHP ar to pieslēgties nevar. Jāizveido atsevišķs lietotājs:

```bash
sudo mysql
```

```sql
CREATE USER 'noliktava'@'localhost' IDENTIFIED BY 'izdomā_paroli';
GRANT ALL PRIVILEGES ON noliktava.* TO 'noliktava'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5. Nomainīt `config.php`

```bash
sudo nano /var/www/html/Diplomdarbs/config.php
```

```php
$db_host = 'localhost';
$db_name = 'noliktava';
$db_user = 'noliktava';
$db_pass = '';
```

### 6. Atvērt pārlūkā

```
http://localhost/Diplomdarbs/login.php
```

Ja serveris ir citā datorā, `localhost` vietā jāraksta servera IP adrese.

### Ja kaut kas nestrādā

- **"Neizdevās pieslēgties datubāzei"** – pārbaudīt `config.php` datus un vai MariaDB darbojas: `sudo systemctl status mariadb`.
- **Balta lapa vai kļūda 500** – skatīt Apache kļūdu žurnālu: `sudo tail -n 50 /var/log/apache2/error.log`.
- **PHP kods tiek parādīts kā teksts** – nav uzinstalēts `libapache2-mod-php`; pēc instalēšanas `sudo systemctl restart apache2`.
- **"could not find driver"** – nav uzinstalēts `php-mysql`; pēc instalēšanas `sudo systemctl restart apache2`.
