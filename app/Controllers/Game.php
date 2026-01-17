<?php
namespace App\Controllers;
use App\Models\GameModel;

class Game extends BaseController 
{
    public function create() 
    {
        $token = uniqid('game_',true);
        $m = new GameModel();
        $m->insert(['game_token'=>$token,'player1_id'=>session()->get('user_id')]);
        return redirect()->to('join/'.$token);
    }

    public function join($token = null) 
    {
        // If no token in URL segment, check query string
        if(!$token) $token = $this->request->getGet('token');

        if(!$token) {
            // Show form to enter token
            return view('game/join'); 
        }

        // Check if game exists
        $gameModel = new GameModel();
        $game = $gameModel->where('game_token', $token)->first();
        if(!$game) return redirect()->to('/dashboard')->with('error','Game not found');

        // Check if a 3rd player is trying to join
        if(!empty($game['player2_id']) && $game['player2_id'] != session()->get('user_id')) {
            // Already has 2 players
            return redirect()->to('/dashboard')->with('error','This game is full. You cannot join.');
        }

        // If 2nd player slot is empty, assign current user
        if(empty($game['player2_id']) && $game['player1_id'] != session()->get('user_id')) {
            $gameModel->update($game['id'], ['player2_id'=>session()->get('user_id')]);
        }

        // Show lobby page
        return view('game/lobby',['token'=>$token]);
    }

    public function play($token)
    {
        $gameModel = new GameModel();
        $game = $gameModel
            ->select('games.*, 
                    u1.username as player1_username, 
                    u2.username as player2_username')
            ->join('users u1', 'u1.id = games.player1_id')
            ->join('users u2', 'u2.id = games.player2_id', 'left')
            ->where('game_token', $token)
            ->first();

        if (!$game) {
            return redirect()->to('/dashboard');
        }

        $userId = session()->get('user_id');

        // Only expose OWN secret code
        $myCode = null;
        if ($userId == $game['player1_id']) {
            $myCode = $game['player1_code'];
        } elseif ($userId == $game['player2_id']) {
            $myCode = $game['player2_code'];
        }

        return view('game/play', [
            'game_id' => $game['id'],
            'game'    => $game,
            'myCode'  => $myCode
        ]);
    }
    
    public function setCode()
    {
        $db = \Config\Database::connect();
        $gameModel = new GameModel();

        $gameId = $this->request->getPost('game_id');
        $code   = $this->request->getPost('code');
        $userId = session()->get('user_id');

        if (!$gameId || !$code || strlen($code) !== 4 || !ctype_digit($code)) {
            return $this->response->setJSON(['error' => 'Invalid input']);
        }

        $game = $gameModel->find($gameId);
        if (!$game) {
            return $this->response->setJSON(['error' => 'Game not found']);
        }

        // 🔒 Only players can set code
        if (!in_array($userId, [$game['player1_id'], $game['player2_id']])) {
            return $this->response->setJSON(['error' => 'Unauthorized']);
        }

        // Determine player column
        if ($userId == $game['player1_id']) {
            $codeField = 'player1_code';
        } elseif ($userId == $game['player2_id']) {
            $codeField = 'player2_code';
        } else {
            return $this->response->setJSON(['error' => 'Unauthorized']);
        }

        // ❌ Do NOT allow overwrite if already set
        if (!empty($game[$codeField])) {
            return $this->response->setJSON([
                'error' => 'Secret code already set'
            ]);
        }

        // ✅ Save code
        $gameModel->update($gameId, [
            $codeField => $code
        ]);

        return $this->response->setJSON(['success' => true]);
    }

    public function makeGuess()
    {
        $db = \Config\Database::connect(); // get database connection
        $gameId = $this->request->getPost('game_id');
        $guess = $this->request->getPost('guess');
        $userId = session()->get('user_id');

        if (!preg_match('/^\d{4}$/', $guess)) {
            return $this->response->setJSON(['error' => 'Invalid guess']);
        }

        $gameModel = new GameModel();
        $game = $gameModel->find($gameId);

        if (!$game || $game['status'] !== 'active') {
            return $this->response->setJSON(['error' => 'Invalid game']);
        }

        // Player must belong to this game
        if ($game['player1_id'] != $userId && $game['player2_id'] != $userId) {
            return $this->response->setJSON(['error' => 'You are not part of this game']);
        }

        // Enforce turn
        if ($game['turn_player_id'] != $userId) {
            return $this->response->setJSON(['error' => 'Not your turn']);
        }

        // Determine opponent code
        $opponentCode = ($game['player1_id'] == $userId)
            ? $game['player2_code']
            : $game['player1_code'];

        $guessArr = str_split($guess);
        $codeArr  = str_split($opponentCode);

        $correctPositions = 0;
        $correctDigits = 0;

        foreach ($guessArr as $i => $d) {
            if ($d === $codeArr[$i]) $correctPositions++;
        }

        $counts = array_count_values($codeArr);
        foreach ($guessArr as $d) {
            if (!empty($counts[$d])) {
                $correctDigits++;
                $counts[$d]--;
            }
        }

        $db->table('guesses')->insert([
            'game_id' => $gameId,
            'player_id' => $userId,
            'guess' => $guess,
            'correct_digits' => $correctDigits,
            'correct_positions' => $correctPositions
        ]);

        // Win condition
        if ($correctPositions === 4) {
            $gameModel->update($gameId, [
                'status' => 'finished',
                'winner_id' => $userId
            ]);
        } else {
            // Switch turn
            $nextTurn = ($game['player1_id'] == $userId)
                ? $game['player2_id']
                : $game['player1_id'];

            $gameModel->update($gameId, ['turn_player_id' => $nextTurn]);
        }

        return $this->response->setJSON([
            'correct_digits' => $correctDigits,
            'correct_positions' => $correctPositions
        ]);
    }


    public function state($id)
    {
        $db = \Config\Database::connect();
        $gameModel = new GameModel();

        $game = $gameModel->find($id);
        if (!$game) {
            return $this->response->setJSON(['error' => 'Game not found']);
        }

        $userId = session()->get('user_id');

        // 🔒 Access control: once active, only players can access
        if (
            $game['status'] !== 'waiting' &&
            !in_array($userId, [$game['player1_id'], $game['player2_id']])
        ) {
            return $this->response->setJSON(['error' => 'Unauthorized']);
        }

        /**
         * 🔧 AUTO-HEAL LOGIC (runs every poll)
         */
        $bothPlayersJoined =
            !empty($game['player1_id']) &&
            !empty($game['player2_id']);

        $bothCodesSet =
            !empty($game['player1_code']) &&
            !empty($game['player2_code']);

        if (
            $bothPlayersJoined &&
            $bothCodesSet &&
            (
                $game['status'] === 'waiting' ||
                empty($game['turn_player_id'])
            )
        ) {
            // Player 1 always starts
            $gameModel->update($id, [
                'status'          => 'active',
                'turn_player_id'  => $game['player1_id'],
            ]);

            // Refresh game data after fix
            $game = $gameModel->find($id);
        }

        /**
         * 📜 Fetch guess history
         */
        $guesses = $db->table('guesses')
            ->where('game_id', $id)
            ->orderBy('created_at', 'ASC')
            ->get()
            ->getResultArray();

        $history = '';
        foreach ($guesses as $g) {
            $history .= '<p
                data-player="'.$g['player_id'].'"
                data-guess="'.$g['guess'].'"
                data-digits="'.$g['correct_digits'].'"
                data-pos="'.$g['correct_positions'].'">
            </p>';
        }

        /**
         * 🧠 Build message
         */
        if ($game['status'] === 'waiting') {
            $message = 'Waiting for opponent...';
            $result  = null;
        }
        elseif ($game['status'] === 'finished') {
            if ($game['winner_id'] == $userId) {
                $message = 'Game finished - You won';
                $result  = 'win';
            } else {
                $message = 'Game finished - You lost';
                $result  = 'lose';
            }
        }
        else {
            $message = ($game['turn_player_id'] == $userId)
                ? 'Your turn'
                : 'Opponent turn';
            $result = null;
        }

        return $this->response->setJSON([
            'message'      => $message,
            'result'       => $result,
            'guesses_html' => $history
        ]);
    }
    
    public function dashboard()
    {
        $userId = session()->get('user_id');

        if (!$userId) {
            return redirect()->to('/login');
        }

        $gameModel = new GameModel();

        $games = $gameModel
            ->select('games.*, 
                    u1.username as player1_username,
                    u2.username as player2_username')
            ->join('users u1', 'u1.id = games.player1_id')
            ->join('users u2', 'u2.id = games.player2_id', 'left')
            ->groupStart()
                ->where('games.player1_id', $userId)
                ->orWhere('games.player2_id', $userId)
            ->groupEnd()
            ->whereIn('games.status', ['waiting', 'active'])
            ->orderBy('games.last_activity', 'DESC')
            ->findAll();

        return view('game/dashboard', [
            'games' => $games
        ]);
    }

}
