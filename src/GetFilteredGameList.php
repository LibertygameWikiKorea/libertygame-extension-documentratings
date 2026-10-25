<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;
use Title;
// GetFilteredGameList.php
// This file provides a function to filter and retrieve a list of games based on specified criteria.

class GetFilteredGameList extends SimpleHandler
{

  /**
   * Filters the game list based on provided criteria.
   *
   * @param string $category word to filter games by category.
   * @param string $age word filtering by age rating.
   * @param integer $count number of games to return (1-100).
   * @return array JSON response containing the filtered list of games.
   */
  // age 파라미터 허용 목록(키) -> 필터링에 사용할 분류 이름
  const AGE_CATEGORIES = [
    "all" => "전체_이용가_게임",
    "12" => "12세_이용가_게임",
    "15" => "15세_이용가_게임",
  ];
  public function run( $category, $age, $count, $offset ): mixed {
    $response = SimpleHandler::getResponseFactory();
    if (strcmp($category, "") === 0) {
       return $response->createHttpError(400, [
        "result" => "FAIL: category parameter is empty",
        "httpCode" => 400,
        "httpReason" => "Bad Request"
       ]);
    }
    if (!is_string($age) || !isset(self::AGE_CATEGORIES[$age])) {
			return $response->createHttpError(400, [
        "result" => "FAIL: wrong age parameter",
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
		if ($offset !== null && $offset < 0) {
			return $response->createHttpError(400, [
        "result" => "FAIL: offset pararmeter is out of bound",
        "httpCode" => 400,
        "httpReason" => "Bad Request"
       ]);
		}

    $services = MediaWikiServices::getInstance();
		// Note: 1.42+ 부터 replica DB는 $services->getConnectionProvider()->getReplicaDatabase()로 가져와야 한다.
		$dbase = $services->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$parsetarget = "";
		// $query는 stdClass 형의 변수임
		// 값은 쿼리 빌더가 바인딩/이스케이프 처리함. age는 위에서 허용 목록으로 검증됨
		$query = $dbase->newSelectQueryBuilder()
			->select( [ 'page_id' => 'page.page_id' ] )
			->from( 'categorylinks' )
			->join( 'page', null, 'categorylinks.cl_from = page.page_id' )
			->where( [ 'categorylinks.cl_to' => self::AGE_CATEGORIES[$age] ] )
			->orderBy( 'page.page_touched DESC' )
			->limit( (int) $count )
			->offset( (int) $offset )
			->caller( __METHOD__ )
			->fetchResultSet();

		$queryresult = [];
		for ($i = 0 ; $i < $query->numRows(); $i += 1){
			$row = $query->current();
			$title = Title::newFromID((int) $row->page_id)->getSubjectPage(); // 토론 페이지에 위젯이 붙는 것을 가정하고 본문 문서를 가져옴
			$titlestr = $title->getTitleValue()->getText();
			array_push($queryresult,  $titlestr);
			// 게임카드 파싱을 위해 파라미터 추가
			$parsetarget = $parsetarget . "{{게임카드|" . $titlestr . "|속성=설명감춤}}";
			$query->next();
		}

    return ["result" => "SUCCESS",
			"category" => $category,
			"gameList" => $queryresult,
			"parseResult" => GameCardRenderer::render( $parsetarget ),
			"httpCode" => 200,
			"httpReason" => "OK"
		];
  }

  public function needsWriteAccess(): bool   {
		return false;
	}
	
	public function getParamSettings(): array {
		return [
			'category' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
      'age' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'count' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
			],
      'offset' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false,
			],
		];
	}
  
}