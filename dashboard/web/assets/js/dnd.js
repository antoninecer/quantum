// assets/js/dnd.js

const API_URL = "https://quantum.api.ventureout.cz/random";

const PRESETS = {
    attack: { sides: 20, count: 1, label: "Attack roll (útok)" },
    save:   { sides: 20, count: 1, label: "Saving throw (záchranný hod)" },
    skill:  { sides: 20, count: 1, label: "Skill / ability check" },
    damage: { sides: 8,  count: 1, label: "Damage roll (zranění – d8 jako default)" },
    custom: { sides: 20, count: 1, label: "Custom roll" }
};

let els = {};

document.addEventListener("DOMContentLoaded", () => {
    // --- hlavní prvky ---
    els.rollType   = document.getElementById("rollType");
    els.diceType   = document.getElementById("diceType");
    els.diceCount  = document.getElementById("diceCount");
    els.advMode    = document.getElementById("advMode");
    els.modifier   = document.getElementById("modifier");
    els.rollBtn    = document.getElementById("rollBtn");
    els.rollStatus = document.getElementById("rollStatus");

    els.resultTitle   = document.getElementById("resultTitle");
    els.diceContainer = document.getElementById("diceContainer");
    els.totalLine     = document.getElementById("totalLine");

    els.debugRequest  = document.getElementById("debugRequest");
    els.debugResponse = document.getElementById("debugResponse");

    // Presety
    if (els.rollType) {
        els.rollType.addEventListener("change", () => applyPreset(els.rollType.value));
    }

    // Submit
    const form = document.getElementById("dnd-form");
    if (form) {
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            rollDice();
        });
    }

    // Default preset
    applyPreset("attack");

    // --- přepínání jazyka v nápovědě ---
    const langButtons = document.querySelectorAll(".help-lang-btn");
    const textCs = document.querySelector(".help-text-cs");
    const textEn = document.querySelector(".help-text-en");

    if (langButtons.length && textCs && textEn) {
        langButtons.forEach((btn) => {
            btn.addEventListener("click", () => {
                const lang = btn.dataset.lang;

                // aktivní tlačítko
                langButtons.forEach((b) => b.classList.remove("active"));
                btn.classList.add("active");

                // obsah
                if (lang === "cs") {
                    textCs.style.display = "";
                    textEn.style.display = "none";
                } else {
                    textCs.style.display = "none";
                    textEn.style.display = "";
                }
            });
        });
    }
});

function applyPreset(key) {
    const preset = PRESETS[key];
    if (!preset || !els.diceType || !els.diceCount || !els.resultTitle) return;

    els.diceType.value = String(preset.sides);
    els.diceCount.value = String(preset.count);
    els.resultTitle.textContent = preset.label;
}

async function rollDice() {
    clearStatus();

    const sides = clampInt(els.diceType.value, 2, 1000);
    let count = clampInt(els.diceCount.value, 1, 20);
    const advMode = els.advMode.value; // normal | adv | dis
    const modifier = Number(els.modifier.value) || 0;

    // Advantage / disadvantage → 2 hody, vezmi vyšší/nižší
    let advActive = false;
    if ((advMode === "adv" || advMode === "dis") && count === 1) {
        count = 2;
        advActive = true;
    }

    const payload = {
        request: [
            {
                random: {
                    type: "int",
                    count: count,
                    unique: false,
                    range: [1, sides],
                    alphabet: null
                }
            }
        ]
    };

    renderDebug(payload, null);
    setStatus("Rolling…");

    try {
        const res = await fetch(API_URL, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const data = await res.json();
        renderDebug(payload, data);
        handleResult(data, { sides, count, modifier, advMode, advActive });
        clearStatus();
    } catch (err) {
        console.error(err);
        setStatus("Chyba při volání API", true);
    }
}

function handleResult(data, ctx) {
    const resultArray = data && Array.isArray(data.result) ? data.result[0] : null;
    if (!resultArray || !Array.isArray(resultArray) || resultArray.length === 0) {
        setStatus("Prázdná odpověď z API", true);
        return;
    }

    const rolls = resultArray.map(Number);

    let usedRolls = rolls.slice();
    let picked = null;

    if (ctx.advActive) {
        if (rolls.length < 2) {
            setStatus("Pro advantage/disadvantage chci aspoň 2 výsledky", true);
            return;
        }
        if (ctx.advMode === "adv") {
            picked = Math.max(rolls[0], rolls[1]);
        } else if (ctx.advMode === "dis") {
            picked = Math.min(rolls[0], rolls[1]);
        }
        usedRolls = [picked];
    }

    const sum = usedRolls.reduce((a, b) => a + b, 0);
    const total = sum + ctx.modifier;

    // Titulek
    const labelParts = [];
    labelParts.push(`${usedRolls.length}×d${ctx.sides}`);
    if (ctx.modifier !== 0) {
        labelParts.push(ctx.modifier > 0 ? `+${ctx.modifier}` : `${ctx.modifier}`);
    }
    if (ctx.advMode === "adv") labelParts.push("(advantage)");
    if (ctx.advMode === "dis") labelParts.push("(disadvantage)");

    if (els.resultTitle) {
        els.resultTitle.textContent = labelParts.join(" ");
    }

    // Kostky
    renderDice(rolls, usedRolls, ctx);

    // Součet
    let formula = usedRolls.join(" + ");
    if (ctx.modifier !== 0) {
        formula += ctx.modifier > 0 ? ` + ${ctx.modifier}` : ` - ${Math.abs(ctx.modifier)}`;
    }
    if (els.totalLine) {
        els.totalLine.textContent = `Celkem: ${total}  (${formula})`;
    }
}

function renderDice(rolls, usedRolls, ctx) {
    if (!els.diceContainer) return;

    els.diceContainer.innerHTML = "";

    const sides = ctx.sides;
    const keepSet = new Set(usedRolls);

    rolls.forEach((value) => {
        const die = document.createElement("div");
        die.classList.add("dice", `dice-d${sides}`);

        const face = document.createElement("span");
        face.classList.add("dice-face");
        face.textContent = value;

        die.appendChild(face);

        if (ctx.advActive && !keepSet.has(value)) {
            die.classList.add("dice-discarded");
        }

        els.diceContainer.appendChild(die);
    });
}

function renderDebug(request, response) {
    if (els.debugRequest) {
        els.debugRequest.textContent = JSON.stringify(request, null, 2);
    }
    if (els.debugResponse && response !== null) {
        els.debugResponse.textContent = JSON.stringify(response, null, 2);
    }
}

function setStatus(text, isError = false) {
    if (!els.rollStatus) return;
    els.rollStatus.textContent = text;
    els.rollStatus.classList.toggle("error", !!isError);
}

function clearStatus() {
    if (!els.rollStatus) return;
    els.rollStatus.textContent = "";
    els.rollStatus.classList.remove("error");
}

function clampInt(value, min, max) {
    let n = parseInt(value, 10);
    if (isNaN(n)) n = min;
    if (n < min) n = min;
    if (n > max) n = max;
    return n;
}

