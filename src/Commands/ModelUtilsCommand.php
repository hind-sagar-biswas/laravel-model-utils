<?php

namespace HindBiswas\ModelUtils\Commands;

use Illuminate\Console\Command;

class ModelUtilsCommand extends Command
{
    public $signature = 'laravel-model-utils';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
