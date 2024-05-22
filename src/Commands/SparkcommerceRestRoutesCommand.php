<?php

namespace Rahat1994\SparkcommerceRestRoutes\Commands;

use Illuminate\Console\Command;

class SparkcommerceRestRoutesCommand extends Command
{
    public $signature = 'sparkcommerce-rest-routes';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
