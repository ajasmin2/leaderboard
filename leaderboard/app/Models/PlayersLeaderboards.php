<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayersLeaderboards extends Model
{
    use HasFactory;

    protected $table = 'players_leaderboards';

    protected $fillable = [
        'leaderboard_id',
        'player_id',
        'score'
    ];
}
