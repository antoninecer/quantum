<?php include "includes/header.php"; ?>
<h1>Quantum Random API – Documentation</h1>

<p>
The Quantum Random API provides unpredictable random values based on quantum-level noise.  
It is designed for games, simulations, lotteries, password generation and other use-cases 
where high-quality randomness matters.
</p>

<hr>

<h2>🔧 API Endpoint</h2>

<pre class="result-box">
POST https://quantum.api.ventureout.cz/random
Content-Type: application/json
</pre>

<hr>

<h2>📦 Request Format</h2>

<p>Send an array of <strong>random tasks</strong> inside a single request:</p>

<pre class="result-box">
{
  "request": [
    {
      "random": {
        "type": "int | string",
        "range": [min, max],     // for type=int
        "alphabet": "ABC…",      // for type=string
        "count": number_of_items_or_chars,
        "unique": true|false
      }
    }
  ]
}
</pre>

<hr>

<h2>🎲 Random Integers</h2>

<p>Example: generate 5 unique numbers between 1–50 (e.g. for Sportka/EUROJACKPOT):</p>

<pre class="result-box">
{
  "request": [
    {
      "random": {
        "type": "int",
        "range": [1, 50],
        "count": 5,
        "unique": true
      }
    }
  ]
}
</pre>

<hr>

<h2>🎰 Lottery Examples</h2>

<h3>• Sportka (6 numbers 1–49)</h3>

<pre class="result-box">
{
  "request": [
    { "random": { "type": "int", "range": [1,49], "count": 6, "unique": true }}
  ]
}
</pre>

<h3>• Eurojackpot (5 numbers 1–50 + 2 numbers 1–12)</h3>

<pre class="result-box">
{
  "request": [
    { "random": { "type": "int", "range": [1,50], "count": 5, "unique": true }},
    { "random": { "type": "int", "range": [1,12], "count": 2, "unique": true }}
  ]
}
</pre>

<hr>

<h2>🧊 Dice Rolls (DnD / RPG)</h2>

<pre class="result-box">
{
  "request": [
    { "random": { "type": "int", "range": [1,6], "count": 4, "unique": false }}
  ]
}
</pre>

<hr>

<h2>🔤 Random String / Text Generation</h2>

<h3>Basic alphanumeric string (16 chars):</h3>

<pre class="result-box">
{
  "request": [
    {
      "random": {
        "type": "string",
        "alphabet": "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
        "count": 16,
        "unique": false
      }
    }
  ]
}
</pre>

<h3>Secure password (20 chars):</h3>

<pre class="result-box">
{
  "request": [
    {
      "random": {
        "type": "string",
        "alphabet": "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+<>?",
        "count": 20,
        "unique": false
      }
    }
  ]
}
</pre>

<h3>HEX token (64 chars):</h3>

<pre class="result-box">
{
  "request": [
    {
      "random": {
        "type": "string",
        "alphabet": "0123456789abcdef",
        "count": 64,
        "unique": false
      }
    }
  ]
}
</pre>

<h3>Custom alphabet:</h3>

<pre class="result-box">
{
  "request": [
    {
      "random": {
        "type": "string",
        "alphabet": "ABCDEF123",
        "count": 10,
        "unique": false
      }
    }
  ]
}
</pre>

<hr>

<h2>🛡 Rate Limiting</h2>

<p>The public API is limited to:</p>

<ul>
    <li><strong>3 requests per minute per IP</strong></li>
    <li>No API key needed for basic exploration</li>
</ul>

<p>Higher limits & API keys will be added later.</p>

<hr>

<h2>📘 Notes</h2>
<ul>
    <li><code>type: "int"</code> → requires <code>range</code></li>
    <li><code>type: "string"</code> → requires <code>alphabet</code></li>
    <li><code>unique: true</code> ensures non-repeating values (only for integers)</li>
    <li>Multiple random tasks can be executed in a single HTTP request</li>
</ul>

<p>
The Quantum API is simple and predictable — each request is transparent and logged,  
and all numbers are generated using quantum-level entropy (simulated for speed).
</p>

<p><b>Author:</b> Antonin Ecer — VentureOut Labs</p>

<?php include "includes/footer.php"; ?>

