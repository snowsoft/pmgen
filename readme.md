#Epigra Package Migration Generator
Let's you add migration generation support to any of your Laravel supported packages.

##Usage

Let's assume you are developing a package named `newpackage` and you have pre defined templates under your `resources/generators` directory while your migration command is in `console` directory.

```php
namespace YourNamespace\Newpackage\Console;

use Epigra\PMGen\MigrationGenerator;

class MigrationCommand extends MigrationGenerator
{

    protected $signature = 'newpackage:migrate';
    protected $description = 'Creates a migrations for questionable items.';
}
```

now you can register this command from your Package Service Provider by

```php
public function boot(){
	$this->commands('command.yourpackagename.migrate');
}
public function register(){		
	$this->app->singleton('command.yourpackagename.migrate', function ($app) {
		return new \YourNamespace\Newpackage\Console\MigrationCommand();
	});
}
public function provides(){
	return array('command.yourpackagename.migrate');
}

```

in order to make any target directory change from the parent class you can redefine the functions

```php
public function getModuleDirectory()
{            
    return dirname((new \ReflectionClass(static::class))->getFileName()).'/../';
}

public function getGeneratorsDirectory()
{
    return $this->moduleDirectory.'/Resources/generators';
}
```

###Basic logical flow of MigrationGenerator
- Get Modules Directory `Called class directory + .. /` or generally yourvendor/yourpackage/src (assuming you called from src\Console\YourClass)
- Decide Generators Directory ``Modules Directory + '/Resources/generators'`
- Get all files in Generators Directory and
	- Compile related blade files
	- Save the files with current timestamps into `database/migrations` directory if they're not already created. (may need composer dump-autoload)

	
* If you need to order your migrations (for some reason like foreign key references etc) you can add `a number and an underscore` to make them orderable by name in your generators directory (like `0_create_xxx_table.php`, `1_create_yyy_table.php`) So that migrations can also be generated in order.