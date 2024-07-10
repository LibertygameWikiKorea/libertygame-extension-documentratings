<?php
// namespace
namespace Mediawiki\Extension\SectionRatings;

// use statement
use ApiBase;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;

// POST /sectionratings/v0/rategame/{gamename}/{score} -> 성공/실패 반환

// class
class RateGame extends SimpleHandler {
	
	public function run( $gamename, $score ) {
		$score_num = (int) $score;
		if (score < 1 || score > $wgSeRaTopNumber){
			return "FAIL";
		} else {
			return "SUCCESS";
		}
	}
	
	public function needsWriteAccess() {
		return true;
	}
	
	public function getParamSettings() {
		'gamename' => [
			self::PARAM_SOURCE => 'path',
			ParamValidator::PARAM_TYPE => 'string',
			ParamValidator::PARAM_REQUIRED => true,
		],
		'score' => [
			self::PARAM_SOURCE => 'path',
			ParamValidator::PARAM_TYPE => 'int',
			ParamValidator::PARAM_REQUIRED => true,
		],
	}
}

// GET /sectionratings/v0/getgameratings/{gamename} -> 배열 반환, 없으면 전체 게임 반환