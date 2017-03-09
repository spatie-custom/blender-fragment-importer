<?php

namespace Spatie\FragmentImporter\Commands;

use Artisan;
use Cache;
use Illuminate\Console\Command;
use Spatie\FragmentImporter\Importer;

class ImportFragments extends Command
{
    /** @var string */
    protected $signature = 'fragments:import {--update : Update the existing fragments}';

    /** @var string */
    protected $description = 'Import fragments from the excel file.';

    public function handle(Importer $importer)
    {
        if ($this->option('update')) {
            Artisan::call('backup:run', ['--only-db' => true]);
            $this->comment('Database dumped');
            $importer->updateExistingFragments();
        }

        Cache::flush();
        $this->comment('Cache cleared');

        $latestExcelFile = $this->getLatestFragmentExcel();

        $this->comment("Importing fragments from {$latestExcelFile}");
        $importer->import($latestExcelFile);

        $this->info($this->getImportMessage($importer));
    }

    public function getLatestFragmentExcel() : string
    {
        $directory = database_path('seeds/data');

        $files = collect(glob("{$directory}/fragments*.xlsx"));

        if ($files->isEmpty()) {
            throw new \Exception("could not find any fragment files in directory `{$directory}`");
        }

        return $files->last();
    }

    protected function getImportMessage($importer): string
    {
        $newFragments = $importer->getNewFragments()->implode('key', ', ');

        if (! $newFragments && ! $this->option('update')) {
            return 'No new fragments imported.';
        }

        $message = 'Imported only ';

        if ($this->option('update')) {
            $message = 'Imported all fragments' . ($newFragments ? ' including ' : '.');
        }

        if ($newFragments) {
            $message .= "the following new fragments: {$newFragments}";
            return $message;
        }

        return $message;
    }
}
