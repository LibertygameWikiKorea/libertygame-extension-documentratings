<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use MediaWiki\Auth\AuthenticationRequest;

// 가입 폼의 '만 12세 이상입니다' 체크박스 (선택 항목)

// class
class AgeAuthenticationRequest extends AuthenticationRequest {
	// 필드 이름은 같은 이름의 공개 속성에 값이 담기므로 하이픈 없이 쓴다
	public const FIELD = 'documentratingsOver12';

	public $required = self::OPTIONAL;

	/** @var bool 체크 여부 */
	public $documentratingsOver12 = false;

	public function getFieldInfo() {
		return [
			self::FIELD => [
				'type' => 'checkbox',
				'label' => wfMessage( 'documentratings-over12-label' ),
				'help' => wfMessage( 'documentratings-over12-help' ),
				'optional' => true,
			],
		];
	}
}
