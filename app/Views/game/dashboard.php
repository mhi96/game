<?= $this->extend('game/layout') ?>

<?= $this->section('content') ?>
<h2 class="mb-4 text-center">Ongoing Games</h2>

<div class="text-center mb-3">
    <a href="<?= site_url('create') ?>" class="btn btn-success">
        Create New Game
    </a>
</div>

<?php if (empty($games)): ?>
    <div class="alert alert-info text-center">
        No ongoing games found.
    </div>
<?php else: ?>

<div class="table-responsive">
<table class="table table-bordered table-hover text-center">
    <thead class="thead-light">
        <tr>
            <th>#</th>
            <th>Players</th>
            <th>Status</th>
            <th>Your Role</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($games as $i => $game): ?>
            <?php
                $userId = session()->get('user_id');

                $role = ($game['player1_id'] == $userId) ? 'Player 1' : 'Player 2';

                $statusBadge = $game['status'] === 'waiting'
                    ? '<span class="badge badge-warning">Waiting</span>'
                    : '<span class="badge badge-success">Active</span>';
            ?>
            <tr>
                <td><?= $i + 1 ?></td>

                <td>
                    <?= esc($game['player1_username']) ?>
                    <strong>vs</strong>
                    <?= esc($game['player2_username'] ?? 'Waiting...') ?>
                </td>

                <td><?= $statusBadge ?></td>

                <td><?= $role ?></td>

                <td>
                    <a href="<?= site_url('play/'.$game['game_token']) ?>"
                       class="btn btn-primary btn-sm">
                        Resume
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php endif; ?>

<?= $this->endSection() ?>
