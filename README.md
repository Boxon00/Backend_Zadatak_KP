# Backend Developer Zadatak — Refaktorizacija

## Opis projekta

Ovaj projekat predstavlja refaktorisanu verziju originalnog PHP koda koji je bio napisan proceduralno, bez strukture i sa brojnim sigurnosnim propustima. Cilj refaktorizacije je bio da se kod prepiše korišćenjem **SOLID principa** i **Design Patterns-a**, bez korišćenja gotovog framework rješenja.

---

## Struktura projekta

```
registration/
├── bootstrap.php                       # Autoloader — registruje sve App\ klase
├── .gitignore                          # Ignorisani fajlovi za Git
├── README.md                           # Dokumentacija projekta
├── public/
│   └── register.php                    # HTTP entry point
├── src/
│   ├── Database/
│   │   ├── Connection.php              # mysqli wrapper (Singleton pattern)
│   │   ├── Expression.php              # Raw SQL expression (npr. NOW())
│   │   └── QueryBuilder.php            # Fluent query builder (Builder pattern)
│   ├── Fraud/
│   │   ├── MaxMindClientInterface.php  # Interface za fraud detekciju
│   │   └── MaxMindClient.php           # Simulirani MaxMind klijent
│   ├── Http/
│   │   ├── Request.php                 # Wrapper za HTTP request
│   │   └── JsonResponse.php            # JSON response helper
│   ├── Logger/
│   │   └── UserLogger.php              # Logovanje akcija korisnika
│   ├── Mail/
│   │   ├── MailerInterface.php         # Interface za slanje emaila
│   │   └── Mailer.php                  # Implementacija mail slanja
│   ├── Repository/
│   │   └── UserRepository.php          # Pristup bazi za User entitet (Repository pattern)
│   ├── Service/
│   │   └── RegistrationService.php     # Orkestracija registracije
│   └── Validation/
│       ├── RuleInterface.php           # Interface za validaciona pravila
│       ├── Validator.php               # Validator koji primjenjuje pravila (Strategy pattern)
│       └── Rules/
│           ├── RequiredRule.php        # Provjera obaveznog polja
│           ├── EmailFormatRule.php     # RFC 5321/5322 validacija emaila
│           ├── MinLengthRule.php       # Minimalna dužina stringa
│           ├── PasswordMatchRule.php   # Poklapanje lozinki
│           ├── UniqueEmailRule.php     # Email jedinstvenost u bazi
│           └── MaxMindRule.php         # MaxMind fraud detekcija
└── tests/
    └── ValidatorTest.php               # 30 unit testova (bez baze podataka)
```

---

## Primijenjeni SOLID principi

### S — Single Responsibility Principle
Svaka klasa ima samo jednu odgovornost:
- `Request.php` — samo čita HTTP podatke
- `JsonResponse.php` — samo šalje JSON odgovor
- `Validator.php` — samo koordinira validaciju
- `Connection.php` — samo upravlja konekcijom na bazu
- `QueryBuilder.php` — samo gradi SQL upite
- `UserRepository.php` — samo pristupa user tabeli
- `RegistrationService.php` — samo orkestrira registraciju
- `Mailer.php` — samo šalje emailove
- `UserLogger.php` — samo loguje akcije

### O — Open/Closed Principle
Validator sistem je otvoren za proširenje, zatvoren za izmjenu. Nova validaciona provjera se dodaje kreiranjem nove klase koja implementira `RuleInterface` — bez ikakve izmjene postojećeg koda.

### L — Liskov Substitution Principle
`MaxMindClient` i fake klijent korišten u testovima su potpuno zamjenjivi jer oba implementiraju `MaxMindClientInterface`.

### I — Interface Segregation Principle
Svi interfejsi su mali i fokusirani sa samo jednim metodom:
- `RuleInterface` — jedan metod: `validate()`
- `MaxMindClientInterface` — jedan metod: `isFraudulent()`
- `MailerInterface` — jedan metod: `send()`

### D — Dependency Inversion Principle
Sve zavisnosti se injektuju kroz konstruktor. Visoko-nivoske klase zavise od apstrakcija, ne od konkretnih implementacija:
- `RegistrationService` prima `MailerInterface`, ne konkretni `Mailer`
- `MaxMindRule` prima `MaxMindClientInterface`, ne konkretni `MaxMindClient`

---

## Korišteni Design Patterns

| Pattern | Gdje se koristi |
|---|---|
| **Strategy** | `RuleInterface` + sve klase u `Rules/` folderu |
| **Repository** | `UserRepository.php` |
| **Builder** | `QueryBuilder.php` |
| **Singleton** | `Connection.php` |
| **Dependency Injection** | Sve klase sa konstruktorom |

---

## Ispravljeni bugovi iz originalnog koda

| Bug | Originalni kod | Ispravljeno |
|---|---|---|
| Typo | `preg_meatch()` | `preg_match()` |
| SQL Injection | `"WHERE email = '$email'"` | `QueryBuilder` escapuje sve vrijednosti |
| Plain text lozinka | `password = '$password'` | `password_hash()` sa bcrypt |
| Copy/paste greška | `mb_strlen($password)` za password2 | `mb_strlen($password2)` |
| Pogrešna error poruka | `password_mismatch` za duplikat emaila | `email_taken` |
| Nema strukture | Sve u jednom fajlu | 25 fajlova, OOP, SOLID |

---

## Dodatni zahtjevi

### Email jedinstvenost u sistemu
Implementirano kroz `UniqueEmailRule.php` koji koristi `UserRepository::emailExists()` da provjeri da li email već postoji u bazi.

### MaxMind fraud detekcija (simulacija)
Implementirano kroz:
- `MaxMindClientInterface.php` — interfejs
- `MaxMindClient.php` — simulirana logika

Simulaciona logika:
- Emailovi koji sadrže "fraud", "spam", "fake" → blokirani (+60 poena)
- IP adrese iz opsega `192.0.2.x` → blokirane (+60 poena)
- Sumnjivi TLD-ovi (.xyz, .top, .click, .loan) → +30 poena
- Prag za blokadu: risk score >= 50

### SQL izrazi (Expression klasa)
Implementirano kroz `Expression.php` koja omogućava prosleđivanje sirovih SQL izraza:

```php
// INSERT sa NOW()
->insert([
    'posted' => new Expression('NOW()'),
]);

// WHERE sa INTERVAL
->where('posted', '>', new Expression('NOW() - INTERVAL 10 DAY'))
```

---

## Pokretanje projekta

### Zahtjevi
- PHP 8.1+
- MySQL / MariaDB

### Kreiranje baze podataka

```sql
CREATE DATABASE my_db;

USE my_db;

CREATE TABLE user (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    email    VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    posted   DATETIME DEFAULT NULL
);

CREATE TABLE user_log (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    action   VARCHAR(50) NOT NULL,
    user_id  INT NOT NULL,
    log_time DATETIME DEFAULT NULL
);
```

### Pokretanje servera

```bash
cd registration
php -S localhost:8000 -t public
```

### Pokretanje unit testova

```bash
php tests/ValidatorTest.php
```

Očekivani rezultat:
```
Results: 30 passed, 0 failed
```

---

## API

### POST /register.php

**Parametri:**

| Parametar | Tip | Opis |
|---|---|---|
| email | string | Email adresa korisnika |
| password | string | Lozinka (minimum 8 karaktera) |
| password2 | string | Potvrda lozinke |

**Uspješan odgovor:**
```json
{
    "success": true,
    "userId": 1
}
```

**Error odgovori:**

| Error | Opis |
|---|---|
| `required` | Email je prazan |
| `email_format` | Email nije validan |
| `email_taken` | Email već postoji u sistemu |
| `fraud_detected` | MaxMind je detektovao prevaru |
| `min_length` | Lozinka je kraća od 8 karaktera |
| `password_mismatch` | Lozinke se ne poklapaju |
| `DB_error` | Greška pri konekciji na bazu |
| `registration_failed` | Greška pri registraciji |

---

## Testovi

Projekat sadrži 30 unit testova koji pokrivaju sve validacione slučajeve:

- `RequiredRule` — 3 testa
- `EmailFormatRule` — 5 testova
- `MinLengthRule` — 4 testa
- `PasswordMatchRule` — 3 testa
- `MaxMindRule` — 2 testa
- `MaxMindClient simulacija` — 4 testa
- `Validator integracija` — 7 testova
- `Expression` — 2 testa

Svi testovi rade bez konekcije na bazu podataka.