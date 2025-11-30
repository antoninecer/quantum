# Quantum Random API & Dashboard

Quantum Random je malý experimentální projekt, který má jednu jednoduchou ambici:

> **poskytovat náhodná data přes jednoduché HTTP API,** které je dnes postavené na Pythonu a kvantové emulaci – a do budoucna se dá přepojit na reálný kvantový hardware.

🔒 O kvalitě náhodnosti

Tento projekt používá kvantově inspirovaný algoritmus společně s moderním kryptograficky bezpečným generátorem náhodných čísel (CSPRNG).
I když probíhá simulace qubitů, proces měření využívá skutečnou entropii systému, což zajišťuje:

plně nepředvídatelné výsledky,

vysokou kryptografickou bezpečnost,

rovnoměrné rozložení hodnot,

spolehlivost i pro loterie, hry a šifrování.

Z hlediska aplikací (bezpečnost, statistika, generování hesel, loterie) je tato náhodnost ekvivalentní skutečným kvantovým RNG — rozdíl je pouze v tom, 
že místo fyzického qubitu se simuluje jeho chování, ale samotná náhodnost pochází z CSPRNG a není deterministická.


Repo obsahuje:

- **`api/`** – Python / FastAPI služba `quantum.api.ventureout.cz`
- **`dashboard/`** – PHP dashboard `dashboard.api.ventureout.cz`, který API jen „obaluje“ do webového UI

---

## Jak to funguje (high-level)

### 1. Klient → HTTP JSON request

Klient (dashboard, vlastní appka, curl…) volá:

http
POST https://quantum.api.ventureout.cz/random
Content-Type: application/json

Pošle JSON:

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


type – "int" nebo "char"

count – kolik hodnot vygenerovat

unique – má smysl hlavně pro int (např. loterie)

range – [min, max] pro int

alphabet – množina znaků pro char (hesla, tokeny)

2. request_parser.py

FastAPI endpoint v main.py vezme body a předá ho do:

request_parser.process_request(request_list)

V tomhle kroku:

se zkontroluje struktura,

připraví se interní reprezentace požadavků,

pro každý „task“ se zavolá generátor náhodných dat.

3. quantum_random.py – generátor náhodných dat

Jádro generování je v Pythonu v souboru quantum_random.py.

Aktuální stav:

používá kombinaci Python knihoven a kvantové emulace, tj.:

kvalitní entropy zdroj z OS / kryptografických knihoven,

kvantový simulátor (emulátor) jako backend pro generování bitových sekvencí,

reálný hardware zatím není připojený – rozhraní je ale navržené tak, aby šlo později backend vyměnit za:

IBM Quantum, IonQ, Quantinuum nebo jiného providera,

případně mix více zdrojů (hardware + fallback emulace).

Cíl: udržet čisté API, které neřeší, jak náhodu získáme – back-end se může změnit, rozhraní zůstane.

4. Odpověď

Výsledek vypadá např.:

{
  "result": [
    [12, 5, 37, 48, 9]
  ]
}


result je pole výsledků pro jednotlivé tasky ve request.

pro Eurojackpot (5/50 + 2/12) přijdou dvě vnořená pole: prvních 5 čísel, pak 2 čísla z 1–12.

Technologie / stack
API (api/app)

Jazyk: Python 3.x

Framework: FastAPI

Server: uvicorn (za Nginx reverse proxy)

Struktura:

main.py – FastAPI app, endpoint /random, /health, /version

request_parser.py – validace a transformace requestu

quantum_random.py – generování náhodných dat (kombinace knihoven + kvantová emulace)

todo.txt – poznámky k logování, rate-limitům, API keyům apod.

Seznam konkrétních Python závislostí je / bude v api/requirements.txt.

Dashboard (dashboard/web)

Dashboard je tenký PHP layer nad API:

Server: Nginx + PHP-FPM 8.3

Hlavní soubory:

index.php – „dokumentace“ a vysvětlení API

dashboard.php – UI pro generování náhodných hodnot

includes/header.php / footer.php – společné menu / layout

assets/css/style.css – card design + responzivita (desktop/mobil)

assets/js/app.js – JS logika:

stav presetů (Sportka, Eurojackpot, Dice, Password),

skládání JSON payloadu,

volání https://quantum.api.ventureout.cz/random,

zobrazení JSON requestu, response a cURL,

u hesel navíc vynucení: min. jedno malé, jedno velké, číslice a speciální znak (!?@#$%*+-).

Dashboard sám žádná data negeneruje, jen vizuálně řídí API.

Příklad – presety v dashboardu
Sportka (6/49)

Dashboard nastaví:

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

Eurojackpot (5/50 + 2/12)

Tady jde o dva tasky v jednom requestu:

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

Password (16 znaků)

Dashboard pošle:

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


JS po obdržení výsledků navíc zkontroluje, že v hesle je:

aspoň 1 malé písmeno,

aspoň 1 velké písmeno,

aspoň 1 číslice,

aspoň 1 speciální znak z výše uvedené množiny;

pokud ne, přepíše náhodné pozice tak, aby podmínka platila.

Nginx / provoz

Konfigurace, které se verziují v repu (pro přehled):

etc/nginx/sites-enabled/dashboard.api.ventureout.cz
etc/nginx/sites-enabled/quantum.api.ventureout.cz


Dashboard – statický obsah + PHP z /opt/quantum/dashboard/web

API – reverse proxy na 127.0.0.1:8000 (uvicorn s FastAPI)

Certifikáty spravuje Let’s Encrypt (Certbot).

Stav implementace (zjednodušený TODO)
Hotovo

✅ API endpoint POST /random + základní struktura request/response

✅ Python backend s oddělením:

parsování požadavku (request_parser.py)

generování náhodných dat (quantum_random.py)

✅ Dashboard s presety (Sportka, Eurojackpot, Dice, Password)

✅ Hesla s vynucenou kombinací malá/velká/číslice/speciál

✅ Responzivní layout dashboardu (desktop + mobil, žádné „uřezané“ kódy v <pre>)

Nehotovo / v plánu

⭕ Reálný kvantový hardware

zatím kvantová část běží na emulaci / knihovnách, ne na skutečném QPU

⭕ API keys / rate-limit / kvóty

zatím není vynucený X-API-Key

rate-limit je jen v náčrtu (todo.txt), ne plně nasazený

⭕ Login do dashboardu

dashboard je teď veřejný, bez autentizace

⭕ Lepší logování a monitoring

JSON logy, logrotate, statistiky per IP / per key / per typ požadavku

Lokální vývoj (krátce)

Pozn.: tohle je spíš nástřel, přesná konfigurace se může lišit podle konkrétního serveru.

API
cd api
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt

# spuštění
uvicorn app.main:app --reload --port 8000


API pak bude na http://127.0.0.1:8000.

Dashboard
cd dashboard/web
php -S 127.0.0.1:8080


Dashboard poběží na http://127.0.0.1:8080/ a bude volat API podle nastavené URL (v produkci https://quantum.api.ventureout.cz, lokálně možno přepnout na http://127.0.0.1:8000).

## Python závislosti (API)

API běží v samostatném virtuálním prostředí (venv) v adresáři:
/opt/quantum/api/venv

Obnovení prostředí na jiném stroji:

cd /opt/quantum/api
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
deactivate

*** Service quantum-api.service ***

cat /etc/systemd/system/quantum-api.service
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

***

Aktivace služby
# načtení nové jednotky
systemctl daemon-reload

# spuštění služby
systemctl start quantum-api.service

# zapnutí po rebootu
systemctl enable quantum-api.service

# kontrola stavu
systemctl status quantum-api.service
