<?php

namespace App\Console\Commands;

use App\Services\MenuImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class SyncMenu extends Command
{
    protected $signature = 'menu:sync {--url= : Override the menu feed URL}';

    protected $description = 'Sync the menu from the genz-admin public feed into the storefront tables (trusted price copy for checkout re-pricing).';

    public function handle(MenuImporter $importer): int
    {
        $url = $this->option('url') ?: config('genz.admin_menu_url') ?: config('genz.rms_menu_url');

        if (! $url) {
            $this->error('No menu feed URL configured. Set ADMIN_MENU_URL in .env or pass --url=');

            return self::FAILURE;
        }

        $this->info("Fetching menu from {$url} …");

        try {
            $response = Http::timeout(20)->acceptJson()->get($url);
        } catch (Throwable $e) {
            $this->error('Request to RMS failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->error('RMS feed returned HTTP '.$response->status());

            return self::FAILURE;
        }

        $menu = $response->json();
        if (! is_array($menu) || ! isset($menu['categories']) || ! is_array($menu['categories'])) {
            $this->error('Unexpected feed format: missing "categories".');

            return self::FAILURE;
        }

        $result = $importer->import($menu);

        $this->info(
            "Synced: {$result['categories']} categories, {$result['items']} items, "
            ."{$result['variants']} variants, {$result['deals']} deals."
        );

        return self::SUCCESS;
    }
}
