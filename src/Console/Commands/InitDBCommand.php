<?php

namespace WikidataStats\Console\Commands;

use Doctrine\DBAL\Schema\Schema;
use Knp\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitDBCommand extends Command {

	protected function configure() {
		$this->setName( 'init-db' )
			->setDescription( 'Inititialize database' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$app = $this->getSilexApplication();

		$statsDb = $app['dbs']['wikidatastats'];
		$this->initDatabase( $statsDb );
	}

	private function initDatabase( $db ) {
		$schemaManager = $db->getSchemaManager();
		$tables = $schemaManager->listTables();

		if ( $tables === array() ) {
			$schema = new Schema();

			$table = $schema->createTable( 'stats_usagetracking' );

			$table->addColumn( 'id', 'integer', array( 'unsigned' => true, 'autoincrement' => true ) );
			$table->addColumn( 'site_id', 'string', array( 'length' => 64 ) );
			$table->addColumn( 'aspect', 'string', array( 'length' => 16 ) );
			$table->addColumn( 'count', 'integer' );

			$table->setPrimaryKey( array( 'id' ) );
			$table->addIndex( array( 'site_id' ) );

			$sqlCommands = $schema->toSql( $db->getDatabasePlatform() );

			foreach( $sqlCommands as $command ) {
				$db->query( $command );
			}
		}
	}

}
