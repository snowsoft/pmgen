<?php
namespace Epigra\PMG;

use Illuminate\Console\Command;

class PMGMigration extends Command
{
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $moduleDirectory  = substr(__DIR__, 0, -8);
        $generatorsDirectory = $moduleDirectory.'/Resources/generators';
        \View::addNamespace('generator', $generatorsDirectory);

        $files = glob($generatorsDirectory.'/*.php');
        foreach($files as $key => $file) {

            $filename = pathinfo($file,PATHINFO_FILENAME);
            $migrationFileName = date('Y_m_d_His').'_'.$filename.'.php';

            $this->migrationFiles[$key] = [];
            $this->migrationFiles[$key]['generator'] = $filename;
            $this->migrationFiles[$key]['file_name'] = $migrationFileName;
            $this->migrationFiles[$key]['file_path'] = base_path("/database/migrations")."/".$migrationFileName;

        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->createMigrations();
    }

    protected function createMigrations(){

        foreach($this->migrationFiles as $key => $mf):
            $mf['output'] = $this->laravel->view->make('generator::'.$mf['generator'])->render();

            if (preg_match('/class[\s\n]+([a-zA-Z0-9_]+)[\s\na-zA-Z0-9_]+\{/', $mf['output'], $matches)) {
                $mf['class'] = $matches[1];
            } 

            if (!class_exists($mf['class']) && $fs = fopen($mf['file_path'], 'x')) {
                $this->line("Creating migration ". $mf['file_name']);
                fwrite($fs, $mf['output']);
                fclose($fs);
            }
            else{
                $this->error("Error creating file ". $mf['file_name']. ' file is not writable or class already exists');
            }
        endforeach;
    }

}
