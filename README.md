# Quantum Random API & Dashboard

> Malý experimentální projekt, který poskytuje kvalitní kryptografickou náhodu přes jednoduché HTTP API.  
> Náhodnost je „kvantově inspirovaná“ a backend je navržený tak, aby šel v budoucnu přepojit na reálný kvantový hardware.

---

## Obsah

- [Cíle projektu](#cíle-projektu)
- [Architektura](#architektura)
  - [API (`api/`)](#api-api)
  - [Dashboard (`dashboard/`)](#dashboard-dashboard)
  - [DnD Dice stránka (`dndphp`)](#dnd-dice-stránka-dndphp)
  - [Přihlášení a role uživatelů](#přihlášení-a-role-uživatelů)
  - [Tombola (`tombolaphp`)](#tombola-tombolaphp)
- [Jak funguje API](#jak-funguje-api)
  - [Struktura requestu](#struktura-requestu)
  - [Příklady použití](#příklady-použití)
- [Lokální vývoj](#lokální-vývoj)
  - [API – FastAPI / Uvicorn](#api--fastapi--uvicorn)
  - [Dashboard – PHP](#dashboard--php)
  - [Obnovení Python prostředí na jiném stroji](#obnovení-python-prostředí-na-jiném-stroji)
- [Provoz / Nginx / systemd](#provoz--nginx--systemd)
  - [Nginx](#nginx)
  - [Systemd služba `quantum-api.service`](#systemd-služba-quantum-apiservice)
- [Bezpečnost, logování a TODO](#bezpečnost-logování-a-todo)

---

## Cíle projektu

Quantum Random má jednu jednoduchou ambici:

- poskytovat **kryptograficky bezpečnou náhodu** přes **HTTP JSON API**,
- mít **čisté, stabilní API**, které neřeší, odkud náhodu bereme,
- umožnit **snadné přepojení backendu** z emulace na reálný kvantový hardware,
- nabídnout **pohodlné webové UI** pro běžné scénáře: loterie, hesla, DnD kostky, tombola atd.

Náhodnost je generovaná kombinací:

- moderního **CSPRNG** a entropie OS,
- **kvantově inspirovaného algoritmu / emulace qubitů** pro generování bitových sekvencí.

Z hlediska aplikací (bezpečnost, statistika, generování hesel, loterie, hry) je tato náhodnost
ekvivalentní reálným kvantovým RNG – rozdíl je v tom, že místo fyzického qubitu se simuluje jeho chování.

---

## Architektura

Repozitář je rozdělený na dvě hlavní části:

- `api/` – Python / FastAPI služba (např. `https://quantum.api.ventureout.cz`)
- `dashboard/` – PHP dashboard (např. `https://dashboard.api.ventureout.cz`), který API jen „obaluje“ do webového UI

### API (`api/`)

**Technologie**

- Jazyk: **Python 3.x**
- Framework: **FastAPI**
- Server: **uvicorn** (za Nginx reverse proxy)

**Struktura (zjednodušeně)**

- `main.py` – FastAPI aplikace, endpointy:
  - `POST /random` – hlavní endpoint pro generování náhodných hodnot
  - `GET /health` – healthcheck
  - `GET /version` – verze API / build info
- `request_parser.py` – parsování, validace a transformace požadavku
- `quantum_random.py` – generování náhodných dat
- `requirements.txt` – Python závislosti
- `todo.txt` – poznámky k logování, rate-limitům, API klíčům apod.

**Backend pro náhodu**

Aktuálně:

- využívá entropii OS / kryptografických knihoven (CSPRNG),
- nad tím staví kvantovou emulaci (simulace qubitů, generování bitů),
- rozhraní je navržené tak, aby se dal backend vyměnit za:
  - IBM Quantum, IonQ, Quantinuum, …
  - případně mix více zdrojů (hardware + fallback emulace).

Cíl: **API rozhraní se nemění**, měnit se může pouze interní „zdroj náhody“.

---

### Dashboard (`dashboard/`)

Dashboard je tenký **PHP layer** nad API. Sám žádná náhodná data negeneruje – vše jde přes `POST /random`.

**Technologie**

- Nginx + **PHP-FPM 8.3**
- Jednoduchý responzivní frontend (HTML/CSS/JS)

**Struktura (hlavní soubory)**

- `web/index.php` – úvodní stránka, dokumentace a vysvětlení API
- `web/dashboard.php` – UI pro generování náhodných hodnot (Sportka, Eurojackpot, Dice, Password…)
- `web/dnd.php` – **DnD Dice** stránka (viz níže)
- `web/tombola.php` – **Tombola** (losování cen pomocí kvantové náhody, viz níže)
- `web/login.php`, `web/logout.php` – přihlášení / odhlášení
- `web/admin_users.php` – administrace uživatelů
- `web/includes/header.php`, `web/includes/footer.php` – společné menu / layout
- `web/includes/auth.php` – práce se session, `current_user()`, `is_admin()`, helpery pro restrikci přístupu
- `web/includes/tombola_lib.php` – logika kolem tomboly (DB operace, helpery)
- `web/assets/css/style.css` – vzhled (cards, layout, responzivita, navbar)
- `web/assets/js/app.js` – logika pro hlavní dashboard:
  - presety (Sportka, Eurojackpot, Dice, Password),
  - stav UI,
  - skládání JSON payloadu pro `/random`,
  - zobrazení JSON requestu/response + ukázka cURL.
- `web/assets/js/dnd.js` – JS logika pro DnD kostky (DnD Dice, viz níže)
- `web/assets/js/tombola.js` – JS helpery pro UI tomboly (pokud/ až budou potřeba)

---

### DnD Dice stránka (`dnd.php`)

Soubor: `dashboard/web/dnd.php`  
URL (typicky): `https://dashboard.api.ventureout.cz/dnd.php`

Stránka DnD Dice umožňuje:

- házet **d4, d6, d8, d10, d12, d20, d100** pomocí kvantové náhody,
- zvolit typ hodu (attack, saving throw, skill check, damage, custom),
- nastavit **počet kostek**, **režim** (Normal / Advantage / Disadvantage) a **modifikátor**,
- přehledně zobrazit:
  - jednotlivé kostky,
  - celkový součet,
  - použitý modifikátor,
- zobrazit **debug JSON request/response**,
- přepínat **nápovědu v češtině a angličtině**.

#### UI – hlavní prvky

1. **Typ hodu (`rollType`)**

   - `Attack roll` – útok  
   - `Saving throw` – záchranný hod  
   - `Skill / ability check`  
   - `Damage roll`  
   - `Custom` – ruční nastavení

   Typ hodu pouze **předvyplní** výchozí kostku a počet. Uživatel může vždy vše ručně přepsat.

2. **Kostka a počet kostek**

   - `diceType` – výběr kostky: d4, d6, d8, d10, d12, d20, d100  
   - `diceCount` – počet kostek (1–20)

3. **Režim (advantage/disadvantage)**

   - `Normal` – klasický hod  
   - `Advantage` – hod 2×, vezmi vyšší  
   - `Disadvantage` – hod 2×, vezmi nižší  

   Nejčastěji pro d20 útoky a záchranné hody, technicky však funguje i pro jiné kostky.

4. **Modifikátor (`modifier`)**

   - celé číslo (kladné i záporné),
   - např. `+5` k útoku, `+3` ke záchrannému hodu, `-1` postih.

5. **Výstup**

   - shrnutí posledního hodu (včetně typu),
   - jednotlivé kostky v přehledné řadě,
   - celkový součet včetně modifikátoru,
   - možnost otevřít **debug** sekci a zobrazit surový JSON request/response z API.

#### JSON požadavky pro DnD

Stránka `dnd.php` používá JavaScript (`assets/js/dnd.js`), který skládá payload pro `/random`.

Obecně:

- `type` je vždy `"int"` (kostka → celé číslo),
- `range` je `[1, N]`, kde `N` je velikost kostky (4, 6, 8, 10, 12, 20, 100),
- `count` odpovídá celkovému počtu hodů:
  - normální hod: `count = diceCount`,
  - advantage/disadvantage: typicky 2× d20 pro daný typ hodu (detail řeší `dnd.js`).

Příklad jednoduchého hodu 1× d20:

```json
{
  "request": [
    {
      "random": {
        "type": "int",
        "count": 1,
        "unique": false,
        "range": [1, 20],
        "alphabet": null
      }
    }
  ]
}
Debug sekce na stránce zobrazí jak tento request, tak i response z API.

Přihlášení a role uživatelů
Dashboard má jednoduchou autentizaci, aby:

Tombolu a další „ostré“ části mohl obsluhovat jen přihlášený uživatel,

bylo možné oddělit běžné uživatele a administrátory.

Databáze
Tabulka users:

id – primární klíč

username – unikátní login

password_hash – hash hesla (password_hash() / password_verify() v PHP)

role – user nebo admin

created_at – čas založení

PHP vrstva
Hlavní soubory:

dashboard/web/includes/auth.php – práce se session:

current_user() – vrací aktuálního uživatele (nebo null),

is_admin() – zjištění role,

helpery pro přesměrování nepřihlášených uživatelů.

dashboard/web/login.php – přihlášení:

formulář (uživatelské jméno + heslo),

ověření přes password_verify(),

po úspěchu se do $_SESSION ukládá user_id, username, role.

dashboard/web/logout.php – odhlášení (zrušení session).

dashboard/web/admin_users.php – jednoduché UI pro správu uživatelů:

dostupné jen pro roli admin,

umožňuje zakládat nové loginy pro tombolu (role user nebo další admin).

Využití v UI
V header.php se podle přihlášení dynamicky zobrazují:

položka Tombola a případně Admin,

jméno přihlášeného uživatele + odkaz Logout.

Nepřihlášený uživatel:

vidí veřejné části (Home, Generator, DnD, About, API Docs),

při pokusu o přístup na tombola.php je přesměrován na login / dashboard.

Admin má navíc přístup na admin_users.php a může vytvářet nové účty pro obsluhu tomboly.

Tombola (tombola.php)
Soubor: dashboard/web/tombola.php
URL (typicky): https://dashboard.api.ventureout.cz/tombola.php

Tombola je speciální část dashboardu, která používá Quantum RNG API k férovému losování vstupenek pro firemní akce apod.

Databázový model
Používají se tři tabulky:

tombola_events

id, name, ticket_from, ticket_to

user_id – vlastník akce (uživatel, který tombolu založil)

public_code – náhodný kód pro veřejný náhled výsledků

created_at – čas založení

tombola_prizes

id, event_id

name – název ceny (nebo generované „Cena 1“, „Cena 2“, …)

quantity_total – počet kusů

sort_order – pořadí losování

created_at

tombola_draws

id, event_id, prize_id, ticket_number

status – valid nebo no_show (popř. další stavy podle potřeby)

created_at – čas losování

Všechny tabulky mají cizí klíče mezi sebou (event_id, prize_id).

Funkce v UI
Stránka tombola.php má tři hlavní části:

Správa tomboly (levý sloupec)

Vytvoření nové akce:

název akce (např. „Vánoční večírek 2025“),

rozsah lístků (ticket_from / ticket_to),

definice cen:

buď jen počet očíslovaných cen „Cena 1 … Cena N“,

nebo seznam názvů z textového pole (Název | počet).

Vytvoření akce:

uloží záznam do tombola_events včetně user_id přihlášeného uživatele,

vygeneruje náhodný public_code,

založí záznamy v tombola_prizes.

Seznam existujících akcí daného uživatele (select box).

Losování (pravý sloupec)

výběr aktuální ceny (prize) z dropdownu,

zobrazení:

názvu akce a rozsahu lístků,

aktuální ceny (název, počet zbývajících kusů),

tlačítko „Losovat“:

zavolá Quantum API (POST /random) pro vygenerování náhodného lístku v dosud nevytaženém rozsahu,

zkontroluje, že číslo ještě nebylo použito pro danou cenu / akci,

uloží výsledek do tombola_draws jako status = 'valid'.

možnost označit výhru jako no_show a přelosovat:

původní záznam se přepne na no_show,

vytvoří se nový záznam s novým lístkem a status = 'valid'.

Přehled losování vybrané akce (spodní část)

tabulka všech losů (tombola_draws) pro aktuálně vybranou akci:

čas losování,

číslo lístku,

název ceny,

stav (valid / no_show),

akce (tlačítko „Přelosovat“).

data se berou přes join na tombola_events a tombola_prizes.

Veřejný náhled výsledků
Každá akce má public_code, který se použije v URL:

https://dashboard.api.ventureout.cz/tombola_tazene.php?code=<public_code>
Soubor: dashboard/web/tombola_tazene.php

je veřejný (není potřeba login),

z databáze načte akci podle public_code,

zobrazí tabulku platných výher (status = 'valid') se sloupci:

čas losování,

číslo lístku,

název ceny,

nahoře zobrazuje čas poslední aktualizace a interval auto-refresh:

Aktualizace: 04.12.2025 08:23:09 (auto refresh každých 30 s)
má jednoduchý JS:

setInterval(function () {
    window.location.reload();
}, 30000); // 30 s
QR kód
V detailu akce na tombola.php je k dispozici i QR kód na veřejný odkaz:

URL se generuje v PHP z $_SERVER['HTTPS'], $_SERVER['HTTP_HOST'] a public_code,

QR se načítá až po kliknutí na tlačítko (např. „Zobrazit QR kód“),

používá se jednoduché externí API (např. https://api.qrserver.com/v1/create-qr-code/?...), aby se QR nenačítal při každém reloadu celé stránky.

Jak funguje API
Struktura requestu
Endpoint:

POST https://quantum.api.ventureout.cz/random
Content-Type: application/json
Payload:

{
  "request": [
    {
      "random": {
        "type": "int",
        "count": 5,
        "unique": true,
        "range": [1, 50],
        "alphabet": null
      }
    }
  ]
}
Parametry:

type

"int" – generování celých čísel,

"char" – generování znaků z definované množiny.

count – kolik hodnot vygenerovat.

unique

u int – zda mají být hodnoty v rámci jednoho tasku unikátní (např. pro loterie),

u char většinou false (hesla, tokeny).

range

[min, max] pro type: "int",

pro type: "char" null.

alphabet

pro type: "char" – množina znaků,

pro type: "int" null.

Odpověď:

{
  "result": [
    [12, 5, 37, 48, 9]
  ]
}
result je pole výsledků pro jednotlivé tasky uvnitř requestu – každý task vrací jedno vnořené pole.

Příklady použití
1) Sportka (6/49)

{
  "request": [
    {
      "random": {
        "type": "int",
        "count": 6,
        "unique": true,
        "range": [1, 49],
        "alphabet": null
      }
    }
  ]
}
2) Eurojackpot (5/50 + 2/12)
Dva tasky v jednom requestu:


{
  "request": [
    {
      "random": {
        "type": "int",
        "count": 5,
        "unique": true,
        "range": [1, 50],
        "alphabet": null
      }
    },
    {
      "random": {
        "type": "int",
        "count": 2,
        "unique": true,
        "range": [1, 12],
        "alphabet": null
      }
    }
  ]
}
Odpověď:

{
  "result": [
    [12, 5, 37, 48, 9],
    [3, 11]
  ]
}
3) Heslo (16 znaků)

{
  "request": [
    {
      "random": {
        "type": "char",
        "count": 16,
        "unique": false,
        "range": null,
        "alphabet": "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!?@#$%*+-"
      }
    }
  ]
}
JS v dashboardu navíc kontroluje, zda heslo obsahuje:

alespoň 1 malé písmeno,

alespoň 1 velké písmeno,

alespoň 1 číslici,

alespoň 1 speciální znak z množiny !?@#$%*+-.

Pokud ne, náhodně přepíše některé pozice tak, aby podmínky byly splněny.

Lokální vývoj
Pozn.: Konkrétní cesty a verze se mohou lišit podle prostředí.

API – FastAPI / Uvicorn

cd api
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
Spuštění:

uvicorn app.main:app --reload --port 8000
API poběží na http://127.0.0.1:8000.

Dashboard – PHP

cd dashboard/web
php -S 127.0.0.1:8080
Dashboard poběží na http://127.0.0.1:8080/.

URL API lze v konfiguraci přepnout dle potřeby:

produkce: https://quantum.api.ventureout.cz,

lokálně: http://127.0.0.1:8000.

Obnovení Python prostředí na jiném stroji
Příklad pro produkční instalaci v /opt/quantum/api:

cd /opt/quantum/api
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
deactivate
Provoz / Nginx / systemd
Nginx
V repozitáři jsou verziované relevantní konfigurace pro přehled:

etc/nginx/sites-enabled/dashboard.api.ventureout.cz

etc/nginx/sites-enabled/quantum.api.ventureout.cz

Typické nastavení:

Dashboard

statický obsah + PHP z /opt/quantum/dashboard/web

index.php, dashboard.php, dnd.php, tombola.php atd.

API

reverse proxy na 127.0.0.1:8000 (uvicorn / FastAPI)

Certifikáty spravuje Let’s Encrypt (Certbot).

Systemd služba quantum-api.service
Příklad jednotky:

# /etc/systemd/system/quantum-api.service
[Unit]
Description=Quantum Random API
After=network.target

[Service]
User=root
WorkingDirectory=/opt/quantum-api/app
ExecStart=/opt/quantum-api/venv/bin/uvicorn main:app --host 0.0.0.0 --port 8000
Restart=always
RestartSec=5
Environment=PYTHONUNBUFFERED=1

[Install]
WantedBy=multi-user.target
Aktivace služby:

Načtení nové jednotky:

systemctl daemon-reload
Spuštění služby:

systemctl start quantum-api.service
Zapnutí po rebootu:

systemctl enable quantum-api.service
Kontrola stavu:

systemctl status quantum-api.service
Bezpečnost, logování a TODO
Stav implementace (shrnutí):

Hotovo
✅ API endpoint POST /random + základní struktura request/response
✅ Oddělení logiky:

parsování (request_parser.py),

generování dat (quantum_random.py)

✅ Dashboard s presety:

Sportka, Eurojackpot, Dice, Password

✅ Hesla:

vynucená kombinace malá/velká/číslice/speciál

✅ Responzivní layout (desktop + mobil)

✅ Stránka DnD Dice (dnd.php):

konfigurace hodu (typ, kostka, počet, režim, modifikátor),

přehledné zobrazení výsledků,

debug JSON request/response,

nápověda v CZ/EN.

✅ Základní autentizace dashboardu:

login, role user / admin,

správa uživatelů (admin_users.php),

omezení přístupu k tombole.

✅ Modul Tombola (tombola.php + tombola_tazene.php):

definice akcí a cen,

ukládání losů do DB,

přelosování při no_show,

veřejný náhled výsledků přes public_code,

QR kód pro hosty, auto-refresh výsledkovky.

V plánu / TODO
⭕ Reálný kvantový hardware

přepojení backendu na skutečný kvantový procesor (IBM Quantum, IonQ, …),

možnost kombinovat více zdrojů náhody (hardware + fallback emulace).

⭕ API keys / rate-limit / kvóty

vynucený X-API-Key pro přístup k API,

rate-limiting, kvóty per key / per IP.

⭕ Rozšířená autorizace a audit

jemnější práva pro různé typy uživatelů,

audit log (kdo kdy losoval / přelosovával),

možnost deaktivace / expirování účtů.

⭕ Lepší logování a monitoring

JSON logy, logrotate,

základní statistiky per IP / per API klíč / per typ požadavku,

health & metrics endpointy pro monitoring.
