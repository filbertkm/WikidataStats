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

	private function hasStatsForDate( $app, $wiki, $aspect ) {
		$db = $app['dbs']['wikidatastats'];

		$sql = 'SELECT site_id from stats_usagetracking '
			. "WHERE site_id = '$wiki' and aspect = '$aspect'"
			. " AND DATE(FROM_UNIXTIME(timestamp)) = '2015-10-06' "
			. " LIMIT 1";

		foreach( $db->fetchAll( $sql ) as $row ) {
			return true;
		}

		return false;
	}

	private function updateStats( $app, $wiki ) {
		$stats = $this->getStats( $app, $wiki );

		$statsDb = $app['dbs']['wikidatastats'];
		$time = time();

		foreach( $stats as $aspect => $count ) {
			if ( !$this->hasStatsForDate( $app, $wiki, $aspect ) ) {
				$data = array(
					'site_id' => $wiki,
					'aspect' => $aspect,
					'count' => $count,
					'timestamp' => $time
				);

				$statsDb->insert(
					'stats_usagetracking',
					$data
				);
			}
		}

		return $stats;
	}

}
