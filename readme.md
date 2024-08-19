# mediawiki-extension-sectionratings
이 확장기능은 미디어위키 용으로 설계된 VoteNY 확장 기능으로 만든 특정 콘테스트의 데이터베이스 테이블에서 현재 평균 별점 상위 n개의 항목에 관한 정보를 JSON 포맷으로 반환합니다.

이 확장 기능은 [리버티게임 위키](https://libertyga.me) 내에서 사용할 목적으로 만들어졌습니다.

그래서 영어가 아닌 한국어로 도큐먼트가 제공됩니다.

# 설치 및 사용법
1. 미디어위키의 extensions 폴더에 git clone합니다. 이때 적절한 폴더 이름(예: SectionRatings)으로 clone하도록 파라미터를 설정하세요
2. wgLoadExtensions 함수로 LocalSettings.php에서 확장 기능을 로드합니다.


# REST API 사용 예시
## getgameratings
```(URL)/rest.php/sectionratings/v0/ratings/{분류 이름}/{불러올 갯수}```

반환값은 다음과 같습니다.
```json
{
    "result": "SUCCESS", // "FAIL:" 로도 시작할 수 있습니다(예: 너무 많거나 적은 게임 정보 갯수). 이 경우 공백을 '-' 문자로 치환한 한 줄 오류 내용을 담은 "error"가 추가로 전달되어야 합니다.
    "Vote": [ //3.0 이하의 평가를 가진 게임은 반환하지 않습니다.
        {
            "pagename": "...",
            "votecount": "1",
            "score": "4"
        },
        {
            "pagename": "...",
            "votecount": "1",
            "score": "3"
        },
        ...
    ],
	"parseResult" : "
	   ...  // 리버티게임 게임카드 틀을 파싱한 HTML Element 결과물이 "mw-parser-output" id를 가진 div 태그에 싸인 채로 포함됩니다.
	",
    "httpCode" : 200 // 오류가 발생할 경우 미디어위키의 내부 코드 정의에 의존합니다(예: 타입 오류시 400)
}
```
* 주의: parseResult는 미디어위키 Parse API 제한으로 인해 최대 50개의 게임만 표시합니다! 그 이상 표시하려 시도할 경우 null을 대신 반환합니다.

# prerequisites
 Mediawiki >= 1.37.0, < 1.42.0
 미디어위키 extensions 폴더 내에 VoteNY 확장 기능 설치 필수
 
 [VoteNY](https://www.mediawiki.org/wiki/Extension:VoteNY) extension required

 # COPYRIGHTS
 Maintainer: Xen-alpha
 Licensed under the MIT License
 ```
Copyright (c) 2024 Maniac Xena(Metagen)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 ```
