<?= $this->extend('game/layout') ?>

<?= $this->section('content') ?>
<?php
$userId = session()->get('user_id');
$gameModel = new \App\Models\GameModel();
$game = $gameModel->where('game_token', $token)->first();
$player1 = (new \App\Models\UserModel())->find($game['player1_id']);
$player2 = $game['player2_id'] ? (new \App\Models\UserModel())->find($game['player2_id']) : null;

// pass player names to layout
$game['player1_name'] = $player1->username ?? 'Player 1';
$game['player2_name'] = $player2->username ?? 'Waiting...';
?>

<h3>Waiting for opponent...</h3>
<p>Share this link with your friend:</p>
<input class="form-control mb-2" value="<?= current_url() ?>" readonly>

<form id="set-code-form">
    <input type="hidden" name="token" value="<?= $token ?>">
    <button type="submit" class="btn btn-success mt-2">Start Game</button>
</form>

<div id="status"></div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$('#set-code-form').on('submit', function(e){
    e.preventDefault();
    $.post('<?= site_url('set-code') ?>', $(this).serialize(), function(res){
        $('#status').html('<p>Starting game...</p>');
        setTimeout(function(){
            window.location.href = '<?= site_url('play/'.$token) ?>';
        }, 2000);
    });
});
</script>
<?= $this->endSection() ?>
