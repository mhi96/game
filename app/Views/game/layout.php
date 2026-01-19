<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'Guess The Code' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .game-header { background: #343a40; color: #fff; padding: 10px 0; margin-bottom: 20px; }
        .game-header h4 { margin: 0; }
        .players { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .players div { text-align: center; flex: 1; }
        .players div h5 { margin-bottom: 0; }

        /* Guess table row colors */
        .win-row {
            background-color: #28a745 !important; /* green for correct 4 positions */
            color: #fff;
        }

        .partial-row {
            background-color: #ffc107 !important; /* yellow for partial match */
            color: #212529;
        }

    </style>
</head>
<body>
    
<div class="game-header text-center">
    Logged in as: <strong><?= esc(session()->get('username')) ?></strong>
    <a href="<?= site_url('logout') ?>" class="btn btn-sm btn-danger ml-3">Logout</a>
    <a href="<?= site_url('dashboard') ?>" class="btn btn-sm btn-secondary ml-3">Dashboard</a>

</div>

<?php if(isset($game)): ?>
    <div class="container players">
        <div>
            <h5><?= esc($game['player1_username'] ?? 'Player 1') ?></h5>
            <small>Player 1</small>
        </div>
        <div>
            <h5><?= esc($game['player2_username'] ?? 'Waiting...') ?></h5>
            <small>Player 2</small>
        </div>
    </div>
<?php endif; ?>

<div class="container">
    <?= $this->renderSection('content') ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
