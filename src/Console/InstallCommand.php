<?php

namespace Andruby\Pay\Console;

use Andruby\Pay\PayServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'deep_pay:install {cmd}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the deep-pay package.cmd:\n
                              db install database;\n
                              publish publish resource and config files';

    /**
     * Install directory.
     *
     * @var string
     */
    protected $directory = '';

    protected $cmd = '';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->cmd = $this->argument('cmd');
        $this->info($this->cmd);
        if ($this->cmd == 'db') {
            $this->initDatabase();
        }

        if ($this->cmd == 'publish') {
            $this->initResources();
        }
    }

    /**
     * Create tables and seed it.
     *
     * @return void
     */
    public function initDatabase()
    {
        DB::unprepared($this->laravel['files']->get(__DIR__ . "/stubs/deep_pay.sql"));
    }

    private function initResources()
    {
        $this->call('vendor:publish', ['--provider' => PayServiceProvider::class]);
    }
}
