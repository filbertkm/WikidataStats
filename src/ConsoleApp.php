<?php

namespace WikidataStats;

class ConsoleApp {

	private $appFactory;

	private $console;

	public function __construct() {
		$this->appFactory = new AppFactory();

		$this->init();
	}

	public function init() {
        $app = $this->appFactory->newConsoleApplication(
			'WikidataStats',
			'1.0.0',
			__DIR__ . '/../config/config.yml',
			__DIR__ . '/../templates',
			__DIR__ . '/../config/wikidataclient.dblist'
		);

		$this->console = $app['console'];
	}

	public function run() {
		$this->console->run();
	}
}
