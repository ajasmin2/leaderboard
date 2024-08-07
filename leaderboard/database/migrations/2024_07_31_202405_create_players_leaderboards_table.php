<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('players_leaderboards', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->lenght(11);
            $table->integer('leaderboard_id')->lenght(11);
            $table->string('player_id', 50);
            $table->integer('score')->default(0);
            $table->timestamps();
            $table->index(['leaderboard_id', 'player_id']);
            $table->unique(['leaderboard_id', 'player_id']);
            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players_leaderboards');
    }
};
