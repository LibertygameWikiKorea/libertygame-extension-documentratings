<?php
// namespace
namespace MediaWiki\Extension\SectionRatings\Utils;

// use statement
use ApiBase;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\ResponseFactory;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Rdbms\Database;
use Title;

// GET /sectionratings/v0/userratings/{user} -> 성공/실패 반환

// enum


// class
class UserRatings extends SimpleHandler {
  const REGEX_STRING_PREVENT_SQL_INJECTION = '/[\(\)@;\'\"*\+\/#]+/';

	public function run( $user ) {
    $response = SimpleHandler::getResponseFactory();
    if (strcmp($user, "") === 0) {
       return $response->createHttpError(400, [
        "result" => "FAIL: category parameter is empty",
        "httpCode" => 400,
        "httpReason" => "Bad Request"
       ]);
    }
    // Prevent SQL Injection
    if (preg_match(self::REGEX_STRING_PREVENT_SQL_INJECTION, $user) == 1 || preg_match('/[\-]{2,}/', $user) == 1) {
      return $response->createHttpError(400, [
              "result" => "FAIL: invalid parameters",
              "httpCode" => 400,
              "httpReason" => "Bad Request"
             ]);
    }

		$services = MediaWikiServices::getInstance();
		// TODO: 1.42+ 부터 replica DB는 $services->getConnectionProvider()->getReplicaDatabase()로 가져와야 한다.
		$dbase = $services->getDBLoadBalancer()->getConnection( DB_REPLICA );
		
		// $query는 stdClass 형의 변수임
		$query = $dbase->query('SELECT Vote.vote_page_id as page_id, Vote.vote_value as vote_value, FROM Vote INNER JOIN user ON user.user_name = "'. $user .'" AND Vote.vote_user_id = user.user_id ORDER BY vote_value DESC;');
		// 특정 유저의 평가 값 목록 가져옴

		$queryresult = [];
		for ($i = 0 ; $i < $query->numRows(); $i += 1){
			$row = $query->current();
			$title = Title::newFromID((int) $row->page_id)->getSubjectPage(); // 토론 페이지에 위젯이 붙는 것을 가정하고 본문 문서를 가져옴
			$titlestr = $title->getTitleValue()->getText();
			array_push($queryresult, ["pagename" => $titlestr, "score" => $row->vote_value]);
			// 게임카드 파싱을 위해 파라미터 추가
			$query->next();
		}
		
		return ["result" => "SUCCESS",
			"user" => $user,
			"ratelist" => $queryresult,
			"httpCode" => 200,
			"httpReason" => "OK"
		];
	}
	
	public function needsWriteAccess() {
		return false;
	}
	
	public function getParamSettings() {
		return [
			'user' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
		];
	}
}
