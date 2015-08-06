# StashServiceProvider
Silex ServiceProvider integrating the Stash caching component

## Usage

    // Register the Mashkin\Silex\Provider\StashServiceProvider\ServiceProvider
    $app->register(new StashServiceProvider(), array(
      // Set driver class for drivers
    	'stashes.driver.class' => array('default' => 'Composite')
    ));
    
    // Set options for default driver
    $app['stash.options'] = $app->share(function() use ($app) {
    	$drivers = array();
    	if(function_exists('apc_fetch')) {
    		$drivers[] = $driver = new \Stash\Driver\Apc();
    		$driver->setOptions(array(
    			'ttl' => 24*60*60,
    			'namespace' => sha1($app['name'])
    		));
    	} else {
    		$drivers[] = $driver = new \Stash\Driver\FileSystem();
    		$driver->setOptions(array(
    			'path' => __DIR__ . '/cache/stash/',
    			'dirSplit' => 2,
    			'filePermissions' => 0666,
    			'dirPermissions' => 0777
    		));
    	}
    	return array('drivers' => $drivers);
    });
