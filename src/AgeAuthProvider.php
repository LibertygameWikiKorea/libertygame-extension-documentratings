<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use MediaWiki\Auth\AbstractSecondaryAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\AuthManager;
use MediaWiki\User\Options\UserOptionsManager;

// 가입 폼에 '만 12세 이상입니다' 체크박스를 추가하고, 체크했다면 계정이 만들어진 직후 사용자 옵션으로 저장하는 보조 인증 제공자

// class
class AgeAuthProvider extends AbstractSecondaryAuthenticationProvider {
	private UserOptionsManager $userOptionsManager;

	public function __construct( UserOptionsManager $userOptionsManager ) {
		$this->userOptionsManager = $userOptionsManager;
	}

	/**
	 * 가입 폼에서만 체크박스를 보여준다.
	 */
	public function getAuthenticationRequests( $action, array $options ) {
		return $action === AuthManager::ACTION_CREATE ? [ new AgeAuthenticationRequest() ] : [];
	}

	/**
	 * 로그인에는 관여하지 않는다.
	 */
	public function beginSecondaryAuthentication( $user, array $reqs ) {
		return AuthenticationResponse::newAbstain();
	}

	/**
	 * 체크한 경우에만 값을 저장한다. 이 메서드가 호출되지 않아도(가입 흐름 중단 등) 기본값 0이라 전체 이용가 게임만 보인다.
	 */
	public function beginSecondaryAccountCreation( $user, $creator, array $reqs ) {
		/** @var AgeAuthenticationRequest|null $req */
		$req = AuthenticationRequest::getRequestByClass( $reqs, AgeAuthenticationRequest::class );
		if ( $req && $req->documentratingsOver12 ) {
			$this->userOptionsManager->setOption( $user, AgePreference::OPTION, 1 );
			$this->userOptionsManager->saveOptions( $user );
		}
		return AuthenticationResponse::newPass();
	}
}
