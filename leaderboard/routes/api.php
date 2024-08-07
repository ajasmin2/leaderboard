<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\leaderboardController;
use App\Http\Controllers\playersLeaderboardsController;
use App\Http\Controllers\userController;


Route::get('/index', function(){
    return response()->json([ 'service' => 'Leaderboard', 'version' => "1.0.0" ]);
});

// This method will create and admin user in order create token to handle authorized api request.
Route::post('/admin/users', [userController::class, 'create']);

// Use this method in order to create a new admin Bearer token
Route::post('/admin/users/token', [userController::class, 'login']);

// Create Leaderboard
Route::post('/leaderboards', [leaderboardController::class, 'save'])->middleware('auth:sanctum');

// Get Leaderboards List
Route::get('/leaderboards', [leaderboardController::class, 'get_list'])->middleware('auth:sanctum');

// Leaderbord Delete (soft delete)
Route::delete('/leaderboards/{leaderboard_id}',  [leaderboardController::class, 'delete'])->middleware('auth:sanctum');

// Enable Leaderboard
Route::patch('/leaderboards/{leaderboard_id}/enable',  [leaderboardController::class, 'enable'])->middleware('auth:sanctum');

// Add Or Update Users' Score In A Leaderboard
Route::post('/leaderboards/{leaderboard_id}/players', [playersLeaderboardsController::class, 'save'])->middleware('auth:sanctum');

// Get Leaderbord With Pagination offet=start, limit=limit of the items
Route::get('/leaderboards/{leaderboard_id}/players', [playersLeaderboardsController::class, 'get_ranks'])->middleware('auth:sanctum');

// Get User Rank And Score
Route::get('/leaderboards/{leaderboard_id}/players/{player_id}/rank', [playersLeaderboardsController::class, 'get_player_rank'])->middleware('auth:sanctum');

// Get N Users Around X User In A Leaderbord
Route::get('/leaderboards/{leaderboard_id}/players/{player_id}/rank/around/{records_around}', [playersLeaderboardsController::class, 'get_ranks_around_player'])->middleware('auth:sanctum');




