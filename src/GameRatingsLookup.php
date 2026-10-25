<?php
// namespace
namespace MediaWiki\Extension\SectionRatings;

// use statement
use MediaWiki\Title\NamespaceInfo;
use MediaWiki\Title\TitleValue;
use Wikimedia\Rdbms\IConnectionProvider;

// 분류 내 평균 별점 상위 문서 조회 (REST 핸들러와 documentratinglist 파서 함수가 함께 사용)

// class
class GameRatingsLookup {
	public const MIN_COUNT = 1;
	public const MAX_COUNT = 100;

	private IConnectionProvider $dbProvider;
	/** @var \WANObjectCache|\Wikimedia\ObjectCache\WANObjectCache 1.43부터 네임스페이스가 바뀌었으므로 타입 힌트를 쓰지 않음 */
	private $cache;
	private NamespaceInfo $namespaceInfo;

	public function __construct( IConnectionProvider $dbProvider, $cache, NamespaceInfo $namespaceInfo ) {
		$this->dbProvider = $dbProvider;
		$this->cache = $cache;
		$this->namespaceInfo = $namespaceInfo;
	}

	/**
	 * 평균 별점이 3.0 이상이고 평가 수가 2명 이상인 문서를 평균 별점 내림차순, 같으면 평가 수 내림차순으로 반환한다.
	 *
	 * @param string $category cl_to에 그대로 쓰이는 분류 이름(공백 대신 언더바)
	 * @param int $count 반환할 갯수
	 * @param int $cacheTtl 결과를 캐시할 시간(초). 0 이하면 캐시하지 않음
	 * @return array[] 각 원소는 ['title' => TitleValue(본문 문서), 'votecount' => string, 'score' => string]
	 */
	public function getTopRated( string $category, int $count, int $cacheTtl = 0 ): array {
		if ( $cacheTtl > 0 ) {
			$rows = $this->cache->getWithSetCallback(
				$this->cache->makeKey( 'sectionratings', 'toprated', $category, $count ),
				$cacheTtl,
				fn () => $this->queryTopRated( $category, $count )
			);
		} else {
			$rows = $this->queryTopRated( $category, $count );
		}

		$result = [];
		foreach ( $rows as $row ) {
			$result[] = [
				'title' => new TitleValue( $row['namespace'], $row['dbkey'] ),
				'votecount' => $row['votecount'],
				'score' => $row['score'],
			];
		}
		return $result;
	}

	/**
	 * 캐시에 넣을 수 있도록 TitleValue가 아닌 배열로 반환한다.
	 */
	private function queryTopRated( string $category, int $count ): array {
		$dbr = $this->dbProvider->getReplicaDatabase();

		// 값은 쿼리 빌더가 바인딩/이스케이프 처리함
		// 카테고리로 필터링 + 평점 3 이상만 결과로 반환 + 자기 추천 방지를 위한 2명 이상의 평가 요구
		$res = $dbr->newSelectQueryBuilder()
			->select( [
				'page_namespace' => 'page.page_namespace',
				'page_title' => 'page.page_title',
				'votecount' => 'COUNT(Vote.vote_value)',
				'vote_average' => 'AVG(Vote.vote_value)',
			] )
			->from( 'categorylinks' )
			->join( 'Vote', null, 'categorylinks.cl_from = Vote.vote_page_id' )
			->join( 'page', null, 'page.page_id = Vote.vote_page_id' )
			->where( [ 'categorylinks.cl_to' => $category ] )
			->groupBy( [ 'Vote.vote_page_id', 'page.page_namespace', 'page.page_title' ] )
			->having( [ 'vote_average >= 3', 'votecount >= 2' ] )
			->orderBy( [ 'vote_average DESC', 'votecount DESC' ] )
			->limit( $count )
			->caller( __METHOD__ )
			->fetchResultSet();

		$rows = [];
		foreach ( $res as $row ) {
			$rows[] = [
				// 토론 페이지에 위젯이 붙는 것을 가정하고 본문 문서의 이름공간을 가져옴
				'namespace' => $this->namespaceInfo->getSubject( (int) $row->page_namespace ),
				'dbkey' => $row->page_title,
				'votecount' => (string) $row->votecount,
				'score' => (string) $row->vote_average,
			];
		}
		return $rows;
	}
}
