<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('follow.table_name', 'follows');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('follower_id');
            $table->string('follower_type', 200);
            $table->unsignedBigInteger('followable_id');
            $table->string('followable_type', 200);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['follower_id', 'follower_type', 'followable_id', 'followable_type'], 'follows_unique');
            $table->index(['followable_type', 'followable_id'], 'follows_followable_idx');
            $table->index(['follower_type', 'follower_id'], 'follows_follower_idx');
            $table->index('created_at', 'follows_created_idx');
        });
    }

    public function down(): void
    {
        $tableName = config('follow.table_name', 'follows');
        Schema::dropIfExists($tableName);
    }
};
