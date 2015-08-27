<?php

namespace WikidataStats\Console\Commands;

use DateTime;
use Doctrine\DBAL\Schema\Schema;
use Knp\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatsUpdateCommand extends Command {

	protected function configure() {
		$this->setName( 'stats-update' )
			->setDescription( 'Wikidata stats update' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$app = $this->getSilexApplication();

		$statsDb = $app['dbs']['wikidatastats'];
		$this->initDatabase( $statsDb );

		// @todo keep snapshots of stats over time
		$statsDb->query( 'TRUNCATE TABLE stats_usagetracking' );

		foreach( $app['wikis'] as $wiki ) {
			$stats = $this->updateStats( $app, $wiki );
			$output->writeln( "Updating stats for $wiki" );
		}
	}

	private function getStats( $app, $wiki ) {
		$db = $app['dbs'][$wiki];

		$sql = 'SELECT eu_aspect, count(*) as count '
					. 'FROM wbc_entity_usage '
					. 'GROUP BY eu_aspect '
					. 'ORDER BY eu_aspect';

		$stats = array();

		foreach( $db->fetchAll( $sql ) as $row ) {
			$aspect = $row['eu_aspect'];
			$stats[$aspect] = (int)$row['count'];
		}

		$stats['total'] = array_sum( $stats );

		return $stats;
	}

	private function updateStats( $app, $wiki ) {
		$stats = $this->getStats( $app, $wiki );

		$statsDb = $app['dbs']['wikidatastats'];

		foreach( $stats as $aspect => $count ) {
			$statsDb->insert(
				'stats_usagetracking',
				array(
					'site_id' => $wiki,
					'aspect' => $aspect,
					'count' => $count
				)
			);
		}

		return $stats;
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
