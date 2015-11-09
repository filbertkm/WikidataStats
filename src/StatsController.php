<?php

namespace WikidataStats;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class StatsController {

	public function indexAction( Request $request, Application $app ) {
		$date = '2015-08-27';

		return $this->dateAction( $request, $app, $date );
	}

	public function dateAction( Request $request, Application $app, $date ) {
		if ( !$this->validateDate( $date ) ) {
			$date = '2015-01-01';
		}

		$statsDb = $app['dbs']['wikidatastats'];

		return $app['twig']->render(
			'index.html.twig',
			array(
				'stats' => $this->getStats( $statsDb, $date ),
				'totals' => $this->getAspectTotals( $statsDb, $date )
			)
		);
	}

	private function validateDate( $date  ) {
		if ( preg_match( "/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $date, $parts ) ) {
			return checkdate( $parts[2], $parts[3], $parts[1] );
		}

		return false;
	}

	private function getStats( $statsDb, $date ) {
		$sql = 'SELECT DISTINCT(u.site_id) as site_id, total.count as total, sitelinks.count as sitelinks, labels.count as labels, title.count as title, other.count as other, allusage.count as allusage '
			. 'FROM stats_usagetracking u '
			. 'LEFT JOIN (' . $this->getAspectSelectSQL( 'S', $date ) . ') as sitelinks on (u.site_id = sitelinks.site_id) '
			. 'LEFT JOIN (SELECT site_id, SUM(count) as count FROM stats_usagetracking '
					. 'where aspect LIKE "L%" '
					. 'AND date(FROM_UNIXTIME(timestamp)) = "' . $date . '" group by site_id) '
					. 'as labels on (u.site_id = labels.site_id) '
			. 'LEFT JOIN (' . $this->getAspectSelectSQL( 'total', $date ) . ') as total on (u.site_id = total.site_id) '
			. 'LEFT JOIN (' . $this->getAspectSelectSQL( 'T', $date ) . ') as title on (u.site_id = title.site_id) '
			. 'LEFT JOIN (' . $this->getAspectSelectSQL( 'O', $date ) . ') as other on (u.site_id = other.site_id) '
			. 'LEFT JOIN (' . $this->getAspectSelectSQL( 'X', $date ) . ') as allusage on (u.site_id = allusage.site_id) '
			. 'ORDER BY site_id';

		return $statsDb->fetchAll( $sql );
	}

	private function getAspectSelectSQL( $aspect, $date ) {
		return 'SELECT site_id, count FROM stats_usagetracking '
			. "WHERE aspect = '$aspect' "
			. " AND date(FROM_UNIXTIME(timestamp)) = '$date' ";
	}

	private function getAspectTotals( $statsDb, $date ) {
		$sql = 'SELECT aspect, sum(count) as sum '
			. 'FROM stats_usagetracking '
			. 'WHERE aspect NOT LIKE "L%" '
			. " AND DATE(FROM_UNIXTIME(timestamp)) = '$date' "
			. 'GROUP BY aspect '
			. 'UNION ( '
				. 'SELECT "L", sum(count) as sum '
				. 'FROM stats_usagetracking '
				. 'WHERE aspect LIKE "L%" '
				. " AND DATE(FROM_UNIXTIME(timestamp)) = '$date' "
			. ')';

		$totals = array();

		foreach( $statsDb->fetchAll( $sql ) as $row ) {
			$aspect = $row['aspect'];
			$totals[$aspect] = $row['sum'];
		}

		return $totals;
	}

}
