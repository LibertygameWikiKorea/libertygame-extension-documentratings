<?php

use MediaWiki\Extension\SectionRatings\AgePreference;
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
	'SectionRatings.AgePreference' => static function ( MediaWikiServices $services ): AgePreference {
		return new AgePreference( $services->getUserOptionsLookup() );
	},
];
