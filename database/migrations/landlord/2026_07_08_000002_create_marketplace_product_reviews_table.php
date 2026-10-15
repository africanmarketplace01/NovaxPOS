<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        $conn = Schema::connection('saleprosaas_landlord');

        if (!$conn->hasTable('marketplace_product_reviews')) {
            $conn->create('marketplace_product_reviews', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('listing_id')->index();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('tenant_id')->nullable()->index();
                $table->string('reviewer_name');
                $table->string('reviewer_email')->nullable();
                $table->unsignedTinyInteger('rating');          // 1..5
                $table->string('title')->nullable();
                $table->text('comment')->nullable();
                $table->boolean('is_verified')->default(false);  // verified purchase
                $table->boolean('is_approved')->default(true);   // shown publicly
                $table->timestamps();

                $table->index(['listing_id', 'is_approved']);
            });
        }

        // Store all product image URLs so the gallery can show multiple images
        if ($conn->hasTable('marketplace_listings')
            && !$conn->hasColumn('marketplace_listings', 'image_urls')) {
            $conn->table('marketplace_listings', function (Blueprint $table) {
                $table->text('image_urls')->nullable()->after('image_url'); // JSON array
            });
        }
    }

    public function down(): void
    {
        $conn = Schema::connection('saleprosaas_landlord');
        $conn->dropIfExists('marketplace_product_reviews');
        if ($conn->hasColumn('marketplace_listings', 'image_urls')) {
            $conn->table('marketplace_listings', function (Blueprint $table) {
                $table->dropColumn('image_urls');
            });
        }
    }
};
