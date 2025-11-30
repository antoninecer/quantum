const API = "https://quantum.api.ventureout.cz/random";

// Copy to clipboard
function copyText(id) {
    const text = document.getElementById(id).innerText;
    navigator.clipboard.writeText(text);
}

// SPORTKA
async function generateSportka() {
    const body = {
        request: [
            { random: { type: "int", range: [1, 49], count: 6, unique: true } }
        ]
    };

    let res = await fetch(API, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body)
    });

    let json = await res.json();
    document.getElementById("sportka-output").innerText = json.result[0].join(", ");
}

// EUROJACKPOT
async function generateEurojackpot() {
    const body = {
        request: [
            { random: { type: "int", range: [1, 50], count: 5, unique: true } },
            { random: { type: "int", range: [1, 12], count: 2, unique: true } }
        ]
    };

    let res = await fetch(API, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body)
    });

    let json = await res.json();
    const a = json.result[0].join(", ");
    const b = json.result[1].join(", ");

    document.getElementById("euro-output").innerText = `${a} | ${b}`;
}

// DICE
async function rollDice(sides) {
    const body = {
        request: [
            { random: { type: "int", range: [1, sides], count: 1, unique: false } }
        ]
    };

    let res = await fetch(API, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body)
    });

    let json = await res.json();
    document.getElementById("dice-output").innerText = `d${sides}: ${json.result[0][0]}`;
}

