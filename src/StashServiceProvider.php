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
		if (!isset($app['stash.driver.default_class'])) {
			$app['stash.driver.default_class'] = 'Ephemeral';
		}
		
		if (!isset($app['stash.default_options'])) {
			$app['stash.default_options'] = array();
		}
		
		$app['stashes.options_initializer'] = $app->protect(function () use ($app) {
			if (!isset($app['stashes.driver.class'])) {
				$app['stashes.driver.class'] = array();
			}
			
			if (!isset($app['stashes.options'])) {
				$app['stashes.options'] = array();
			}
			
			$tmp = $app['stashes.options'];
			
			if (isset($app['stash.options'])) {
				$tmp['default'] = $app['stash.options'];
			}
			
			foreach ($tmp as $name => &$options) {
				$options = array_replace($app['stash.default_options'], $options);
				
				if (!isset($app['stashes.driver.class'][$name])) {
					$app['stashes.driver.class'][$name] = $app['stash.driver.default_class'];
				}
				
				if (!isset($app['stashes.default'])) {
					$app['stashes.default'] = $name;
				}
			}
			
			$app['stashes.options'] = $tmp;
		});
		
		$app['stashes.driver'] = $app->share(function ($app) {
			$app['stashes.options_initializer']();
			$drivers = new \Pimple();
			foreach ($app['stashes.options'] as $name => $options) {
				$drivers[$name] = $drivers->share(function ($drivers) use ($app, $name, $options) {
					$class = sprintf('\\Stash\\Driver\\%s', $app['stashes.driver.class'][$name]);
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
				if ($app['stashes.default'] === $name) {
					$driver = $app['stash.driver'];
				}
				
				$stashes[$name] = $stashes->share(function ($stashes) use ($driver) {
					return new Pool($driver);
				});
			}
			
			return $stashes;
		});
		
		$app['stash.driver'] = function ($app) {
			$drivers = $app['stashes.driver'];
			return $drivers[$app['stashes.default']];
		};
		
		$app['stash'] = function ($app) {
			$stashes = $app['stashes'];
			return $stashes[$app['stashes.default']];
		};
	}
    
	public function boot(Application $app)
	{
		
	}
}
