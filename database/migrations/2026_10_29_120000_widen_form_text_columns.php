<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->modify('school_activities', 'excerpt', 'TEXT NULL');
        $this->modify('website_settings', 'address', 'TEXT NULL');

        foreach ([
            ['school_activities', 'image_path'],
            ['clinical_departments', 'cover_image'],
            ['clinical_services', 'cover_image'],
            ['leadership_team', 'profile_image'],
            ['partners', 'logo_path'],
            ['facilities', 'image_path'],
            ['home_sliders', 'image_path'],
            ['media_gallery_items', 'image_path'],
            ['media_gallery_items', 'video_url'],
            ['website_settings', 'logo_path'],
            ['website_settings', 'home_background_image_path'],
            ['website_settings', 'cta_background_image_path'],
            ['page_headers', 'image_path'],
        ] as [$table, $column]) {
            $this->modify($table, $column, 'VARCHAR(500) NULL');
        }

        $this->modify('school_activity_images', 'image_path', 'VARCHAR(500) NOT NULL');
    }

    public function down(): void
    {
        $this->modify('school_activities', 'excerpt', 'VARCHAR(255) NULL');
        $this->modify('website_settings', 'address', 'VARCHAR(255) NULL');

        foreach ([
            ['school_activities', 'image_path'],
            ['clinical_departments', 'cover_image'],
            ['clinical_services', 'cover_image'],
            ['leadership_team', 'profile_image'],
            ['partners', 'logo_path'],
            ['facilities', 'image_path'],
            ['home_sliders', 'image_path'],
            ['media_gallery_items', 'image_path'],
            ['media_gallery_items', 'video_url'],
            ['website_settings', 'logo_path'],
            ['website_settings', 'home_background_image_path'],
            ['website_settings', 'cta_background_image_path'],
            ['page_headers', 'image_path'],
            ['school_activity_images', 'image_path'],
        ] as [$table, $column]) {
            $definition = $table === 'school_activity_images' && $column === 'image_path'
                ? 'VARCHAR(255) NOT NULL'
                : 'VARCHAR(255) NULL';
            $this->modify($table, $column, $definition);
        }
    }

    private function modify(string $table, string $column, string $definition): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` {$definition}");
    }
};
