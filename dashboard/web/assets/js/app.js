// ------------------------------------------------------------
// Quantum Random Dashboard JS
// ------------------------------------------------------------

let currentPreset = null;

// Znaky pro heslo – policy: malé, velké, číslice, speciály
const PASS_LOWER   = "abcdefghijklmnopqrstuvwxyz";
const PASS_UPPER   = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
const PASS_DIGITS  = "0123456789";
const PASS_SPECIAL = "!?@#$%*-+";

// Náhodný index v rozsahu 0..max-1
function randomIndex(max) {
    return Math.floor(Math.random() * max);
}

// Vynucení policy na poli znaků:
// - aspoň 1 malé, 1 velké, 1 číslice, 1 speciál
function enforcePasswordPolicy(chars) {
    if (!Array.isArray(chars) || chars.length === 0) return chars;

    let hasLower   = chars.some(c => PASS_LOWER.includes(c));
    let hasUpper   = chars.some(c => PASS_UPPER.includes(c));
    let hasDigit   = chars.some(c => PASS_DIGITS.includes(c));
    let hasSpecial = chars.some(c => PASS_SPECIAL.includes(c));

    const len = chars.length;
    const usedIdx = new Set();

    function pickIdx() {
        let i;
        do {
            i = randomIndex(len);
        } while (usedIdx.has(i) && usedIdx.size < len);
        usedIdx.add(i);
        return i;
    }

    if (!hasLower) {
        chars[pickIdx()] = PASS_LOWER[randomIndex(PASS_LOWER.length)];
    }
    if (!hasUpper) {
        chars[pickIdx()] = PASS_UPPER[randomIndex(PASS_UPPER.length)];
    }
    if (!hasDigit) {
        chars[pickIdx()] = PASS_DIGITS[randomIndex(PASS_DIGITS.length)];
    }
    if (!hasSpecial) {
        chars[pickIdx()] = PASS_SPECIAL[randomIndex(PASS_SPECIAL.length)];
    }

    return chars;
}

// ------------------------------------------------------------
// Presety
// ------------------------------------------------------------

function loadPreset(name) {
    currentPreset = name;

    const typeEl     = document.getElementById("type");
    const rangeEl    = document.getElementById("range");
    const alphabetEl = document.getElementById("alphabet");
    const countEl    = document.getElementById("count");
    const uniqueEl   = document.getElementById("unique");

    if (!typeEl || !rangeEl || !alphabetEl || !countEl || !uniqueEl) {
        console.error("Form elements not found");
        return;
    }

    if (name === "sportka") {
        // 6 čísel z 1–49
        typeEl.value     = "int";
        rangeEl.value    = "1-49";
        countEl.value    = 6;
        uniqueEl.checked = true;
        alphabetEl.value = "";
    } else if (name === "eurojackpot") {
        // první část – 5 čísel z 1–50, druhá část se přidá v sendRequest()
        typeEl.value     = "int";
        rangeEl.value    = "1-50";
        countEl.value    = 5;
        uniqueEl.checked = true;
        alphabetEl.value = "";
    } else if (name === "dice6") {
        typeEl.value     = "int";
        rangeEl.value    = "1-6";
        countEl.value    = 1;
        uniqueEl.checked = false;
        alphabetEl.value = "";
    } else if (name === "dice20") {
        typeEl.value     = "int";
        rangeEl.value    = "1-20";
        countEl.value    = 1;
        uniqueEl.checked = false;
        alphabetEl.value = "";
    } else if (name === "password") {
        // heslo – 16 znaků, malé+velké+čísla+speciály
        typeEl.value     = "char";
        rangeEl.value    = "";
        alphabetEl.value =
            PASS_LOWER + PASS_UPPER + PASS_DIGITS + PASS_SPECIAL;
        countEl.value    = 16;
        uniqueEl.checked = false;
    } else {
        // custom – nic zvláštního
        currentPreset = null;
    }
}

// ------------------------------------------------------------
// Odeslání požadavku na API
// ------------------------------------------------------------

async function sendRequest() {
    const typeEl     = document.getElementById("type");
    const rangeEl    = document.getElementById("range");
    const alphabetEl = document.getElementById("alphabet");
    const countEl    = document.getElementById("count");
    const uniqueEl   = document.getElementById("unique");

    const jsonBox  = document.getElementById("json_request");
    const outBox   = document.getElementById("output");
    const curlBox  = document.getElementById("curl");
    const passBox  = document.getElementById("password_plain"); // může, nemusí být

    if (!typeEl || !rangeEl || !alphabetEl || !countEl || !uniqueEl) {
        console.error("Form elements not found");
        return;
    }

    const type   = typeEl.value;
    const count  = parseInt(countEl.value, 10) || 1;
    const unique = uniqueEl.checked;

    const rangeField    = rangeEl.value.trim();
    const alphabetField = alphabetEl.value.trim();

    let range = null;
    if (rangeField !== "") {
        const parts = rangeField.split("-").map(Number);
        if (parts.length === 2 && !Number.isNaN(parts[0]) && !Number.isNaN(parts[1])) {
            range = parts;
        }
    }

    const alphabet = alphabetField !== "" ? alphabetField : null;

    let payload;

    if (currentPreset === "eurojackpot") {
        // 1. blok – podle UI (5 čísel z 1–50)
        const firstTask = {
            random: {
                type: type,
                count: count,
                unique: unique,
                range: range,
                alphabet: alphabet
            }
        };

        // 2. blok – fixně 2 čísla z 1–12, unique
        const secondTask = {
            random: {
                type: "int",
                count: 2,
                unique: true,
                range: [1, 12],
                alphabet: null
            }
        };

        payload = { request: [firstTask, secondTask] };
    } else {
        // vše ostatní – jeden blok
        payload = {
            request: [
                {
                    random: {
                        type: type,
                        count: count,
                        unique: unique,
                        range: range,
                        alphabet: alphabet
                    }
                }
            ]
        };
    }

    if (jsonBox) {
        jsonBox.textContent = JSON.stringify(payload, null, 2);
    }

    try {
        const res = await fetch("https://quantum.api.ventureout.cz/random", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        // Speciální zacházení pro preset "password"
        if (currentPreset === "password" && data && data.result && data.result[0]) {
            let chars = data.result[0];

            // vynutit policy (malé, velké, číslice, speciály)
            chars = enforcePasswordPolicy(chars);
            data.result[0] = chars;

            const password = chars.join("");

            if (outBox) {
                outBox.textContent = JSON.stringify(data, null, 2);
            }
            if (passBox) {
                passBox.textContent = password;
            }
        } else {
            if (outBox) {
                outBox.textContent = JSON.stringify(data, null, 2);
            }
        }

        if (curlBox) {
            const curl =
`curl -X POST https://quantum.api.ventureout.cz/random \\
  -H "Content-Type: application/json" \\
  -d '${JSON.stringify(payload, null, 2)}'`;
            curlBox.textContent = curl;
        }
    } catch (err) {
        console.error(err);
        if (outBox) {
            outBox.textContent = "ERROR: " + err;
        }
    }
}

