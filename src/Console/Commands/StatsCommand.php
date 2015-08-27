<?php

namespace WikidataStats\Console\Commands;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatsCommand extends Command {

	protected function configure() {
		$this->setName( 'stats' )
			->setDescription( 'Wikidata stats' )
			->addArgument(
				'wiki',
				InputArgument::REQUIRED,
				'Wiki'
			);
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$app = $this->getSilexApplication();

		$wiki = $input->getArgument( 'wiki' );

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

		var_export( $stats );
	}

}
