# mediawiki-extension-DocumentRatings(구 sectionratings)

이 확장기능은 미디어위키 용으로 설계된 VoteNY 확장 기능으로 만든 특정 콘테스트의 데이터베이스 테이블에서 현재 3.0 이상의 평균 별점 상위 n개의 항목에 관한 정보를 JSON 포맷으로 반환합니다. 문서 안에서 같은 결과를 게임카드로 바로 출력하는 파서 함수 `{{#documentratinglist:}}`도 제공합니다. 또, 특정 이름 공간 내 특정 분류가 달린 문서의 수를 세는 보조 기능도 가지고 있습니다. 이 밖에 이용가(전체/12/15)별 게임 목록과 사용자별 평가 기록을 조회하는 API도 제공합니다.

이 확장 기능은 [리버티게임 위키](https://libertyga.me) 내에서 사용할 목적으로 만들어졌습니다.

그래서 영어가 아닌 한국어로 도큐먼트가 제공됩니다.

# 설치 및 사용법

1. 미디어위키의 extensions 폴더에 git clone합니다. 이때 적절한 폴더 이름(예: DocumentRatings)으로 clone하도록 파라미터를 설정하세요
2. LocalSettings.php에서 `wfLoadExtension( 'DocumentRatings' );`로 확장 기능을 로드합니다. (인자는 clone한 폴더 이름입니다)
3. 0.6.0 이상으로 업데이트할 때는 파서 함수의 매직 워드를 등록하기 위해 `php maintenance/run.php rebuildLocalisationCache`를 한 번 실행해야 할 수 있습니다. 로컬라이제이션 캐시를 수동으로 갱신하는 설정(`$wgLocalisationCacheConf['manualRecache']`)이면 필수입니다.

# 파서 함수 사용법

위키 문서 안에서 `{{#documentratinglist:분류 이름|불러올 갯수}}`로 평균 별점 상위 게임의 게임카드를 바로 출력할 수 있습니다. JavaScript나 REST API 호출 없이 서버에서 문서를 파싱할 때 함께 렌더링되며, 결과는 파서 캐시에 저장됩니다.

```wikitext
{{#documentratinglist:리버티게임|4}}
{{#documentratinglist:어드벤처 게임|{{{1|8}}}}}
```

- 분류 이름은 `분류:` 접두사, 공백, 언더바를 모두 쓸 수 있습니다. 비워 둘 수 없습니다.
- 불러올 갯수는 1 이상 100 이하이며, 생략하면 4입니다.
- 선정 기준은 `get document ratings` API와 같습니다(평균 별점 3.0 이상, 평가 수 2명 이상, 평균 별점 내림차순).
- 출력은 `<div class="documentratinglist">` 안에 게임카드 틀(`{{게임카드}}`) 하나가 들어가는 구조이므로, `.documentratinglist > .gamecards`를 CSS로 꾸밀 수 있습니다.
- 조건에 맞는 문서가 없으면 `MediaWiki:Documentratinglist-empty` 메시지가 출력됩니다. 이 메시지를 `-`로 바꾸면 아무것도 출력하지 않습니다.
- 인자가 잘못되면 빨간색 오류 문구(`<strong class="error">`)가 출력됩니다.
- 비싼 파서 함수로 취급되므로 문서당 호출 횟수는 `$wgExpensiveParserFunctionLimit`(기본값 100)의 영향을 받습니다.

평가 값이 바뀌어도 문서가 다시 파싱되기 전까지는 이전 결과가 보입니다. 문서의 파서 캐시 유지 시간은 `LocalSettings.php`에서 설정합니다.

```php
// 기본값 3600(초). 0이면 이 파서 함수를 쓰는 문서는 파서 캐시를 쓰지 않습니다.
$wgDocumentRatingListCacheExpiry = 3600;
```

문서를 바로 갱신하려면 문서에서 Purge를 실행하세요. 파서 캐시가 비어 있을 때 DB 부하를 줄이기 위해 조회 결과도 10분간 별도로 캐시되므로, 반영까지 최대 10분이 더 걸릴 수 있습니다.

# REST API 사용 예시

모든 엔드포인트는 GET 요청이며 `(URL)/rest.php/sectionratings/...` 아래에 있습니다. (확장 기능의 옛 이름인 sectionratings가 경로에 그대로 사용됩니다. 버전 접두사(v0/v1)는 엔드포인트마다 다르니 아래 경로를 그대로 사용하세요.)

파라미터가 잘못되면 HTTP 400과 함께 다음 형태의 JSON이 반환됩니다.

```json
{
  "result": "FAIL: ...", // 오류 내용(예: 분류 이름이 비어 있음, 갯수가 1~100 범위를 벗어남)
  "httpCode": 400,
  "httpReason": "Bad Request"
}
```

## get document ratings

`(URL)/rest.php/sectionratings/v0/ratings/{분류 이름}/{불러올 갯수}`

- 불러올 갯수는 1 이상 100 이하입니다.
- 평균 별점이 3.0 이상이고 평가 수가 2명 이상인 문서만 반환합니다(자기 추천 방지). 평균 별점 내림차순, 같으면 평가 수 내림차순으로 정렬됩니다.

반환값은 다음과 같습니다.

```json
{
  "result": "SUCCESS",
  "category": "...", // 분류 이름
  "gameList": [
    {
      "pagename": "...", // 평가 위젯이 토론 문서에 붙는다고 가정하여 본문 문서의 이름을 반환합니다.
      "votecount": "2", // 평가 수(문자열)
      "score": "4.5" // 평균 별점(문자열)
    },
    {
      "pagename": "...",
      "votecount": "3",
      "score": "3"
    },
    ...
  ],
  "parseResult": "...", // (deprecated: 파서 함수 {{#documentratinglist:}}로 대체됨) 리버티게임 게임카드 틀을 파싱한 HTML 결과물이 "mw-parser-output" div 태그에 싸인 채로 포함됩니다.
  "httpCode": 200,
  "httpReason": "OK"
}
```

- 참고: 게임카드 틀은 Parse API(api.php)를 호출하지 않고 미디어위키 파서로 직접 렌더링하며, 익명 사용자 기준 옵션이 적용됩니다.

## get filtered game list

`(URL)/rest.php/sectionratings/v1/ratings/{분류 이름}/filter/{이용가}/{불러올 갯수}/{오프셋}`

- 이용가는 `all`, `12`, `15` 중 하나이며 각각 `전체_이용가_게임`, `12세_이용가_게임`, `15세_이용가_게임` 분류에 속한 문서를 가장 최근에 수정된 순으로 반환합니다.
- 불러올 갯수는 1 이상 100 이하, 오프셋은 0 이상의 정수입니다.
- 주의: `{분류 이름}`은 현재 필터링에 사용되지 않고 응답의 `category`로 그대로 반환될 뿐입니다. 실제 필터링은 이용가 분류로만 이루어집니다.

반환값은 다음과 같습니다.

```json
{
  "result": "SUCCESS",
  "category": "...", // 요청한 분류 이름
  "gameList": ["...", "..."], // 문서 이름의 배열
  "parseResult": "...", // 위와 동일한 게임카드 HTML 결과물
  "httpCode": 200,
  "httpReason": "OK"
}
```

## categorycounter

`(URL)/rest.php/sectionratings/v0/categorycounter/{분류 이름, |(파이프 문자)로 구분하여 여러개를 반환}/{네임스페이스 번호}`

- 네임스페이스 번호는 0 이상의 정수여야 하며, 아니면 400을 반환합니다.
- 분류에 직접 속한 문서(문서 타입)만 세며 하위 분류와 파일은 제외합니다.

반환값은 다음과 같습니다.

```json
{
  "result": "SUCCESS",
  "category": ["...", ...], // 파이프 문자로 나눈 분류 이름 배열
  "count": ["7", ...], // 분류 이름 순서와 같은 문자열 배열로 전달됩니다.
  "namespace": "1", // 요청한 이름공간 번호(문자열)
  "httpCode": 200,
  "httpReason": "OK"
}
```

## get user's ratings

`(URL)/rest.php/sectionratings/v1/userratings/{사용자 이름}`

- 평가 값 내림차순으로 정렬됩니다. 존재하지 않거나 평가 기록이 없는 사용자는 빈 배열을 반환합니다.

반환값은 다음과 같습니다.

```json
{
  "result": "SUCCESS",
  "user": "...", // 사용자 이름
  "ratelist": [
    {
      "pagename": "...", // 본문 문서의 이름
      "score": "4" // 해당 사용자가 준 평가 값(문자열)
    },
    ...
  ],
  "httpCode": 200,
  "httpReason": "OK"
}
```

# prerequisites

MediaWiki >= 1.42.0
미디어위키 extensions 폴더 내에 VoteNY 확장 기능 설치 필수

[VoteNY](https://www.mediawiki.org/wiki/Extension:VoteNY) extension required

# COPYRIGHTS

Maintainer: Xen-alpha
Licensed under the MIT License

```
Copyright (c) 2024 Maniac Xena(Metagen, senouis)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
```
