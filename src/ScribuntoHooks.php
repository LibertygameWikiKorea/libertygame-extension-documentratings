<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// Scribunto 확장 기능이 없으면 이 훅이 호출되지 않는다.
// Scribunto의 훅 인터페이스를 구현하면 미설치 환경에서 클래스 로드가 실패하므로 구현하지 않는다.

// class
class ScribuntoHooks {
	/**
	 * @param string $engine
	 * @param array &$extraLibraries
	 */
	public function onScribuntoExternalLibraries( string $engine, array &$extraLibraries ) {
		if ( $engine === 'lua' ) {
			$extraLibraries['mw.ext.documentratings'] = DocumentRatingsLibrary::class;
		}
	}
}
