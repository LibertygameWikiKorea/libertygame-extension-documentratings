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
  const AGE_TYPES = ["all", "12", "15"];
  const REGEX_STRING_PREVENT_SQL_INJECTION = '/[\(\)@;\'\"*\+\/#]+/';
  public function run( $category, $age, $count, $offset ): mixed {
    $response = SimpleHandler::getResponseFactory();
    if (strcmp($category, "") === 0) {
       return $response->createHttpError(400, [
        "result" => "FAIL: category parameter is empty",
        "httpCode" => 400,
        "httpReason" => "Bad Request"
       ]);
    }
    if (array_find(self::AGE_TYPES, $age) === false) {
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
    // Prevent SQL Injection
    if (preg_match(self::REGEX_STRING_PREVENT_SQL_INJECTION, $category) == 1 || preg_match('/[\-]{2,}/', $category) == 1) {
      return $response->createHttpError(400, [
              "result" => "FAIL: invalid parameters",
              "httpCode" => 400,
              "httpReason" => "Bad Request"
             ]);
    } else if (preg_match(self::REGEX_STRING_PREVENT_SQL_INJECTION, $age) == 1 || preg_match('/[\-]{2,}/', $age) == 1) {
      return $response->createHttpError(400, [
              "result" => "FAIL: invalid parameters",
              "httpCode" => 400,
              "httpReason" => "Bad Request"
             ]);
    }

    $services = MediaWikiServices::getInstance();
		// Note: 1.42+ 부터 replica DB는 $services->getConnectionProvider()->getReplicaDatabase()로 가져와야 한다.
		$dbase = $services->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$parsetarget = "";
		$query = null;
		// $query는 stdClass 형의 변수임
    if ($age === "all") {
      $query = $dbase->query('SELECT page.page_id AS page_id FROM categorylinks INNER JOIN page ON categorylinks.cl_from = page.page_id WHERE categorylinks.cl_to = \"전체_이용가_게임\" ORDER BY page.page_touched DESC LIMIT ' . $count .' OFFSET '. $offset .';');
    } else if ($age === "12") {
      $query = $dbase->query('SELECT page.page_id AS page_id FROM categorylinks INNER JOIN page ON categorylinks.cl_from = page.page_id WHERE categorylinks.cl_to = \"12세_이용가_게임\" ORDER BY page.page_touched DESC LIMIT ' . $count .' OFFSET '. $offset .';');
    } else if ($age === "15") {
		  $query = $dbase->query('SELECT page.page_id AS page_id FROM categorylinks INNER JOIN page ON categorylinks.cl_from = page.page_id WHERE categorylinks.cl_to = \"15세_이용가_게임\" ORDER BY page.page_touched DESC LIMIT ' . $count .' OFFSET '. $offset .';');
    } else {
      $query = $dbase->query('SELECT page.page_id AS page_id FROM categorylinks INNER JOIN page ON categorylinks.cl_from = page.page_id WHERE categorylinks.cl_to = \"리버티게임\" ORDER BY page.page_touched DESC LIMIT ' . $count .' OFFSET '. $offset .';');
    }

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

    $endPoint = MediaWikiServices::getInstance()->getMainConfig()->get("Server") . "/api.php";
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