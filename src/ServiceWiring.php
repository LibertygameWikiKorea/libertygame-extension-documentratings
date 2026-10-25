<?php

use MediaWiki\Extension\SectionRatings\GameRatingsLookup;
use MediaWiki\MediaWikiServices;

return [
	'SectionRatings.GameRatingsLookup' => static function ( MediaWikiServices $services ): GameRatingsLookup {
		return new GameRatingsLookup(
			$services->getConnectionProvider(),
			$services->getMainWANObjectCache(),
			$services->getNamespaceInfo()
		);
	},
];
