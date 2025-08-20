<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ServeWithFrontend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serve:full 
                            {--host=127.0.0.1 : The host address to serve the application on}
                            {--port=8000 : The port to serve the application on}
                            {--frontend-port=5173 : The port for the frontend development server}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start both Laravel backend server and frontend development server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $host = $this->option('host');
        $port = $this->option('port');
        $frontendPort = $this->option('frontend-port');

        $this->info('🚀 Starting SavedFeast Development Servers...');
        $this->info('Backend: http://' . $host . ':' . $port);
        $this->info('Frontend: http://' . $host . ':' . $frontendPort);
        $this->info('API: http://' . $host . ':' . $port . '/api');
        $this->newLine();

        // Check if package.json exists (frontend dependencies installed)
        if (!file_exists(base_path('package.json'))) {
            $this->error('Frontend dependencies not found. Please run: npm install');
            return 1;
        }

        // Check if node_modules exists
        if (!is_dir(base_path('node_modules'))) {
            $this->warn('Frontend dependencies not installed. Installing now...');
            $this->call('npm:install');
        }

        // Check if concurrently is installed
        $packageJson = json_decode(file_get_contents(base_path('package.json')), true);
        $hasConcurrently = isset($packageJson['devDependencies']['concurrently']) || 
                          isset($packageJson['dependencies']['concurrently']);

        if (!$hasConcurrently) {
            $this->warn('concurrently package not found. Installing now...');
            $this->call('npm:install', ['--save-dev', 'concurrently']);
        }

        $this->info('Starting both servers using npm script...');
        
        // Use the npm script which uses concurrently
        $process = new Process(['npm', 'run', 'serve:full'], base_path());
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        
        // Run the process with real-time output
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->error($buffer);
            } else {
                $this->line($buffer);
            }
        });

        return $process->getExitCode();
    }
}
