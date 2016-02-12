# StashServiceProvider
Silex ServiceProvider integrating the Stash caching component

## Usage

    // Register the Mashkin\Silex\Provider\StashServiceProvider\ServiceProvider
    $app->register(new StashServiceProvider());
    
    // Set options for default driver
    $app['stashes.options'] = array();
    $app['stashes.driver.class'] = array();
    
    if(function_exists('apc_fetch')) {
	    $app['stashes.driver.class']['default'] = 'Apc';
		$app['stashes.options']['default'] = array(
			'ttl' => 24*60*60,
			'namespace' => sha1($app['name'])
		);
	} else {
	    $app['stashes.driver.class']['default'] = 'FileSystem';
		$app['stashes.options']['default'] = array(
			'path' => __DIR__ . '/cache/stash/',
			'dirSplit' => 2,
			'filePermissions' => 0666,
			'dirPermissions' => 0777
		);
	}
	
	// ...
	
	
	$app['stashes']['default'] instanceof Stash\Pool    // true
	$app['stash'] === $app['stashes']['default']        // true
	
	
