<?php

namespace App\Controllers;

use App\Models\GameModel;
use CodeIgniter\Controller;

class Game extends BaseController
{
    /* =========================
       CREATE GAME
       ========================= */
    public function create()
    {
        $token = uniqid('game_'.date('YmdHis'), false);

        $gameModel = new GameModel();
        $gameModel->insert([
            'game_token'   => $token,
            'player1_id'   => session()->get('user_id'),
            'status'       => 'inactive',
            'last_activity'=> date('Y-m-d H:i:s')
        ]);

        return redirect()->to('play/' . $token);
    }

    /* =========================
       JOIN GAME
       ========================= */
    public function join($token = null)
    {
        
        if (!$token) {
            $token = $this->request->getGet('token');
        }

        if (!$token) {
            return view('game/join');
        }

        $gameModel = new GameModel();
        $game = $gameModel->where('game_token', $token)->first();

        if (!$game) {
            return redirect()->to('/dashboard')->with('error', 'Game not found');
        }

        $userId = session()->get('user_id');

        // ❌ Block third player
        if (!empty($game['player2_id']) && $game['player2_id'] != $userId) {
            return redirect()->to('/dashboard')->with('error', 'Game is full');
        }

        // ✅ Assign player 2
        if (empty($game['player2_id']) && $game['player1_id'] != $userId) {
            $gameModel->update($game['id'], [
                'player2_id'   => $userId,
                'status'       => 'waiting',
                'last_activity'=> date('Y-m-d H:i:s')
            ]);
        }

        return redirect()->to('play/' . $token);
    }

    /* =========================
       PLAY SCREEN
       ========================= */
    public function play($token)
    {
        $gameModel = new GameModel();

        $game = $gameModel
            ->select('games.*,
                u1.username AS player1_username,
                u2.username AS player2_username')
            ->join('users u1', 'u1.id = games.player1_id')
            ->join('users u2', 'u2.id = games.player2_id', 'left')
            ->where('game_token', $token)
            ->first();

        if (!$game) {
            return redirect()->to('/dashboard');
        }

        $userId = session()->get('user_id');

        // 🔒 Only show OWN secret code
        $myCode = null;
        if ($userId == $game['player1_id']) {
            $myCode = $game['player1_code'];
        } elseif ($userId == $game['player2_id']) {
            $myCode = $game['player2_code'];
        }

        return view('game/play', [
            'token'   => $token,
            'game_id' => $game['id'],
            'game'    => $game,
            'myCode'  => $myCode
        ]);
    }

    /* =========================
       SET SECRET CODE
       ========================= */
    public function setCode()
    {
        $gameId = $this->request->getPost('game_id');
        $code   = $this->request->getPost('code');
        $userId = session()->get('user_id');

        if (!preg_match('/^\d{4}$/', $code)) {
            return $this->response->setJSON(['error' => 'Invalid code']);
        }

        $gameModel = new GameModel();
        $game = $gameModel->find($gameId);

        if (!$game) {
            return $this->response->setJSON(['error' => 'Game not found']);
        }

        if (!in_array($userId, [$game['player1_id'], $game['player2_id']])) {
            return $this->response->setJSON(['error' => 'Unauthorized']);
        }

        $field = ($userId == $game['player1_id'])
            ? 'player1_code'
            : 'player2_code';

        if (!empty($game[$field])) {
            return $this->response->setJSON(['error' => 'Code already set']);
        }

        $gameModel->update($gameId, [
            $field          => $code,
            'last_activity' => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON(['success' => true]);
    }

    /* =========================
       MAKE GUESS
       ========================= */
    public function makeGuess()
    {
        $gameId = $this->request->getPost('game_id');
        $guess  = $this->request->getPost('guess');
        $userId = session()->get('user_id');

        if (!preg_match('/^\d{4}$/', $guess)) {
            return $this->response->setJSON(['error' => 'Invalid guess']);
        }

        $gameModel = new GameModel();
        $game = $gameModel->find($gameId);

        if (!$game || $game['status'] !== 'active') {
            return $this->response->setJSON(['error' => 'Invalid game']);
        }

        if ($game['turn_player_id'] != $userId) {
            return $this->response->setJSON(['error' => 'Not your turn']);
        }

        $opponentCode = ($userId == $game['player1_id'])
            ? $game['player2_code']
            : $game['player1_code'];

        $guessArr = str_split($guess);
        $codeArr  = str_split($opponentCode);

        $pos = 0;
        $digits = 0;

        foreach ($guessArr as $i => $d) {
            if ($d === $codeArr[$i]) $pos++;
        }

        $counts = array_count_values($codeArr);
        foreach ($guessArr as $d) {
            if (!empty($counts[$d])) {
                $digits++;
                $counts[$d]--;
            }
        }

        db_connect()->table('guesses')->insert([
            'game_id' => $gameId,
            'player_id' => $userId,
            'guess' => $guess,
            'correct_digits' => $digits,
            'correct_positions' => $pos
        ]);

        if ($pos === 4) {
            $gameModel->update($gameId, [
                'status' => 'finished',
                'winner_id' => $userId
            ]);
        } else {
            $next = ($userId == $game['player1_id'])
                ? $game['player2_id']
                : $game['player1_id'];

            $gameModel->update($gameId, [
                'turn_player_id' => $next,
                'last_activity' => date('Y-m-d H:i:s')
            ]);
        }

        return $this->response->setJSON(['success' => true]);
    }

    /* =========================
       GAME STATE (POLLING)
       ========================= */
    public function state($id)
    {
        $gameModel = new GameModel();
        $game = $gameModel->find($id);

        if (!$game) {
            return $this->response->setJSON(['error' => 'Game not found']);
        }

        $userId = session()->get('user_id');

        /* INACTIVE */
        if ($game['status'] === 'inactive') {
            return $this->response->setJSON([
                'message' => 'Waiting for Player 2 to join...',
                'result' => null,
                'guesses_html' => '',
                'player2_id' => null
            ]);
        }

        /* AUTO-HEAL */
        if (
            !empty($game['player1_id']) &&
            !empty($game['player2_id']) &&
            !empty($game['player1_code']) &&
            !empty($game['player2_code']) &&
            ($game['status'] === 'waiting' || empty($game['turn_player_id']))
        ) {
            $gameModel->update($id, [
                'status' => 'active',
                'turn_player_id' => $game['player1_id']
            ]);

            $game = $gameModel->find($id);
        }

        /* HISTORY */
        $guesses = db_connect()->table('guesses')
            ->where('game_id', $id)
            ->orderBy('created_at', 'ASC')
            ->get()->getResultArray();

        $html = '';
        foreach ($guesses as $g) {
            $html .= '<p
                data-player="'.$g['player_id'].'"
                data-guess="'.$g['guess'].'"
                data-digits="'.$g['correct_digits'].'"
                data-pos="'.$g['correct_positions'].'"></p>';
        }

        /* MESSAGE */
        if ($game['status'] === 'finished') {
            $message = ($game['winner_id'] == $userId)
                ? 'Game finished - You won'
                : 'Game finished - You lost';
            $result = ($game['winner_id'] == $userId) ? 'win' : 'lose';
        } else {
            $message = ($game['turn_player_id'] == $userId)
                ? 'Your turn'
                : 'Opponent turn';
            $result = null;
        }

        return $this->response->setJSON([
            'message' => $message,
            'result' => $result,
            'guesses_html' => $html,
            'player2_id' => $game['player2_id']
        ]);
    }

    /* =========================
       DASHBOARD
       ========================= */
    public function dashboard()
    {
        $userId = session()->get('user_id');

        $games = (new GameModel())
            ->select('games.*,
                u1.username AS player1_username,
                u2.username AS player2_username')
            ->join('users u1', 'u1.id = games.player1_id')
            ->join('users u2', 'u2.id = games.player2_id', 'left')
            ->groupStart()
                ->where('games.player1_id', $userId)
                ->orWhere('games.player2_id', $userId)
            ->groupEnd()
            ->whereIn('games.status', ['inactive','waiting','active'])
            ->orderBy('games.last_activity', 'DESC')
            ->findAll();

        return view('game/dashboard', ['games' => $games]);
    }
}
