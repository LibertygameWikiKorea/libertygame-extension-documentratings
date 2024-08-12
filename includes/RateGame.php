<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use ApiBase;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Rdbms\Database;

// POST /sectionratings/v0/rategame/{game_id}/{score} -> 성공/실패 반환

// class
class RateGame extends SimpleHandler {
	public $User;
	public $PageID;
	
	public function run( $game_id, $score ) {
		$score_num = (int) $score;

		// TODO: 1.42+ 부터 primary DB는 $services->getConnectionProvider()->getPrimaryDatabase()로 가져와야 한다.
		$dbaseref = wfGetDB(DB_PRIMARY);
		
		if ($score < 1 || $score > 5){
			return ["result" => "FAIL"];
		} else {
			return ["result" => "SUCCESS"
			];
		}
	}
	
	public function needsWriteAccess() {
		return true;
	}
	
	public function getParamSettings() {
		return [
			'game_id' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'score' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}
}

// GET /sectionratings/v0/getgameratings/{gamename} -> 배열 반환, 없으면 전체 게임 반환