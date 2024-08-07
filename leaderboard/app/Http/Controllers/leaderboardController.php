<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Leaderboard;

/**
 * @group Leaderboard admin
 *
 */
class leaderboardController extends Controller
{
    /**
     * Get leaderboards list
     *
     * Enpoint get the leaderboard list
     * 
     * 
     * @response 200 [{
     *  "id": "Leaderboard id",
     *  "name": "Leaderboard name",
     *  "status": "Leaderboard status",
     * }...]
     * 
     */
    public function get_list() {
        $leaderboards = Leaderboard::all();
        return response()->json($leaderboards, 200);
    }

    /**
     * Create Leaderboard
     *
     * Enpoint to create leaderboard
     * 
     * @bodyParam name string required
     * The leaderboard name
     * @response 201 {
     *  "name": "Leaderboard name"
     * }
     * 
     * @response 409 {
     *  "message": "Leaderbord name already exist"
     * }
     * 
     */
    public function save(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100'
        ]);

        if($validator->fails()) {
            $message =  [
                'message' => 'Error validating data',
                'errors' => $validator->errors()
            ];
            return response()->json($message, 400);
        }

        if(Leaderboard::where('name', $request->name)->first()) {
            $message =  [
                'message' => 'Leaderbord name already exist'
            ];
            return response()->json($message, 409);
        }

        $leaderboad = Leaderboard::create(['name' => $request->name]);

        if(!$leaderboad) {
            $message =  [
                'message' => 'Error creating leaderboard'
            ];
            return response()->json($message, 500);
        }

        $message = ['leaderboard' => $leaderboad];
        return response()->json($message, 201);
    }


    /**
     * Enable Leaderboard
     *
     * Enpoint to enable a leaderboard
     * 
     * @pathParam lederboard_id int required
     * The leaderboard lederboard_id
     * 
     * @response 200 {
     *  "id": "Leaderboard id",
     *  "name": "Leaderboard name",
     *  "status": "Leaderboard status"
     * }
     * 
     * @response 404 {
     *  "message": "Leaderbord does not exist"
     * }
     * 
     */
    public function enable(int $lederboard_id) {
        
        $leaderboad = Leaderboard::find($lederboard_id);

        if(!$leaderboad) {
            $message =  [
                'message' => 'Leaderbord does not exist'
            ];
            return response()->json($message, 404);
        }

        $leaderboad->status = true;
        $leaderboad->save();

        $message =  [
            'leaderboard' => $leaderboad
        ];

        return response()->json($message, 200);
    }

    /**
     * Disable Leaderboard
     *
     * Enpoint to disable a leaderboard
     * 
     * @pathParam lederboard_id int required
     * The leaderboard lederboard_id
     * 
     * @response 404 {
     *  "message": "The Leaderbord to delete does not exist"
     * }
     * 
     * @response 409 {
     *  "message": "The Leaderbord is already disabled"
     * }
     * 
     */
    public function delete($lederboard_id) {
        $leaderboad = Leaderboard::find($lederboard_id);

        if (!$leaderboad) {
            $message =  [
                'message' => 'The Leaderbord to delete does not exist'
            ];
            return response()->json($message, 404);
        }

        if (!$leaderboad->status) {
            $message =  [
                'message' => 'The Leaderbord is already disabled'
            ];
            return response()->json($message, 409);
        }

        $leaderboad->status = false;
        $leaderboad->save();
        
        return response()->json(null, 204);
    }
}
