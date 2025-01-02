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

// GET /sectionratings/v0/categorycounter/{category}/{namespace} -> 이름공간 내 카테고리 개수 반환

// class
class CategoryCounter extends SimpleHandler {
	const REGEX_STRING_PREVENT_SQL_INJECTION = '/[\(\)@;\'\"*\+\/#]+/';
	public function run( $category, $namespace ) {
    $response = SimpleHandler::getResponseFactory();
    if (strcmp($category, "") === 0) {
      return $response->createHttpError(400, [
        "result" => "FAIL: category parameter is empty",
        "httpCode" => 400,
        "httpReason" => "Bad Request"
       ]);
    }

		if ($namespace < 0) {
			return $response->createHttpError(400, [
        "result" => "FAIL: namespace pararmeter is out of bound(non-negative)",
        "httpCode" => 400,
        "httpReason" => "Bad Request"
       ]);
		}

    // Prevent SQL Injection
    if (preg_match(self::REGEX_STRING_PREVENT_SQL_INJECTION, $category) == 1 || preg_match('/[\-]{2,}/', $category) == 1) {
      return $response->createHttpError(400, [
              "result" => "FAIL: invalid character(s) found in parameters",
              "httpCode" => 400,
              "httpReason" => "Bad Request"
             ]);
    }

		$services = MediaWikiServices::getInstance();
		// TODO: 1.42+ 부터 replica DB는 $services->getConnectionProvider()->getReplicaDatabase()로 가져와야 한다.
		$dbaseref = $services->getDBLoadBalancer()->getConnection( DB_REPLICA );

		$resultarr = [];

		$category = explode("|", $category);
		foreach ($category as $c){
			// $query는 stdClass 형의 변수임
			$query = $dbase->query('SELECT COUNT(page.page_id) as count, COUNT(Vote.vote_value) as votecount, AVG(Vote.vote_value) as vote_average FROM categorylinks INNER JOIN page ON categorylinks.cl_from = page.page_id AND categorylinks.cl_type = \'page\' AND categorylinks.cl_to = "' . $c . '" AND page.page_namespace = '. $namespace .';');
			$resultarr[] = $query->current()->count; // resultarr에 push
		}
		return ["result" => "SUCCESS",
			"category" => $category,
			"count" => $resultarr,
			"namespace" => $namespace,
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
			'namespace' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}
}
