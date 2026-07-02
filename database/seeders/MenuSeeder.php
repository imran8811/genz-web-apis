<?php

namespace Database\Seeders;

use App\Services\MenuImporter;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Bootstrap the menu from the bundled menu.json snapshot.
     * Live updates come from the RMS via `php artisan menu:sync`.
     */
    public function run(): void
    {
        $path = database_path('data/menu.json');
        $menu = json_decode(file_get_contents($path), true);

        $result = app(MenuImporter::class)->import($menu);

        $this->command?->info(
            "Menu imported: {$result['categories']} categories, {$result['items']} items, "
            ."{$result['variants']} variants, {$result['deals']} deals."
        );
    }
}
