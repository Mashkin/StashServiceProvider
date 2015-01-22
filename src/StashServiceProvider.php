<?php

/*
 * This file is part of StashServiceProvider
 *
 * (c) Mashkin <git@mashkin.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mashkin\Silex\Provider\StashServiceProvider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Stash\Pool;

class StashServiceProvider implements ServiceProviderInterface
{
	public function register(Application $app)
	{
		if(!isset($app['stash.driver.class.default'])) {
			$app['stash.driver.default_class'] = 'Ephemeral';
		}
		
		if(!isset($app['stash.default_options'])) {
			$app['stash.default_options'] = array();
		}
		
		$app['stashes.options.initializer'] = $app->protect(function () use ($app) {
			static $initialized = false;
			
			if($initialized) {
				return;
			}
			
			$initialized = true;
			
			if(!isset($app['stashes.options'])) {
				$app['stashes.options'] = array(
					'default' => isset($app['stash.options']) ? $app['stash.options'] : array()
				);
				$app['stashes.driver.class'] = array();
			}
				
			$tmp = $app['stashes.options'];
			foreach ($tmp as $name => &$options) {
				$options = array_replace($app['stash.default_options'], $options);
				
				if(!isset($app['stashes.driver.class'][$name])) {
					$app['stashes.driver.class'][$name] = $app['stash.driver.default_class'];
				}
				
				if(!isset($app['stashes.default'])) {
					$app['stashes.default'] = $name;
				}
			}
			$app['stashes.options'] = $tmp;
		});
		
		$app['stashes.driver'] = $app->share(function ($app) {
			$app['stashes.options.initializer']();
			
			$drivers = new \Pimple();
			foreach ($app['stashes.options'] as $name => $options) {
				$drivers[$name] = $app->share(function ($app) use ($name) {
					$class = sprintf('\\Stash\\Driver\\%s', $app['stashes.driver.class'][$name]);
					$options = $app['stashes.options'][$name];
					
					$driver = new $class;
					$driver->setOptions($options);
					return $driver;
				});
			}
			
			return $drivers;
		});
		
		$app['stashes'] = $app->share(function ($app) {
			$stashes = new \Pimple();
			foreach ($app['stashes.driver'] as $name => $driver) {
				if($app['stashes.default'] === $name) {
					$driver = $app['stash.driver'];
				}
				
				$stashes[$name] = $stashes->share(function ($stashes) use ($driver) {
					return new Pool($driver);
				});
			}
			
			return $stashes;
		});
		
		$app['stash'] = $app->share(function ($app) {
			$stashes = $app['stashes'];
			
			return $stashes[$app['stashes.default']];
		});
		
		$app['stash.driver'] = $app->share(function ($app) {
			$stashes = $app['stashes.driver'];
			
			return $stashes[$app['stashes.default']];
		});
	}
    
	public function boot(Application $app)
	{
		
	}
}
