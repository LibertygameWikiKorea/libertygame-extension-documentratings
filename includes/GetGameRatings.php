<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use ApiBase;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\ResponseFactory;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Rdbms\Database;
use Title;

// GET /sectionratings/v0/ratings/{category}/{count} -> 성공/실패 반환

// enum


// class
class GetGameRatings extends SimpleHandler {
  const REGEX_STRING_PREVENT_SQL_INJECTION = '/[\(\)@;\'\"*\+\/#]+/';

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
    // Prevent SQL Injection
    if (preg_match(self::REGEX_STRING_PREVENT_SQL_INJECTION, $category) == 1 || preg_match('/[\-]{2,}/', $category) == 1) {
      return $response->createHttpError(400, [
              "result" => "FAIL: invalid parameters",
              "httpCode" => 400,
              "httpReason" => "Bad Request"
             ]);
    }

		$score_num = (string) $count;
		$services = MediaWikiServices::getInstance();
		// TODO: 1.42+ 부터 replica DB는 $services->getConnectionProvider()->getReplicaDatabase()로 가져와야 한다.
		$dbaseref = wfGetDB(DB_REPLICA);
		$parsetarget = "";
		
		// $query는 stdClass 형의 변수임
		$query = $dbaseref->select('categorylinks INNER JOIN Vote ON categorylinks.cl_from = Vote.vote_page_id', ['page_id' => 'Vote.vote_page_id', 'votecount' => 'COUNT(Vote.vote_value)', 'vote_average' => 'AVG(Vote.vote_value)'],
		'categorylinks.cl_to = "' . $category . '"', '__METHOD__', ['GROUP BY' => 'Vote.vote_page_id', 'HAVING' => 'vote_average >= 3 AND votecount >= 2', 'ORDER BY' => 'vote_average DESC, votecount DESC','LIMIT' => $count ]);
		// 카테고리로 필터링 + 평점 3 이상만 결과로 반환 + 자기 추천 방지를 위한 2명 이상의 평가 요구

		$queryresult = [];
		for ($i = 0 ; $i < $query->numRows(); $i += 1){
			$row = $query->current();
			$title = Title::newFromID((int) $row->page_id)->getSubjectPage(); // 토론 페이지에 위젯이 붙는 것을 가정하고 본문 문서를 가져옴
			$titlestr = $title->getTitleValue()->getText();
			array_push($queryresult, ["pagename" => $titlestr, "votecount" => $row->votecount, "score" => $row->vote_average]);
			// 게임카드 파싱을 위해 파라미터 추가
			$parsetarget = $parsetarget . "{{게임카드|" . $titlestr . "|속성=설명감춤}}";
			$query->next();
		}
		// Mediawiki 사이트의 Parse API 예제를 가져와 응용함(Licensed under MIT License)
		$parseResult = "";

		// TODO: use $wgServer in LocalSettings.php
		$endPoint = "/api.php";
		$params = [
			"action" => "parse",
			"text" => $parsetarget,
			"contentmodel" => "wikitext",
			"format" => "json"
		];

		$url = $endPoint . "?" . http_build_query( $params );

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$output = curl_exec( $ch );
		curl_close( $ch );

		$parseResult = json_decode( $output, true );
		return ["result" => "SUCCESS",
			"category" => $category,
			"gameList" => $queryresult,
			"parseResult" => $parseResult["parse"]["text"]["*"],
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
