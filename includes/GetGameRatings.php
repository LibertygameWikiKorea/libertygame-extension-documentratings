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

// GET /sectionratings/v0/getgameratings/{category}/{count} -> 성공/실패 반환

// class
class GetGameRatings extends SimpleHandler {
	
	public function run( $category, $count ) {
		if ($count < 1 || $count > 100) {
			return ["result" => "FAIL: count pararmeter is out of bound"];
		}
		$score_num = (string) $count;
		$services = MediaWikiServices::getInstance();
		// TODO: 1.42+ 부터 replica DB는 $services->getConnectionProvider()->getReplicaDatabase()로 가져와야 한다.
		$dbaseref = wfGetDB(DB_REPLICA);
		
		// $query는 stdClass 형의 변수임
		$query = $dbaseref->select('Vote', ['page_id' => 'vote_page_id', 'votecount' => 'COUNT(*)', 'vote_average' => 'AVG(vote_value)'],
		'', '__METHOD__', ['GROUP BY' => 'vote_page_id', 'ORDER BY' => 'vote_average DESC','LIMIT' => $count ]);
		
		$queryresult = [];
		for ($i = 0 ; $i < $query->numRows(); $i += 1){
			$row = $query->current();
			$title = Title::newFromID((int) $row->page_id)->getSubjectPage(); // 토론 페이지에 위젯이 붙는 것을 가정하고 주제 문서를 가져옴
			if(in_array($category, array_keys($title->getParentCategories()), true)){ // 카테고리로 필터링
				$titlestr = $title->getTitleValue()->getText();
				array_push($queryresult, ["pagename" => $titlestr, "votecount" => $row->votecount, "score" => $row->vote_average]);
			}
			$query->next();
		}
		
		return ["result" => "SUCCESS",
			"Vote" => $queryresult
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
