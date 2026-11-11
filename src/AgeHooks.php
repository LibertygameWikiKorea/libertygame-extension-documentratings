<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use MediaWiki\Auth\AuthManager;
use MediaWiki\Hook\ParserOptionsRegisterHook;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\SpecialPage\Hook\AuthChangeFormFieldsHook;

// '만 12세 이상입니다' 설정의 환경 설정 항목, 가입 폼 배치, 파서 캐시 분리를 담당하는 훅

// class
class AgeHooks implements GetPreferencesHook, ParserOptionsRegisterHook, AuthChangeFormFieldsHook {
	// 가입 폼에서 필드를 맨 아래로 내리는 가중치 (기본값 0)
	private const CREATE_FORM_WEIGHT = 100;

	private AgePreference $agePreference;

	public function __construct( AgePreference $agePreference ) {
		$this->agePreference = $agePreference;
	}

	/**
	 * 계정 환경 설정에 같은 체크박스를 둔다.
	 *
	 * @param \User $user
	 * @param array &$preferences
	 */
	public function onGetPreferences( $user, &$preferences ) {
		$preferences[AgePreference::OPTION] = [
			'type' => 'toggle',
			'section' => 'personal/info',
			'label-message' => 'documentratings-pref-over12',
			'help-message' => 'documentratings-pref-over12-help',
		];
	}

	/**
	 * 이 옵션을 읽은 문서는 파서 캐시가 설정 값별로 나뉜다. 등록하지 않으면 한 사용자의 결과가 다른 사용자에게 캐시로 전달된다.
	 * 1.42에서는 ParserOptions가 전역 클래스이므로 콜백에는 타입 힌트를 쓰지 않는다.
	 *
	 * @param array &$defaults
	 * @param array &$inCacheKey
	 * @param array &$lazyLoad
	 */
	public function onParserOptionsRegister( &$defaults, &$inCacheKey, &$lazyLoad ) {
		$defaults[AgePreference::OPTION] = null;
		$inCacheKey[AgePreference::OPTION] = true;
		$lazyLoad[AgePreference::OPTION] = fn ( $parserOptions ) =>
			$this->agePreference->isOver12( $parserOptions->getUserIdentity() );
	}

	/**
	 * 가입 폼에서는 체크박스를 캡차 아래, 폼 맨 아래에 놓는다.
	 *
	 * @param \MediaWiki\Auth\AuthenticationRequest[] $requests
	 * @param array $fieldInfo
	 * @param array &$formDescriptor
	 * @param string $action
	 */
	public function onAuthChangeFormFields( $requests, $fieldInfo, &$formDescriptor, $action ) {
		if ( $action === AuthManager::ACTION_CREATE
			&& isset( $formDescriptor[AgeAuthenticationRequest::FIELD] )
		) {
			$formDescriptor[AgeAuthenticationRequest::FIELD]['weight'] = self::CREATE_FORM_WEIGHT;
		}
	}
}
