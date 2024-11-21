<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use ApiBase;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Rdbms\Database;
use Title;

// GET /sectionratings/v0/categorycounter/{category}/{namespace} -> 이름공간 내 카테고리 개수 반환

// class
class CategoryCounter extends SimpleHandler {
	const REGEX_STRING_PREVENT_SQL_INJECTION = '/(;|\'|\"|*|\+|\-|\/|#)+/';
	public function run( $category, $namespace ) {
		if ($namespace < 0) {
			return ["result" => "FAIL: namespace pararmeter is out of bound(non-negative)",
				"httpCode" => 400,
				"httpReason" => "Bad Request"
			];
		}

    // Prevent SQL Injection
    if (preg_match(self::REGEX_STRING_PREVENT_SQL_INJECTION, $category) == 1) {
      return [
              "result" => "FAIL: invalid character(s) found in parameters",
              "httpCode" => 400,
              "httpReason" => "Bad Request"
            ];
    }

		$services = MediaWikiServices::getInstance();
		// TODO: 1.42+ 부터 replica DB는 $services->getConnectionProvider()->getReplicaDatabase()로 가져와야 한다.
		$dbaseref = wfGetDB(DB_REPLICA);

		$resultarr = [];

		$category = explode("|", $category);
		foreach ($category as $c){
			// $query는 stdClass 형의 변수임
			$query = $dbaseref->select('categorylinks INNER JOIN page ON categorylinks.cl_from = page.page_id', ['count' => 'COUNT(page.page_id)', ],
			'categorylinks.cl_type = \'page\' AND categorylinks.cl_to = "' . $c . '" AND page.page_namespace = '. $namespace , '__METHOD__', []);

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
