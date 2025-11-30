<?php include "includes/header.php"; ?>

<h1>Quantum Random Generator</h1>

<p class="intro">
    Choose one of the presets or build your own request to the Quantum Random API.
</p>

<!-- PRESETS -->
<div class="preset-buttons">
    <button type="button" onclick="loadPreset('sportka')">Sportka (6/49)</button>
    <button type="button" onclick="loadPreset('eurojackpot')">Eurojackpot (5/50 + 2/12)</button>
    <button type="button" onclick="loadPreset('dice6')">Dice d6</button>
    <button type="button" onclick="loadPreset('dice20')">Dice d20</button>
    <button type="button" onclick="loadPreset('password')">Password (16 chars)</button>
</div>

<!-- FORM -->
<div class="gen-box">
    <label>Type:</label>
    <select id="type">
        <option value="int">Integer</option>
        <option value="char">Characters (from alphabet)</option>
    </select>

    <br><br>

    <label>Range (for integers):</label>
    <input id="range" type="text" placeholder="1-49">

    <br><br>

    <label>Alphabet (for characters):</label>
    <input id="alphabet" type="text" placeholder="abcABC123!?">

    <br><br>

    <label>Count:</label>
    <input id="count" type="number" value="6" min="1">

    <br><br>

    <label>
        <input id="unique" type="checkbox" checked>
        Unique (only for integers)
    </label>

    <br><br>

    <button class="generate" type="button" onclick="sendRequest()">➡ Send Request</button>
</div>

<!-- OUTPUT -->
<h3>JSON Request</h3>
<pre id="json_request" class="result-box"></pre>

<h3>Response</h3>
<pre id="output" class="result-box"></pre>

<h3>cURL</h3>
<pre id="curl" class="result-box"></pre>

<?php include "includes/footer.php"; ?>

