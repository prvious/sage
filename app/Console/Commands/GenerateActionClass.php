<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class GenerateActionClass extends GeneratorCommand
{
    protected $signature = 'make:action {name}';

    protected function getStub()
    {
        return base_path('stubs/action.stub');
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Actions';
    }

    public function handle()
    {
        $result = parent::handle();

        return $result;
    }
}
