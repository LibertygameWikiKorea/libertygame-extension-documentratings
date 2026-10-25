<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

// GET /sectionratings/v0/ratings/{category}/{count} -> 성공/실패 반환

// enum


// class
class GetGameRatings extends SimpleHandler {
	private GameRatingsLookup $lookup;

	public function __construct( GameRatingsLookup $lookup ) {
		$this->lookup = $lookup;
	}

	public function run( $category, $count ) {
    $response = SimpleHandler::getResponseFactory();
    if (strcmp($category, "") === 0) {
       return $response->createHttpError(400, [
        "result" => "FAIL: category parameter is empty",
        "httpCode" => 400,
        "httpReason" => "Bad Request"
       ]);
    }
		if ($count < 1 || $count > 100) {
			return $response->createHttpError(400, [
        "result" => "FAIL: count pararmeter is out of bound",
        "httpCode" => 400,
        "httpReason" => "Bad Request"
       ]);
		}
		// 카테고리로 필터링 + 평점 3 이상만 결과로 반환 + 자기 추천 방지를 위한 2명 이상의 평가 요구
		$rows = $this->lookup->getTopRated( $category, (int) $count );

		$parsetarget = "";
		$queryresult = [];
		foreach ($rows as $row) {
			$titlestr = $row['title']->getText(); // 토론 페이지에 위젯이 붙는 것을 가정하고 본문 문서를 가져옴
			array_push($queryresult, ["pagename" => $titlestr, "votecount" => $row['votecount'], "score" => $row['score']]);
			// 게임카드 파싱을 위해 파라미터 추가
			$parsetarget = $parsetarget . "{{게임카드|" . $titlestr . "|속성=설명감춤}}";
		}
		return ["result" => "SUCCESS",
			"category" => $category,
			"gameList" => $queryresult,
			"parseResult" => GameCardRenderer::render( $parsetarget ),
			"httpCode" => 200,
			"httpReason" => "OK"
		];
	}
	
	public function needsWriteAccess() {
		return false;
	}
	
	public function getParamSettings() {
		return [
			'category' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
			'count' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}
}
