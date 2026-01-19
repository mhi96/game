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
<table class="table table-bordered table-hover text-center align-middle">
    <thead class="thead-light">
        <tr>
            <th>#</th>
            <th>Players</th>
            <th>Status</th>
            <th>Your Role</th>
            <th>Action</th>
            <th>Join Link</th>
        </tr>
    </thead>
    <tbody>

    <?php foreach ($games as $i => $game): ?>

        <?php
            $userId = session()->get('user_id');

            $role = ($userId == $game['player1_id'])
                ? 'Player 1'
                : 'Player 2';

            $playUrl = site_url('play/' . $game['game_token']);
            $joinUrl = site_url('game/join?token=' . $game['game_token']);
            $i + 1;
        ?>

        <tr>
            <td><?= esc($game['id'])?></td>

            <td>
                <strong><?= esc($game['player1_username']) ?></strong>
                vs
                <strong><?= esc($game['player2_username'] ?? 'Waiting...') ?></strong>
            </td>

            <td>
                <?php if ($game['status'] === 'inactive'): ?>
                    <span class="badge badge-secondary">Inactive</span>

                <?php elseif ($game['status'] === 'waiting'): ?>
                    <span class="badge badge-warning">Waiting</span>

                <?php elseif ($game['status'] === 'active'): ?>
                    <span class="badge badge-success">Active</span>

                <?php else: ?>
                    <span class="badge badge-dark">Finished</span>
                <?php endif; ?>
            </td>

            <td><?= $role ?></td>

            <td>
                <?php if (in_array($game['status'], ['waiting', 'active', 'inactive'])): ?>
                    <a href="<?= $playUrl ?>" class="btn btn-primary btn-sm">
                        Open
                    </a>
                <?php else: ?>
                    <button class="btn btn-secondary btn-sm" disabled>
                        Finished
                    </button>
                <?php endif; ?>
            </td>

            <td class="text-center align-middle">
                <?php if ($game['status'] === 'inactive'): ?>
                    <?php
                        $waText = urlencode('Join my game: ' . $joinUrl);
                        $waUrl  = 'https://wa.me/?text=' . $waText;
                    ?>

                    <div class="d-inline-flex align-items-center gap-2">

                        <input type="hidden" id="joinLink<?= $i ?>" value="<?= $joinUrl ?>">

                        <button
                            class="btn btn-outline-secondary btn-sm"
                            onclick="copyLink('joinLink<?= $i ?>')">
                            Copy Join Link
                        </button>

                        <a
                            href="<?= $waUrl ?>"
                            target="_blank"
                            class="btn btn-success btn-sm"
                            title="Share via WhatsApp">
                            <i class="fab fa-whatsapp"></i> Share
                        </a>

                    </div>

                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
        </tr>

    <?php endforeach; ?>

    </tbody>
</table>
</div>

<?php endif; ?>

<script>
function copyLink(id) {
        const input = document.getElementById(id);

        if (!input) {
            console.error('Input element not found with id:', id);
            return;
        }

        const textToCopy = input.value;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(textToCopy)
                .then(() => {
                    alert('Join link copied!');
                })
                .catch(err => {
                    console.error('Clipboard write failed, fallback:', err);
                    fallbackCopy(textToCopy);
                });
        } else {
            // fallback for older browsers
            fallbackCopy(textToCopy);
        }
    }

    // fallback using execCommand
    function fallbackCopy(text) {
        const tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        tempInput.setSelectionRange(0, 99999); // for mobile
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        alert('Join link copied.');
    }
</script>

<?= $this->endSection() ?>
