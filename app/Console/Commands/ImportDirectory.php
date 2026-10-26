<?php

namespace App\Console\Commands;

use App\Services\DirectoryImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('caresenda:import {path? : Path to a CareSenda directory export (.json or .json.gz)}')]
#[Description('Import the CareSenda facility and service directory')]
class ImportDirectory extends Command
{
    public function handle(DirectoryImporter $importer): int
    {
        $path = $this->argument('path') ?? config('caresenda.dataset');
        $started = microtime(true);
        $result = $importer->import($path);

        $this->components->info(sprintf('Imported in %.2fs', microtime(true) - $started));
        $this->components->twoColumnDetail('Areas', (string) $result['areas']);
        $this->components->twoColumnDetail('Facilities', (string) $result['facilities']);
        $this->components->twoColumnDetail('Placed on map by area', "{$result['geocoded']} (rest use city centre)");
        $this->components->twoColumnDetail('Services (tests + specialists)', (string) $result['services']);

        return self::SUCCESS;
    }
}
