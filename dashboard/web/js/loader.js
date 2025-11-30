async function loadHeader() {
    const header = document.getElementById("header");
    if (!header) return;

    const html = await fetch("/header.html");
    const text = await html.text();
    header.innerHTML = text;
}

document.addEventListener("DOMContentLoaded", loadHeader);

