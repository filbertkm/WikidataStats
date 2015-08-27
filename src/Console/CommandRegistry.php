<?php

namespace WikidataStats\Console;

class CommandRegistry {

	public function getCommands() {
		$classNames = array(
			'\WikidataStats\Console\Commands\StatsCommand',
			'\WikidataStats\Console\Commands\StatsUpdateCommand'
		);

		foreach( $classNames as $className ) {
			$commands[] = $this->newCommand( $className );
		}

		return $commands;
	}

	private function newCommand( $class ) {
		return new $class;
	}

}
