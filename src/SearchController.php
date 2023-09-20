<?php
/**
 * @author      Peter Chigozie(NG) peterujah
 * @copyright   Copyright (c), 2019 Peter(NG) peterujah
 * @license     MIT public license
 */
namespace Peterujah\NanoBlock;

/**
 * Class SearchController.
 */
class SearchController{
	/**
     * @var string SQL patterns 
    */
	public const START_WITH_QUERY = "query%";
	public const END_WITH_QUERY = "%query";
	public const HAVE_ANY_QUERY = "%query%";
	public const HAVE_SECOND_QUERY = "_query%";
	public const START_WITH_QUERY_2LENGTH = "query_%";
	public const START_WITH_QUERY_3LENGTH = "query__%";
	public const START_END_WITH_QUERY = "query%query";

	/**
     * @var string SearchController algorithms 
    */
	public const OR = "OR";
	public const AND = "AND";
	public const NAND = "NAND";
	public const NOR = "NOR";

	/**
     * @var string SQL search keywords 
    */
	public const LIKE = "LIKE";
	public const NOT_LIKE = "NOT LIKE";

	/**
     * @var string SQL Query 
    */
	private string $QueryCondition = '';

	/**
     * @var string Search query algorithm that needs to be used
    */
	private string $searchAlgorithm;

	/**
     * @var string Search request query value
    */
	private mixed $searchQuery = '';

	/**
     * @var array Search request query values
    */
	private array $searchQueryBuilder = [];

	/**
     * @var array MYSQL database table rows to search form
    */
	private array $paramArray = [];

	/**
     * @var string MYSQL database table row for tag value
    */
	private string $paramTags;

	/**
     * @var string SQL LIKE query operator to be used
    */
	private string $operators;

	/**
     * @var string SQL query prefix
    */
	private string $queryStart;

	/**
     * @var string SQL query suffix
    */
	private string $queryEnd;

	public function __construct(string $algorithm = self::OR) {
		$this->searchAlgorithm = $algorithm;
		$this->operators = self::END_WITH_QUERY;
		$this->queryStart = self::LIKE;
	 	$this->queryEnd = self::OR;
	}
		
	/**
     * Set database search table  columns.
     *
     * @param array          $param columns
     */
	public function setParameter(array $param=[]): void{
		$this->paramArray = $param;
	}

	/**
     * Set initial SQL queries.
     *
     * @param string          $query query
     */
	public function setSQLQuery(string $query): void{
		$this->QueryCondition = $query;
	}

	/**
     * Set database search operator pattern.
     *
     * @param string          $pattern name
     */
	public function setOperators(string $pattern): void{
		$this->operators = $pattern;
	}

	/**
     * Set database tag table column name.
     *
     * @param string          $column name
     */
	public function setTags(string $column): void{
		$this->paramTags = $column;
	}

	/**
     * Set search query value.
     *
     * @param string          $query query value
     * @return object|SearchController 
     */
	public function setQuery(string $query): SearchController{
		$this->searchQuery = strtolower(htmlspecialchars($query, ENT_QUOTES, "UTF-8"));
		return $this;
	}
	
	/**
     * Set query prefix string.
     *
     * @param string          $start query prefix
     */
	public function setStart(string $start): void{
		$this->queryStart = $start;
	}

	/**
     * Set query suffix string.
     *
     * @param string          $end query suffix
     */
	public function setEnd(string $end): void{
		$this->queryEnd = $end;
	}

	/**
     * Split search query value by space.
     */
	public function split(): void{
		if(strpos($this->searchQuery, " ") !== false) {
			$this->searchQuery = explode(" ", $this->searchQuery);
		}
		//$this->searchQuery = [$this->searchQuery];
	}

	/**
     * Create SQL query from the specified pattern.
     *
     * @param string          $value query value
     * @return string query
     */

	private function format(string $value): string {
		$queryString = "";
		foreach($this->paramArray as $col){
			$sqlQuery = str_replace("query", $value, $this->operators);
			$queryString .= "LOWER($col) {$this->queryStart} '{$sqlQuery}' {$this->queryEnd} ";
		}
		return $queryString;
	}

	private function buildQuery(): string{
		return rtrim($this->format($this->searchQuery) , " {$this->queryEnd} ");
	}

	private function buildArrayQuery(int $i = 0): string{
		return rtrim($this->format($this->searchQuery[$i]) , " {$this->queryEnd} ");;
	}

	/**
     * Determine which search method to use while creating a query.
     *
     * @return string SQL query
     */
	private function buildSQL(): string{
		$sql = "";
		if(!empty($this->paramTags)){
			if(is_array($this->searchQuery)) {
				foreach($this->searchQuery as $tag){
					$sql .= "FIND_IN_SET('{$tag}',{$this->paramTags}) {$this->queryEnd} ";
				}
				$sql = rtrim($sql , " {$this->queryEnd} ");
			}else{
				$sql .= "FIND_IN_SET('{$this->searchQuery}',{$this->paramTags})"; 
			}
		}else{
			if(is_array($this->searchQuery)) {
				$arrayCount = count($this->searchQuery); 
				for ($i = 0; $i < $arrayCount; $i++) {
					$sql .= $this->buildArrayQuery($i);
					if ($i != $arrayCount - 1) { 
						$sql .=  " {$this->queryEnd} ";
					}
				}
			} else {
				$sql .= $this->buildQuery();
			}
		}
		return $sql;
	}

	/**
     * Execute search query.
     *
     * @return string SQL query
     */
	public function getQuery(): string{
		if (!empty($this->searchQuery)){ 
			if (!empty($this->searchQuery)){ 
				if (!empty($this->QueryCondition)){ 
					$this->QueryCondition .= " AND (";
				}else { 
					$this->QueryCondition .=  " WHERE (";
				} 
				
				switch ($this->searchAlgorithm){
					
					case self::OR: 
						$this->setStart(self::LIKE);
						$this->setEnd(self::OR);
						$this->QueryCondition .= $this->buildSQL(); 
						$this->QueryCondition .= " )";
					break; 
				
					case self::AND: 
						$this->setStart(self::LIKE);
						$this->setEnd(self::AND);
						$this->QueryCondition .= $this->buildSQL(); 
						$this->QueryCondition .= " )";
					break; 
				
					case self::NAND: 
						$this->setStart(self::NOT_LIKE);
						$this->setEnd(self::AND);
						$this->QueryCondition .= $this->buildSQL(); 
						$this->QueryCondition .= " )";
					break; 
				
					case self::NOR: 
						$this->setStart(self::NOT_LIKE);
						$this->setEnd(self::OR);
						$this->QueryCondition .= $this->buildSQL(); 
						$this->QueryCondition .= " )";
					break; 
					default: 
						$this->setStart(self::LIKE);
						$this->setEnd(self::OR);
						$this->QueryCondition .= $this->buildSQL(); 
						$this->QueryCondition .= " )";
					break;
				}
			} 
		}
		return $this->QueryCondition;
	}
}
