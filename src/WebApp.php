<?php

namespace WikidataStats;

class WebApp {

	private $appFactory;

	private $app;

	public function __construct() {
		$this->appFactory = new AppFactory();

		$this->init();
	}

	public function init() {
        $this->app = $this->appFactory->newApplication(
			__DIR__ . '/../config/config.yml',
			__DIR__ . '/../templates',
			__DIR__ . '/../config/wikidataclient.dblist'
		);

		$this->initRoutes();
	}

	private function initRoutes() {
		$app = $this->app;

		$this->app->get( '/', 'WikidataStats\StatsController::indexAction' );
	}

	public function run() {
		$this->app->run();
	}
}
