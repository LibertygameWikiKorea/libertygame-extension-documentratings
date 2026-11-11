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
	// 게임카드 틀 이름
	public const TEMPLATE = '게임카드';

	/**
	 * 문서 이름 목록을 게임카드 틀 한 번 호출로 바꾼 위키텍스트를 반환한다.
	 * 틀은 호출마다 .gamecards로 감싸므로, 한 번만 호출해 문서 목록(<ul><li>)을 통째로 넘긴다.
	 * 문서 이름에 '='이 있어도 되도록 1=로 지정한다. 문서 이름에는 '|', '{', '}', '<', '>'를 쓸 수 없다.
	 *
	 * @param string[] $titles
	 */
	public static function listWikitext( array $titles ): string {
		$items = '';
		foreach ( $titles as $title ) {
			$items .= '<li>' . $title . '</li>';
		}
		return '{{' . self::TEMPLATE . '|1=<ul>' . $items . '</ul>|속성=설명감춤}}';
	}

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
