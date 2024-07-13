<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use ApiBase;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Rdbms\Database;

// GET /sectionratings/v0/getgameratings/{category}/{count} -> 성공/실패 반환

// class
class GetGameRatings extends SimpleHandler {
	public $User;
	public $PageID;
	
	public function run( $category, $count ) {
		$score_num = (string) $count;
		$services = MediaWikiServices::getInstance();
		// TODO: After 1.42+, replica DB must be called via $services->getConnectionProvider()->getReplicaDatabase();
		$dbaseref = wfGetDB(DB_REPLICA);
		
		$query = $dbaseref->select('Vote', ['page_id' => 'vote_page_id', 'votecount' => 'COUNT(*)', 'vote_average' => 'AVG(vote_value)'],
		'', '__METHOD__', ['GROUP BY' => 'vote_page_id']);
		
		$queryresult = [];
		for ($i = 0 ; $i < $query->numRows(); $i += 1){
			array_push($queryresult, $query->current());
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
