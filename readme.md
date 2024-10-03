# mediawiki-extension-sectionratings

이 확장기능은 미디어위키 용으로 설계된 VoteNY 확장 기능으로 만든 특정 콘테스트의 데이터베이스 테이블에서 현재 3.0 이상의 평균 별점 상위 n개의 항목에 관한 정보를 JSON 포맷으로 반환합니다. 또, 특정 이름 공간 내 특정 분류가 달린 문서의 수를 세는 보조 기능도 가지고 있습니다.

이 확장 기능은 [리버티게임 위키](https://libertyga.me) 내에서 사용할 목적으로 만들어졌습니다.

그래서 영어가 아닌 한국어로 도큐먼트가 제공됩니다.

# 설치

1. 미디어위키의 extensions 폴더에 git clone합니다. 이때 적절한 폴더 이름(예: SectionRatings)으로 clone하도록 파라미터를 설정하세요
2. wgLoadExtensions 함수로 LocalSettings.php에서 확장 기능을 로드합니다.
3. 아래 '사전 작업'을 필요한 만큼 시행합니다.
## 사전 작업
* 먼저 '틀:게임카드'(Template:게임카드)라는 이름의 틀 문서를 위키 사이트 내에 만듭니다. 이 틀에는 당신이 탐색할 문서(본문)에 딸린 토론 문서에 붙은 분류에 따라 본문에 대한 정보를 보여줘야 합니다. 이 틀이 없으면 해당 틀 문서로 가는 없는 페이지 링크가 미디어위키 문서 파싱 결과로 반환되므로, 게임카드 틀 렌더링 없이 단순히 목록 데이터만 얻어올 것이라면 만들지 않아도 됩니다.
  * 여기서 본문 문서는 짝수 ID를 가지는 이름 공간(Main, Project, User, Template, etc.), 토론 문서는 홀수 ID를 가지는 이름공간(예: Talk, User Talk, Project Talk, Template Talk, etc.)을 말합니다.
* 원하는 문서에 딸린 '토론 문서'에 원하는 분류와 VoteNY 별점 위젯을 삽입하고 저장합니다.
* 위젯으로 별점 평가를 토론 문서에 대해 시행합니다.

# REST API 사용 예시

## get game ratings
아래의 REST API 요청을 시행하면 토론 문서를 탐색한 후 본문 문서에 대한 정보를 담은 게임카드 틀에 대한 DOM 렌더링 결과를 반환합니다.

`(URL)/rest.php/sectionratings/v0/ratings/{분류 이름}/{불러올 갯수}`

반환값은 다음과 같습니다.

```json
{
    "result": "SUCCESS", // "FAIL:" 로도 시작할 수 있습니다(예: 너무 많거나 적은 게임 정보 갯수). 이 경우 공백을 '-' 문자로 치환한 한 줄 오류 내용을 담은 "error"가 추가로 전달되어야 합니다.
    "category": "...", // 분류 이름
    "gamelist": [ //3.0 이하의 평가를 가진 게임은 반환하지 않습니다.
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
    "httpCode" : 200 // 오류가 발생할 경우 미디어위키의 내부 코드 정의에 의존합니다(예: 타입 오류시 400),
    "httpReason" : "OK"
}
```

- 주의: parseResult는 미디어위키 Parse API 제한으로 인해 최대 50개의 게임만 표시합니다! 그 이상 표시하려 시도할 경우 null을 대신 반환합니다.

## categorycounter
어떤 분류(들)에 해당하는 문서가 특정 이름공간에 몇 개 있는지 결과 목록을 반환하는 API입니다. 다음과 같이 호출합니다. 

`(URL)/rest.php/sectionratings/v0/categorycounter/{분류 이름, |(파이프 문자)로 구분하여 여러개를 반환}/{네임스페이스 번호}`

반환값은 다음과 같습니다.

```json
{
  "result": "SUCCESS", // "FAIL:" 로도 시작할 수 있습니다(예: 너무 많거나 적은 게임 정보 갯수). 이 경우 공백을 '-' 문자로 치환한 한 줄 오류 내용을 담은 "error"가 추가로 전달되어야 합니다.
  "category": "...", // 분류 이름
  "count": ["7", ...], // 문자열 배열로 전달됩니다.
  "namespace": "1", // 0 이상의 이름공간 번호로 가능
  "httpCode": 200, // 오류가 발생할 경우 미디어위키의 내부 코드 정의에 의존합니다(예: 타입 오류시 400),
  "httpReason": "OK"
}
```

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
