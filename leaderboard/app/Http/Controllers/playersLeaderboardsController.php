<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\PlayersLeaderboards;
use App\Models\Leaderboard;
use Illuminate\Support\Facades\Redis;

/**
 * @group Leaderboard Players
 *
 */
class playersLeaderboardsController extends Controller
{
    private function validate_leaderboard(int $leaderboard_id) {
        $leaderboard = Leaderboard::find($leaderboard_id);
        if(!$leaderboard) {
            $message =  [
                'message' => 'Leaderboard does not exist'
            ];
            return response()->json($message, 404);
        }

        if(!$leaderboard->status) {
            $message =  [
                'message' => 'Leaderboard is currently disabled'
            ];
            return response()->json($message, 409);
        }

        return $leaderboard;
    }

    private function get_valid_player_rank(int $leaderboard_id, string $player_id) {
        $rank = Redis::zrevrank('ranking_leaderboard_'.$leaderboard_id, $player_id);
        
        if ($rank !== false) {
            return $rank;
        }

        $message =  [
            'message' => 'User not Found In Leaderbord'
        ];
        return response()->json($message, 404);

        
    }

    private function update_or_add_player(object $player, int $leaderboard_id) {
        $player_leaderboard = PlayersLeaderboards::where('leaderboard_id', $leaderboard_id)
                                                    ->where('player_id', $player->player_id)->first();

        if($player_leaderboard) {
            $player_leaderboard->score += $player->score;
            $player_leaderboard->save();
            
        } else {
            $player_leaderboard = PlayersLeaderboards::create(
                [
                'leaderboard_id' => $leaderboard_id,
                'player_id' => $player->player_id,
                'score' => $player->score,
                ]
            );
        }
        Redis::zadd('ranking_leaderboard_'.$leaderboard_id, $player_leaderboard->score, $player_leaderboard->player_id);
    }


    /**
     * Update or Add Players Score in a Leaderboard
     * 
     * Update or add a user score
     * score = new score + old score
     * 
     * @pathParam lederboard_id int required
     * The leaderboard id
     * 
     * @bodyParam array List of users to add or update
     * [
     *  {
     *      "player_id": string,
     *      "score": int
     *  }
     * ]
     * 
     * 
     * @response 201
     * 
     * @response 400 {
     *  "message": "Error validating data"
     * }
     * @response 409 {
     *  "message": "Leaderboard is currently disabled"
     * }
     * @response 404 {
     *  "message": "Leaderboard does not exist"
     * }
     */
    public function save(Request $request, int $leaderboard_id) {

        $validator = Validator::make($request->all(), [
            '*.player_id' => 'required|string|max:50',
            '*.score' => 'required|int'
        ]);

        if($validator->fails()) {
            $message =  [
                'message' => 'Error validating data',
                'errors' => $validator->errors()
            ];
            return response()->json($message, 400);
        }

        $leaderboard = $this->validate_leaderboard($leaderboard_id);

        if(!($leaderboard instanceof Leaderboard)) {
            return $leaderboard;
        }

        $players = $request->all();

        foreach($players as $player){

            $this->update_or_add_player((object)$player, $leaderboard_id);
        }

        return response()->json(null, 204);
    }


    /**
     * Get users leaderboard
     * 
     * Get leaderboard user rank paginated
     * 
     * @pathParam lederboard_id int required
     * The leaderboard id
     * 
     * @queryParam offset int optional
     * The start rank
     * 
     * @queryParam limit int optional
     * The number of users after the offset value
     * 
     * @bodyParam score int required
     * The players score
     * 
     * 
     * @response 200 {
     *  "leaderboard_name": string
     *  "leaderboard_id": int,
     *  "total_players: int,
     *  "leaderboard_ranks": [
     *      {
     *           "rank": int,
     *           "player_id": string,
     *           "score": int
     *      },...
     *  ]
     * }
     * 
     * @response 409 {
     *  "message": "Leaderboard is currently disabled"
     * }
     * @response 404 {
     *  "message": "Leaderboard does not exist"
     * }
     */
    public function get_ranks(Request $request, int $leaderboard_id) {

        $offset = $request->query('offset', 1);
        $limit = $request->query('limit', 1000);

        if($offset <= 0) {
            $offset = 1;
        }

        $offset -= 1;
        $limit = ($limit -1) + $offset;

        $leaderboard = $this->validate_leaderboard($leaderboard_id);

        if(!($leaderboard instanceof Leaderboard)) {
            return $leaderboard;
        }
        
        $ranks = Redis::zrevrange('ranking_leaderboard_'.$leaderboard_id, $offset, $limit, ['withscores' => true]);
        
        if (!$ranks) {
            return response()->json([], 200);
        }

        $rank = $offset + 1;
        foreach ($ranks as $player => $score) {
            $rankedResults[] = ['rank' => $rank, 'player_id' => $player, 'score' => $score];
            $rank++;
        }

        $totalplayers = Redis::zCard('ranking_leaderboard_'.$leaderboard_id);
        $message =  [
            'leaderbord_name' => $leaderboard->name,
            'leaderbord_id' => $leaderboard->id,
            'leaderboard_ranks' => $rankedResults,
            'total_players' => $totalplayers
        ];

        return response()->json($message, 200);

    }


    /**
     * Get player rank
     * 
     * Get a leaderboard player rank
     * 
     * @pathParam lederboard_id int required
     * The leaderboard id
     * 
     * @pathParam player_id string required
     * The player user id
     * 
     * @response 200 {
     * "leaderbord_name": string,
     * "leaderbord_id": int,
     * "plyer_rank": int,
     * "player_score": int
     * }
     * 
     * @response 409 {
     *  "message": "Leaderboard is currently disabled"
     * }
     * @response 404 {
     *  "message": "Leaderboard does not exist"
     * }
     */
    public function get_player_rank(int $leaderboard_id, string $player_id){

        $leaderboard = $this->validate_leaderboard($leaderboard_id);

        if(!($leaderboard instanceof Leaderboard)) {
            return $leaderboard;
        }

        $rank = $this->get_valid_player_rank($leaderboard_id, $player_id);

        if(!is_int($rank)) {
            return $rank;
        }

        $rank = $rank !== null ? $rank + 1 : 0;

        $score = Redis::zscore('ranking_leaderboard_'.$leaderboard_id, $player_id);
 
        $message =  [
            'leaderbord_name' => $leaderboard->name,
            'leaderbord_id' => $leaderboard->id,
            'plyer_rank' => $rank,
            'player_score' => $score
        ];

        return response()->json($message, 200);
    }


    /**
     * Get ranks arounf user
     * 
     * Get N users around X player in a leaderboard (where X is the middle rank)
     * 
     * @pathParam lederboard_id int required
     * The leaderboard id
     * 
     * @pathParam player_id string required
     * The player username identifier
     * 
     * @pathParam records_around string required
     * The number of users arounf the the X player
     * 
     * @response 200 {
     *  "leaderboard_name": string
     *  "leaderboard_id": int,
     *  "total_players: int,
     *  "leaderboard_ranks": [
     *      {
     *           "rank": int,
     *           "player_id": string,
     *           "score": int
     *      },...
     *  ]
     * }
     * 
     * @response 409 {
     *  "message": "Leaderboard is currently disabled"
     * }
     * @response 404 {
     *  "message": "Leaderboard does not exist"
     * }
     */
    public function get_ranks_around_player(int $leaderboard_id, string $player_id, int $records_around) {

        $leaderboard = $this->validate_leaderboard($leaderboard_id);

        if(!($leaderboard instanceof Leaderboard)) {
            return $leaderboard;
        }

        if ($records_around <= 0 ){
            return response()->json([], 200);
        }

        $rank = $this->get_valid_player_rank($leaderboard_id, $player_id);

        if(!is_int($rank)) {
            return $rank;
        }

        $split_number = floor($records_around / 2);
        $offset = $rank - $split_number;

        $residual = 0;
        if($offset < 0 ) {
            // Move the rest of the user to the end
            $residual = abs($offset);
            $offset = 0;
        }
        $limit = $rank + $split_number;
        if (($records_around % 2) != 0) {
            $limit += 1;
        }

        $limit += $residual;

        $ranks = Redis::zrevrange('ranking_leaderboard_'.$leaderboard_id, $offset, $limit, ['withscores' => true]);
        
        if (!$ranks) {
            return response()->json([], 200);
        }

        $rank = $offset + 1;
        foreach ($ranks as $player => $score) {
            $rankedResults[] = ['rank' => $rank, 'player_id' => $player, 'score' => $score];
            $rank++;
        }

        $message =  [
            'leaderbord_name' => $leaderboard->name,
            'leaderbord_id' => $leaderboard->id,
            'leaderboard_ranks' => $rankedResults
        ];

        return response()->json($message, 200);
        
    }
}