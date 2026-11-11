<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\UserIdentity;

// 사용자의 '만 12세 이상입니다' 설정을 읽는 공통 판정 (파서 옵션, REST, Lua가 함께 사용)

// class
class AgePreference {
	// 사용자 옵션 이름 (값 1: 만 12세 이상, 기본값 0). extension.json의 DefaultUserOptions와 일치해야 함
	public const OPTION = 'documentratings-over12';

	private UserOptionsLookup $userOptionsLookup;

	public function __construct( UserOptionsLookup $userOptionsLookup ) {
		$this->userOptionsLookup = $userOptionsLookup;
	}

	/**
	 * 익명 사용자는 항상 false이다. 이 값이 false이면 '전체 이용가 게임'만 표시한다.
	 */
	public function isOver12( UserIdentity $user ): bool {
		if ( !$user->isRegistered() ) {
			return false;
		}
		return (bool) $this->userOptionsLookup->getOption( $user, self::OPTION );
	}
}
