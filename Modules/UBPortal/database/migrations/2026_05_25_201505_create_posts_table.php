<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\UBPortal\Enums\PostStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('content');
            $table->string('cover_image')->nullable();

            // Foreign Key linking back to the Admin/Author who created it
            $table->foreignUuid('author_id')->constrained('users')->onDelete('cascade');

            // Status column mapped natively to your PHP PostStatus Enum ('draft')
            $table->string('status')->default(PostStatus::DRAFT->value);

            // Foreign Key linking to the Marketing user who approved it (Nullable at creation)
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            // Created_at and Updated_at timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
