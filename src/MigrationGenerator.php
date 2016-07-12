<?php
namespace Epigra\PMGen;

use Illuminate\Console\Command;

class MigrationGenerator extends Command
{
    private $migrationFiles;
    private $moduleDirectory;
    private $generatorsDirectory;
    private $fileNamesOrdered = false;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->moduleDirectory  = $this->getModuleDirectory();
        $this->generatorsDirectory = $this->getGeneratorsDirectory();
        \View::addNamespace('pmgenviews', $this->generatorsDirectory);
        
    }

    public function getModuleDirectory()
    {            
        return dirname((new \ReflectionClass(static::class))->getFileName()).'/../';
    }

    public function getGeneratorsDirectory()
    {
        return $this->moduleDirectory.'/Resources/generators';
    }

    private function getMigrationFileName($filename){
        $first_underscore_pos =  strpos($filename, '_');
        $before_first_underscore = substr($filename, 0, $first_underscore_pos);
        if(is_numeric($before_first_underscore)){
            $filename = substr($filename, $first_underscore_pos + 1);
            $this->fileNamesOrdered = true;
        }

        return date('Y_m_d_His').'_'.$filename.'.php';
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->prepareMigrationFiles();
        $this->createMigrations();
    }

    protected function prepareMigrationFiles(){
        $files = glob($this->generatorsDirectory.'/*.php');

        foreach($files as $key => $file) {

            $filename = pathinfo($file,PATHINFO_FILENAME);

            $migrationFileName = $this->getMigrationFileName($filename);

            $this->migrationFiles[$key] = [];
            $this->migrationFiles[$key]['generator'] = $filename;
            $this->migrationFiles[$key]['file_name'] = $migrationFileName;
            $this->migrationFiles[$key]['file_path'] = base_path("database/migrations")."/".$migrationFileName;
            
            if($this->fileNamesOrdered) sleep(1);

        }
    }

    protected function createMigrations(){

        foreach($this->migrationFiles as $key => $mf):
            $mf['output'] = $this->laravel->view->make('pmgenviews::'.$mf['generator'])->render();

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
