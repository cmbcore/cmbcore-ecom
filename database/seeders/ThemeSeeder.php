<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Theme\ThemeManager;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        app(ThemeManager::class)->syncInstalledThemes();
    }
}
