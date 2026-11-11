<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use MediaWiki\Config\Config;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Parser\Parser;
use MediaWiki\Title\MalformedTitleException;
use MediaWiki\Title\TitleFormatter;
use MediaWiki\Title\TitleParser;

// {{#documentratinglist:분류|갯수}} -> 분류 내 평균 별점 상위 문서의 게임카드를 반환
// 예: {{#documentratinglist:어드벤처 게임|4}}

// class
class DocumentRatingList implements ParserFirstCallInitHook {
	public const FUNCTION_NAME = 'documentratinglist';
	// 갯수를 생략했을 때의 기본값 (기존 Common.js의 기본값과 동일)
	public const DEFAULT_COUNT = 4;
	// 게임카드 틀 이름
	private const GAMECARD_TEMPLATE = '게임카드';
	// 파서 캐시가 비어 있을 때 DB 부하를 줄이기 위한 조회 결과 캐시 시간(초)
	private const LOOKUP_CACHE_TTL = 600;

	private GameRatingsLookup $lookup;
	private TitleParser $titleParser;
	private TitleFormatter $titleFormatter;
	private Config $config;

	public function __construct(
		GameRatingsLookup $lookup,
		TitleParser $titleParser,
		TitleFormatter $titleFormatter,
		Config $config
	) {
		$this->lookup = $lookup;
		$this->titleParser = $titleParser;
		$this->titleFormatter = $titleFormatter;
		$this->config = $config;
	}

	/**
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook( self::FUNCTION_NAME, [ $this, 'render' ] );
	}

	/**
	 * @param Parser $parser
	 * @param string $category 분류 이름('분류:' 접두사, 공백, 언더바 모두 허용)
	 * @param string $count 불러올 갯수(1~100), 생략하면 기본값
	 * @return string|array
	 */
	public function render( Parser $parser, string $category = '', string $count = '' ) {
		$category = trim( $category );
		$count = trim( $count );

		if ( $category === '' ) {
			return $this->error( $parser, 'documentratinglist-error-category-empty' );
		}
		try {
			// 접두사가 없으면 분류 이름공간으로 해석하고, 있으면 접두사를 따른다.
			$title = $this->titleParser->parseTitle( $category, NS_CATEGORY );
		} catch ( MalformedTitleException $e ) {
			return $this->error( $parser, 'documentratinglist-error-category-invalid' );
		}
		if ( $title->getNamespace() !== NS_CATEGORY || $title->isExternal() ) {
			return $this->error( $parser, 'documentratinglist-error-category-invalid' );
		}

		if ( $count === '' ) {
			$limit = self::DEFAULT_COUNT;
		} elseif ( ctype_digit( $count ) && strlen( $count ) <= 3 ) {
			$limit = (int) $count;
		} else {
			$limit = 0;
		}
		if ( $limit < GameRatingsLookup::MIN_COUNT || $limit > GameRatingsLookup::MAX_COUNT ) {
			return $this->error(
				$parser,
				'documentratinglist-error-count',
				GameRatingsLookup::MIN_COUNT,
				GameRatingsLookup::MAX_COUNT
			);
		}

		if ( !$parser->incrementExpensiveFunctionCount() ) {
			return $this->error( $parser, 'documentratinglist-error-expensive' );
		}

		// 평가 값이 바뀌어도 문서가 이 시간 안에 다시 파싱되도록 파서 캐시 만료 시간을 제한한다.
		$expiry = (int) $this->config->get( 'DocumentRatingListCacheExpiry' );
		if ( $expiry >= 0 ) {
			$parser->getOutput()->updateCacheExpiry( $expiry );
		}

		$rows = $this->lookup->getTopRated( $title->getDBkey(), $limit, self::LOOKUP_CACHE_TTL );
		if ( !$rows ) {
			$empty = $parser->msg( 'documentratinglist-empty' );
			if ( $empty->isDisabled() ) {
				return '';
			}
			return [
				'<div class="documentratinglist-empty">' . $empty->escaped() . '</div>',
				'noparse' => true,
				'isHTML' => true,
			];
		}

		// 게임카드 틀은 호출마다 .gamecards로 감싸므로, 한 번만 호출해 문서 목록(<ul><li>)을 통째로 넘긴다.
		// 문서 이름에 '='이 있어도 되도록 1=로 지정한다. 문서 이름에는 '|', '{', '}', '<', '>'를 쓸 수 없다.
		$items = '';
		foreach ( $rows as $row ) {
			$items .= '<li>' . $this->titleFormatter->getPrefixedText( $row['title'] ) . '</li>';
		}
		$wikitext = '{{' . self::GAMECARD_TEMPLATE . '|1=<ul>' . $items . '</ul>|속성=설명감춤}}';

		return [ '<div class="documentratinglist">' . $wikitext . '</div>', 'noparse' => false ];
	}

	private function error( Parser $parser, string $messageKey, ...$params ): array {
		return [
			'<strong class="error">' . $parser->msg( $messageKey, ...$params )->escaped() . '</strong>',
			'noparse' => true,
			'isHTML' => true,
		];
	}
}
