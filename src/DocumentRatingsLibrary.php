<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LibraryBase;

// Scribunto(Lua) 모듈에서 mw.ext.documentratings.isOver12()로 '만 12세 이상입니다' 설정을 읽는 라이브러리
// Scribunto가 설치되어 있을 때만 ScribuntoHooks가 이 클래스를 등록하므로, 미설치 환경에서는 로드되지 않는다.

// class
class DocumentRatingsLibrary extends LibraryBase {
	public function register() {
		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.ext.documentratings.lua',
			[ 'isOver12' => [ $this, 'isOver12' ] ],
			[]
		);
	}

	/**
	 * 파서 옵션으로 읽으므로 이 값을 쓴 문서의 파서 캐시도 설정 값별로 나뉜다. 익명 사용자는 항상 false.
	 *
	 * @return bool[]
	 */
	public function isOver12() {
		return [ (bool) $this->getParserOptions()->getOption( AgePreference::OPTION ) ];
	}
}
