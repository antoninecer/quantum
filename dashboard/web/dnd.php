<?php
// dashboard/web/dnd.php
include __DIR__ . '/includes/header.php';
?>

<main class="page page-dnd">
    <section class="dnd-hero">
        <div class="dnd-hero-text">
            <h1>DnD Dice &nbsp;<span>powered by Quantum RNG</span></h1>
            <p>
            Házej d4–d20 kostky s kvantovou náhodou – pro útoky,
            záchranné hody, skill checky i damage. Všechno běží přes API
            <span class="endpoint-badge">/random</span>.
            </p>
        </div>
        <div class="dnd-hero-dice">
            <div class="dice-orbit">
                <div class="dice dice-d20">20</div>
                <div class="dice dice-d12">12</div>
                <div class="dice dice-d8">8</div>
            </div>
        </div>
    </section>

    <section class="dnd-layout">
        <!-- LEVÝ PANEL – konfigurace hodu -->
        <div class="dnd-panel dnd-config">
            <h2>Nastavení hodu</h2>

            <form id="dnd-form" class="dnd-form">
                <!-- Typ hodu -->
                <div class="form-group">
                    <label for="rollType">Typ hodu</label>
                    <select id="rollType">
                        <option value="attack">Attack roll (útok)</option>
                        <option value="save">Saving throw (záchranný hod)</option>
                        <option value="skill">Skill / ability check</option>
                        <option value="damage">Damage roll (zranění)</option>
                        <option value="custom">Custom</option>
                    </select>
                    <p class="hint">
                        Typ hodu jen předvyplní kostku a počet – vždy to můžeš ručně změnit.
                    </p>
                </div>

                <!-- Kostka -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="diceType">Kostka</label>
                        <select id="diceType">
                            <option value="4">d4</option>
                            <option value="6">d6</option>
                            <option value="8">d8</option>
                            <option value="10">d10</option>
                            <option value="12">d12</option>
                            <option value="20" selected>d20</option>
                            <option value="100">d100</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="diceCount">Počet kostek</label>
                        <input id="diceCount" type="number" min="1" max="20" value="1">
                    </div>
                </div>

                <!-- Advantage / disadvantage -->
                <div class="form-group">
                    <label for="advMode">Režim</label>
                    <select id="advMode">
                        <option value="normal" selected>Normal</option>
                        <option value="adv">Advantage (2×, vezmi vyšší)</option>
                        <option value="dis">Disadvantage (2×, vezmi nižší)</option>
                    </select>
                    <p class="hint">
                        Nejčastěji pro d20 útoky / záchranné hody. Technicky to ale funguje i pro jiné kostky.
                    </p>
                </div>

                <!-- Modifikátor -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="modifier">Modifikátor (+/-)</label>
                        <input id="modifier" type="number" value="0">
                        <p class="hint">
                            Např. +5 k útoku, +3 ke záchrannému hodu, apod.
                        </p>
                    </div>
                </div>

                <!-- Tlačítko -->
                <div class="form-actions">
                    <button id="rollBtn" type="submit" class="btn-primary">
                        Hodit kostkami
                    </button>
                    <span id="rollStatus" class="roll-status"></span>
                </div>
            </form>
        </div>

        <!-- PRAVÝ PANEL – výsledky -->
        <div class="dnd-panel dnd-result">
            <h2>Výsledek</h2>

            <div class="result-summary">
                <div class="result-label">Poslední hod:</div>
                <div id="resultTitle" class="result-title">
                    Zatím nic – hoď si prvním útokem ⚔️
                </div>
            </div>

            <div id="diceContainer" class="dice-row">
                <!-- sem se vykreslí jednotlivé kostky -->
            </div>

            <div id="totalLine" class="result-total"></div>
                <div class="result-extra">
    <details>
        <summary>Debug – zobrazit JSON request/response</summary>
        <pre id="debugRequest" class="code-block"></pre>
        <pre id="debugResponse" class="code-block"></pre>
    </details>

    <details class="help-details" open>
        <summary>Nápověda k DnD hodům / Help</summary>

        <div class="help-lang-switch">
            <button type="button" class="help-lang-btn active" data-lang="cs">
                Česky
            </button>
            <button type="button" class="help-lang-btn" data-lang="en">
                English
            </button>
        </div>

        <!-- ČESKY -->
        <div class="help-text help-text-cs">
            <h3>Jaké kostky se v DnD používají</h3>
            <ul>
                <li><strong>d20</strong> – útoky, záchranné hody, skill/ability checky.</li>
                <li><strong>d4 / d6 / d8 / d10 / d12</strong> – většinou zranění (damage).</li>
                <li><strong>d100</strong> – procentuální hod (1–100&nbsp;%).</li>
            </ul>

            <h3>Typ hodu</h3>
            <ul>
                <li><strong>Attack roll</strong> – klasický hod na zásah cíle (většinou 1× d20 + modifikátor útoku).</li>
                <li><strong>Saving throw</strong> – záchranný hod proti kouzlu/efektu (1× d20 + příslušný modifikátor).</li>
                <li><strong>Skill / ability check</strong> – ověřování dovedností (1× d20 + bonus za skill/stat).</li>
                <li><strong>Damage roll</strong> – hod na zranění, obvykle více menších kostek (např. 2×d6, 1×d8, 8×d6).</li>
                <li><strong>Custom</strong> – ruční nastavení kostky a počtu.</li>
            </ul>

            <h3>Režim (Normal / Advantage / Disadvantage)</h3>
            <ul>
                <li><strong>Normal</strong> – hodíš 1× danou kostkou (typicky 1× d20).</li>
                <li><strong>Advantage</strong> – hodíš 2× d20 a <strong>bereš vyšší</strong> výsledek
                    (např. když máš výhodu v situaci).</li>
                <li><strong>Disadvantage</strong> – hodíš 2× d20 a <strong>bereš nižší</strong> výsledek
                    (když máš nevýhodu).</li>
            </ul>

            <h3>Modifikátor (+/−)</h3>
            <ul>
                <li>Zadáš bonus nebo postih, který se k hodu přičítá/odečítá.</li>
                <li>Příklady: <strong>+5</strong> k útoku, <strong>+3</strong> k záchrannému hodu, <strong>−1</strong> postih.</li>
            </ul>

            <h3>Typické příklady</h3>
            <ul>
                <li><strong>Útok mečem</strong> – Typ hodu: Attack roll, Kostka: d20, Počet: 1, Režim: Normal/Adv, Modifikátor: např. +5.</li>
                <li><strong>Fireball (kouzlo)</strong> – Typ hodu: Damage roll, Kostka: d6, Počet: 8 (8× d6), Režim: Normal, Modifikátor: většinou 0.</li>
                <li><strong>Skok přes propast</strong> – Typ hodu: Skill / ability check, Kostka: d20, Počet: 1, Režim: podle situace, Modifikátor: bonus za DEX/skill.</li>
            </ul>
        </div>

        <!-- ENGLISH -->
        <div class="help-text help-text-en" style="display: none;">
            <h3>Which dice are used in DnD</h3>
            <ul>
                <li><strong>d20</strong> – attacks, saving throws, skill/ability checks.</li>
                <li><strong>d4 / d6 / d8 / d10 / d12</strong> – usually damage rolls.</li>
                <li><strong>d100</strong> – percentile roll (1–100&nbsp;%).</li>
            </ul>

            <h3>Roll type</h3>
            <ul>
                <li><strong>Attack roll</strong> – classic attack against a target (usually 1× d20 + attack modifier).</li>
                <li><strong>Saving throw</strong> – resisting a spell/effect (1× d20 + the relevant save modifier).</li>
                <li><strong>Skill / ability check</strong> – checking skills/abilities (1× d20 + skill/ability bonus).</li>
                <li><strong>Damage roll</strong> – damage dealt, often multiple smaller dice (2×d6, 1×d8, 8×d6, …).</li>
                <li><strong>Custom</strong> – fully manual setup (any dice, any count).</li>
            </ul>

            <h3>Mode (Normal / Advantage / Disadvantage)</h3>
            <ul>
                <li><strong>Normal</strong> – roll once (typically 1× d20).</li>
                <li><strong>Advantage</strong> – roll 2× d20 and <strong>take the higher</strong> result
                    (you’re in a favourable situation).</li>
                <li><strong>Disadvantage</strong> – roll 2× d20 and <strong>take the lower</strong> result
                    (you’re at a disadvantage).</li>
            </ul>

            <h3>Modifier (+/−)</h3>
            <ul>
                <li>Bonus or penalty added to the roll.</li>
                <li>Examples: <strong>+5</strong> to attack, <strong>+3</strong> to saving throw, <strong>−1</strong> penalty.</li>
            </ul>

            <h3>Typical examples</h3>
            <ul>
                <li><strong>Sword attack</strong> – Roll type: Attack roll, Dice: d20, Count: 1, Mode: Normal/Adv, Modifier: e.g. +5.</li>
                <li><strong>Fireball spell</strong> – Roll type: Damage roll, Dice: d6, Count: 8 (8× d6), Mode: Normal, Modifier: usually 0.</li>
                <li><strong>Jump over a gap</strong> – Roll type: Skill / ability check, Dice: d20, Count: 1, Mode: depends, Modifier: DEX/skill bonus.</li>
            </ul>
        </div>
    </details>
</div>

            
            </div>
        </div>
    </section>
</main>

<script src="assets/js/dnd.js"></script>

<?php
include __DIR__ . '/includes/footer.php';
?>

