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
		$dbaseref = wfGetDB(DB_REPLICA);
		// TODO: After 1.42+, replica DB must be called via $services->getConnectionProvider()->getReplicaDatabase();
		
		$queryresult = $dbaseref->selectField('Vote', 'vote_page_id, COUNT(*) AS votecount, AVG(vote_value) AS vote_average',
		'', '__METHOD__', ['GROUP BY vote_page_id', 'LIMIT ' . $score_num]);
		
		return ["result" => "SUCCESS",
			"Vote" => $queryresult
		];
	}
	
	public function needsWriteAccess() {
		return false; // TODO: DB 작업을 위해 true로 바꿀 것
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
