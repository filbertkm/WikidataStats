<?php

namespace WikidataStats;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class StatsController {

	public function indexAction( Request $request, Application $app ) {
		$statsDb = $app['dbs']['wikidatastats'];

		$sql = 'SELECT DISTINCT(u.site_id) as site_id, total.count as total, sitelinks.count as sitelinks, labels.count as labels, title.count as title, other.count as other, allusage.count as allusage '
			. 'FROM stats_usagetracking u '
			. 'LEFT JOIN (SELECT site_id, count FROM stats_usagetracking WHERE aspect = "S") as sitelinks on (u.site_id = sitelinks.site_id) '
			. 'LEFT JOIN (SELECT site_id, SUM(count) as count FROM stats_usagetracking where aspect LIKE "L%" group by site_id) as labels on (u.site_id = labels.site_id) '
			. 'LEFT JOIN (SELECT site_id, count FROM stats_usagetracking WHERE aspect = "total") as total on (u.site_id = total.site_id) '
			. 'LEFT JOIN (SELECT site_id, count FROM stats_usagetracking WHERE aspect = "T") as title on (u.site_id = title.site_id) '
			. 'LEFT JOIN (SELECT site_id, count FROM stats_usagetracking WHERE aspect = "O") as other on (u.site_id = other.site_id) '
			. 'LEFT JOIN (SELECT site_id, count FROM stats_usagetracking WHERE aspect = "X") as allusage on (u.site_id = allusage.site_id) '
			. 'ORDER BY site_id';

		$stats = $statsDb->fetchAll( $sql );

		return $app['twig']->render(
			'index.html.twig', array( 'stats' => $stats )
		);
	}

}
