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
	public function run( $category, $namespace ) {
    $response = SimpleHandler::getResponseFactory();
    if (strcmp($category, "") === 0) {
      return $response->createHttpError(400, [
        "result" => "FAIL: category parameter is empty",
        "httpCode" => 400,
        "httpReason" => "Bad Request"
       ]);
    }

		// namespace는 문자열로 들어오므로 0 이상의 정수 형태인지 직접 확인
		if (!ctype_digit($namespace)) {
			return $response->createHttpError(400, [
        "result" => "FAIL: namespace pararmeter must be a non-negative integer",
        "httpCode" => 400,
        "httpReason" => "Bad Request"
       ]);
		}

		$services = MediaWikiServices::getInstance();
		// TODO: 1.42+ 부터 replica DB는 $services->getConnectionProvider()->getReplicaDatabase()로 가져와야 한다.
		$dbaseref = wfGetDB(DB_REPLICA);

		$resultarr = [];

		$category = explode("|", $category);
		foreach ($category as $c){
			// 값은 쿼리 빌더가 바인딩/이스케이프 처리함
			$resultarr[] = $dbaseref->newSelectQueryBuilder()
				->select( 'COUNT(page.page_id)' )
				->from( 'categorylinks' )
				->join( 'page', null, 'categorylinks.cl_from = page.page_id' )
				->where( [
					'categorylinks.cl_type' => 'page',
					'categorylinks.cl_to' => $c,
					'page.page_namespace' => (int) $namespace,
				] )
				->caller( __METHOD__ )
				->fetchField(); // resultarr에 push
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
