<?php

namespace WikidataStats;

use DerAlex\Silex\YamlConfigServiceProvider;
use Knp\Command\Command;
use Knp\Provider\ConsoleServiceProvider;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\TwigServiceProvider;
use WikidataStats\Console\CommandRegistry;

class AppFactory {

	public function newApplication( $configFile, $templatesPath, $wikiDBList ) {
		$app = new Application();

		$app->register( new YamlConfigServiceProvider( $configFile ) );

		// wikis
		$wikis = file( $wikiDBList );

		if ( !is_array( $wikis ) ) {
			throw new \InvalidArgumentException( '$wikIDBList is invalid.' );
		}

		$app['wikis'] = array_map( 'trim', $wikis );

		$app->register(
			new DoctrineServiceProvider(),
			$this->getDoctrineOptions( $app['config'], $app['wikis'] )
		);

		$app->register( new TwigServiceProvider(), array(
			'twig.path' => $templatesPath,
		) );

		return $app;
	}

	public function newConsoleApplication(
		$appName,
		$appVersion,
		$configFile,
		$templatesPath,
		$wikiDBList
	) {
		$app = $this->newApplication( $configFile, $templatesPath, $wikiDBList );

		$app->register( new ConsoleServiceProvider(), array(
			'console.name' => $appName,
			'console.version' => $appVersion,
			'console.project_directory' => __DIR__ . '/Console'
		) );

		$commandRegistry = new CommandRegistry();

		foreach( $commandRegistry->getCommands() as $command ) {
			$app['console']->add( $command );
		}

		return $app;
	}

	private function getDoctrineOptions( array $settings, array $wikis ) {
		if ( !array_key_exists( 'dbs', $settings ) ) {
			throw new \InvalidArgumentException( '$settings are missing "dbs" key.' );
		}

		if ( !array_key_exists( 'wikidatastats', $settings['dbs'] ) ) {
			throw new \InvalidArgumentException( '$settings are missing "wikidatastats" dbs config' );
		}

		$options = $this->getWikiDBOptions( $settings, $wikis );
		$options['wikidatastats'] = $settings['dbs']['wikidatastats'];

		return array(
			'dbs.options' => $options
		);
	}

	private function getWikiDBOptions( array $settings, array $wikis ) {
		$options = array();

		foreach( $wikis as $wiki ) {
			$options[$wiki] = array_merge(
				$settings['dbs']['wiki'],
				array(
					'host' => "$wiki.labsdb",
					'dbname' => $wiki . '_p'
				)
			);
		}

		return $options;
	}

}
