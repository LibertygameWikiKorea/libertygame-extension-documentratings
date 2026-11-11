<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

// GET /sectionratings/v1/documentratinglist/{category}/{count} -> 사용자의 '만 12세 이상입니다' 설정에 따라 인기 게임 반환
// 설정을 체크하지 않았거나 익명 사용자이면 '전체 이용가 게임' 분류가 함께 붙은 문서만 대상으로 한다.

// class
class GetDocumentRatingList extends SimpleHandler {
	private GameRatingsLookup $lookup;
	private AgePreference $agePreference;

	public function __construct( GameRatingsLookup $lookup, AgePreference $agePreference ) {
		$this->lookup = $lookup;
		$this->agePreference = $agePreference;
	}

	public function run( $category, $count ) {
		$response = $this->getResponseFactory();
		if (strcmp($category, "") === 0) {
			return $response->createHttpError(400, [
				"result" => "FAIL: category parameter is empty",
				"httpCode" => 400,
				"httpReason" => "Bad Request"
			]);
		}
		if ($count < GameRatingsLookup::MIN_COUNT || $count > GameRatingsLookup::MAX_COUNT) {
			return $response->createHttpError(400, [
				"result" => "FAIL: count pararmeter is out of bound",
				"httpCode" => 400,
				"httpReason" => "Bad Request"
			]);
		}

		// 세션(쿠키)으로 식별되는 사용자의 설정을 따른다. 익명 사용자는 항상 false
		$over12 = $this->agePreference->isOver12( $this->getAuthority()->getUser() );
		$rows = $this->lookup->getTopRated( $category, (int) $count, !$over12 );

		$titles = [];
		$queryresult = [];
		foreach ($rows as $row) {
			$titlestr = $row['title']->getText(); // 토론 페이지에 위젯이 붙는 것을 가정하고 본문 문서를 가져옴
			$titles[] = $titlestr;
			$queryresult[] = ["pagename" => $titlestr, "votecount" => $row['votecount'], "score" => $row['score']];
		}

		$result = $response->createJson( [
			"result" => "SUCCESS",
			"category" => $category,
			"over12" => $over12,
			"allAgesOnly" => !$over12,
			"gameList" => $queryresult,
			"parseResult" => GameCardRenderer::render( GameCardRenderer::listWikitext( $titles ) ),
			"httpCode" => 200,
			"httpReason" => "OK"
		] );
		// 사용자별로 응답이 달라지므로 공유 캐시에 남기지 않는다.
		$result->setHeader( 'Cache-Control', 'private, must-revalidate, max-age=0' );
		$result->setHeader( 'Vary', 'Cookie' );
		return $result;
	}

	public function needsWriteAccess() {
		return false;
	}

	public function getParamSettings() {
		return [
			'category' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'count' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}
}
