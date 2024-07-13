<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use ApiBase;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

// POST /sectionratings/v0/rategame/{gamename}/{score} -> 성공/실패 반환

// class
class RateGame extends SimpleHandler {
	
	public function run( $gamename, $score ) {
		$score_num = (int) $score;
		if ($score < 1 || $score > 5){
			return ["result" => "FAIL"];
		} else {
			return ["result" => "SUCCESS"];
		}
	}
	
	public function needsWriteAccess() {
		return false; // TODO: DB 작업을 위해 true로 바꿀 것
	}
	
	public function getParamSettings() {
		return [
			'gamename' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
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