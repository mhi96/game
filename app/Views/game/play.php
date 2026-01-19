<?= $this->extend('game/layout') ?>

<?= $this->section('content') ?>
<h2 class="text-center mb-4">Guess The Code</h2>

<div class="text-center mb-3">
    <h5>
        <?= esc($game['player1_username']) ?>
        vs
        <?= esc($game['player2_username'] ?? 'Waiting for opponent...') ?>
    </h5>

    <?php if ($myCode): ?>
        <div class="alert alert-info d-inline-block mt-2">
            <strong>Your Secret Code:</strong>
            <span class="badge badge-dark px-3 py-2"><?= esc($myCode) ?></span>
        </div>
    <?php else: ?>
        <div class="alert alert-warning d-inline-block mt-2">
            Your secret code is not set yet
        </div>
    <?php endif; ?>
</div>

<!-- SHOW JOIN LINK IF PLAYER 2 HASN'T JOINED -->
<?php if (empty($game['player2_id'])): ?>
<div class="card mb-4">
    <div class="card-body text-center">
        <h5 class="mb-2">Invite Player 2</h5>
        <div class="input-group justify-content-center">
            <?php $joinLink = site_url('join/'.$game['game_token']); ?>
            <input type="text" id="joinLink" class="form-control mr-2" value="<?= $joinLink ?>" readonly>
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" onclick="copyLink()">Copy</button>
            </div>
            <div class="input-group-append">
                    <?php
                        $waText = urlencode('Join my game: ' . $joinLink);
                        $waUrl  = 'https://wa.me/?text=' . $waText;
                    ?>
                <a href="<?= $waUrl ?>" target="_blank" class="btn btn-success" title="Share via WhatsApp"><i class="fab fa-whatsapp"></i> Share</a>
            </div>
        </div>
        <small class="text-muted d-block mt-2">
            Share this link with a friend to join the game.
        </small>
    </div>
</div>
<?php endif; ?>

<!-- SET / RE-SET CODE -->
<?php if (!$myCode): ?>
<div class="card mb-4">
    <div class="card-body text-center">
        <h5 class="mb-2">Set Your Secret Code</h5>
        <form id="setCodeForm" class="form-inline justify-content-center">
            <input type="text"
                   id="secretCodeInput"
                   class="form-control mr-2"
                   maxlength="4"
                   placeholder="4-digit code"
                   required>
            <button class="btn btn-warning">Save Code</button>
        </form>
        <small class="text-muted d-block mt-2">
            This option appears only if your code was not saved.
        </small>
    </div>
</div>
<?php endif; ?>

<div id="turnMessage" class="alert alert-secondary text-center mb-4">
    Loading game state...
</div>

<!-- GUESS FORM -->
<div class="text-center mb-4">
    <form id="guessForm" class="form-inline justify-content-center">
        <input type="number"
               id="guessInput"
               class="form-control mr-2"
               maxlength="4"
               placeholder="Enter 4-digit guess"
               required>
        <button type="submit" class="btn btn-primary">Submit Guess</button>
        <input type="hidden" id="gameId" value="<?= $game_id ?>">
    </form>
</div>

<!-- HISTORY TABLES -->
<div class="row">
    <div class="col-md-6">
        <h4><?= esc($game['player1_username']) ?></h4>
        <table class="table table-sm table-bordered">
            <thead class="thead-light">
            <tr>
                <th>#</th>
                <th>Guess</th>
                <th>Digits</th>
                <th>Positions</th>
            </tr>
            </thead>
            <tbody id="player1Table"></tbody>
        </table>
    </div>

    <div class="col-md-6">
        <h4><?= esc($game['player2_username'] ?? 'Waiting...') ?></h4>
        <table class="table table-sm table-bordered">
            <thead class="thead-light">
            <tr>
                <th>#</th>
                <th>Guess</th>
                <th>Digits</th>
                <th>Positions</th>
            </tr>
            </thead>
            <tbody id="player2Table"></tbody>
        </table>
    </div>
</div>

<!-- CLOSE BUTTON -->
<div class="text-center mt-4 d-none" id="closeGameBox">
    <a href="<?= site_url('dashboard') ?>" class="btn btn-dark">
        Close Game
    </a>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
let player2Joined = <?= $game['player2_id'] ? 'true' : 'false' ?>;

function checkPlayer2() {
    if (player2Joined) return; // Stop if already joined

    $.get('<?= site_url('state/') ?>' + gameId, function(res){
        // In state response, let's add player2_id dynamically if needed
        if (res.player2_id && !player2Joined) {
            player2Joined = true;
            // Refresh page to update player 2 info and secret code
            location.reload();
        }
    }, 'json');
}

// Run every 10 seconds
setInterval(checkPlayer2, 10000);
</script>

<script>
const gameId = $('#gameId').val();
const myPlayer1Id = <?= (int) $game['player1_id'] ?>;
const myPlayer2Id = <?= $game['player2_id'] ? (int) $game['player2_id'] : 'null' ?>;
const guessInput = $('#guessInput');
const turnMessage = $('#turnMessage');
const p1Table = $('#player1Table');
const p2Table = $('#player2Table');

function loadGameState() {
    $.get('<?= site_url('state/') ?>' + gameId, function(res) {

        // Update turn/win message
        turnMessage
            .removeClass('alert-success alert-danger alert-secondary')
            .addClass(
                res.result === 'win'  ? 'alert-success' :
                res.result === 'lose' ? 'alert-danger'  :
                                        'alert-secondary'
            )
            .text(res.message);

        if (res.result) {
            guessInput.prop('disabled', true);
            $('#closeGameBox').removeClass('d-none');
        }

        // Clear tables
        p1Table.empty();
        p2Table.empty();

        if (!res.guesses_html) return;

        const temp = $('<div>').html(res.guesses_html);
        let c1 = 1, c2 = 1;

        temp.find('p').each(function () {
            const pid = parseInt($(this).data('player'));
            const guess = $(this).data('guess');
            const digits = parseInt($(this).data('digits'));
            const pos = parseInt($(this).data('pos'));

            // COLOR LOGIC
            let rowClass = '';
            if (pos === 4) {
                rowClass = 'table-success';    // GREEN: all correct
            } else if (digits > 0 || pos > 0) {
                rowClass = 'table-warning';    // YELLOW: partially correct
            }

            const row = `
                <tr class="${rowClass}">
                    <td>${pid === myPlayer1Id ? c1++ : c2++}</td>
                    <td>${guess}</td>
                    <td>${digits}</td>
                    <td>${pos}</td>
                </tr>
            `;

            if (pid === myPlayer1Id) {
                p1Table.append(row);
            } else if (myPlayer2Id !== null && pid === myPlayer2Id) {
                p2Table.append(row);
            }
        });
    }, 'json');
}

loadGameState();
setInterval(loadGameState, 2000);

/* SUBMIT GUESS */
$('#guessForm').on('submit', function(e){
    e.preventDefault();

    const guess = guessInput.val().trim();
    if (!/^\d{4}$/.test(guess)) {
        alert('Enter a valid 4-digit guess');
        return;
    }

    $.post('<?= site_url('guess') ?>', {
        game_id: gameId,
        guess: guess
    }, function(res){
        if (res.error) {
            alert(res.error);
            return;
        }
        guessInput.val('');
        loadGameState();
    }, 'json');
});

/* SET CODE */
$('#setCodeForm').on('submit', function(e){
    e.preventDefault();

    const code = $('#secretCodeInput').val().trim();
    if (!/^\d{4}$/.test(code)) {
        alert('Enter a valid 4-digit code');
        return;
    }

    $.post('<?= site_url('set-code') ?>', {
        game_id: gameId,
        code: code
    }, function(res){
        if (res.error) {
            alert(res.error);
            return;
        }
        location.reload();
    }, 'json');
});

/* COPY JOIN LINK */
function copyLink() {
    const copyText = document.getElementById("joinLink");
    copyText.select();
    copyText.setSelectionRange(0, 99999); // For mobile
    document.execCommand("copy");
    alert("Join link copied: " + copyText.value);
}
</script>
<?= $this->endSection() ?>
