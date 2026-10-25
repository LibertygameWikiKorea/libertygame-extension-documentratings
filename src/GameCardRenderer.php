<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use MediaWiki\MediaWikiServices;
use ParserOptions;
use Title;

// 게임카드 틀 wikitext를 MediaWiki 파서로 직접 렌더링하여 HTML을 반환 (Parse API 호출 대체)

// class
class GameCardRenderer {
	public static function render( string $wikitext ): string {
		$services = MediaWikiServices::getInstance();
		// api.php?action=parse 를 text만으로 호출했을 때의 기본 제목과 동일
		$title = Title::makeTitle( NS_MAIN, 'API' );
		// 기존 Parse API 호출은 쿠키 없이 익명으로 요청했으므로 익명 옵션 사용
		$popts = ParserOptions::newFromAnon();
		// 전역 파서 재진입 문제를 피하기 위해 새 파서 인스턴스 사용
		$parser = $services->getParserFactory()->getInstance();
		$output = $parser->parse( $wikitext, $title, $popts );
		// 1.42+ : getText() 대신 출력 파이프라인 사용 (mw-parser-output div로 감싸짐)
		return $output->runOutputPipeline( $popts, [] )->getContentHolderText();
	}
}
