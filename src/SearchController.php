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
	private $QueryCondition = "";

	/**
     * @var string Search query algorithm that needs to be used
    */
	private $searchAlgorithm;

	/**
     * @var string Search request query value
    */
	private $searchQuery = null;

	/**
     * @var array MYSQL database table rows to search from
    */
	private $paramArray = [];

	/**
     * @var string MYSQL database table row for tag value
    */
	private $paramTags;

	/**
     * @var string SQL LIKE query operator to be use
    */
	private $operators;

	/**
     * @var string SQL query prefix
    */
	private $queryStart;

	/**
     * @var string SQL query suffix
    */
	private $queryEnd;

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
	public function setParameter(array $param=[]): self{
		$this->paramArray = $param;
		return $this;
	}

	/**
     * Set initial SQL queries.
     *
     * @param string          $query query
     */
	public function setSQLQuery(string $query): self{
		$this->QueryCondition = $query;
		return $this;
	}

	/**
     * Set database search operator pattern.
     *
     * @param string          $pattern name
     */
	public function setOperators(string $pattern): self{
		$this->operators = $pattern;
		return $this;
	}

	/**
     * Set database tag table column name.
     *
     * @param string          $column name
     */
	public function setTags(string $column): self{
		$this->paramTags = $column;
		return $this;
	}

	/**
     * Set search query value.
     *
     * @param string          $query query value
     * @return object|SearchController 
     */
	public function setQuery(mixed $query): self{
		$this->searchQuery = htmlspecialchars((string) $query, ENT_QUOTES, "UTF-8");
		return $this;
	}
	
	/**
     * Set query prefix string.
     *
     * @param string          $str query prefix
     */
	public function setStart(string $str): self{
		$this->queryStart = $str;
		return $this;
	}

	/**
     * Set query suffix string.
     *
     * @param string          $str query suffix
     */
	public function setEnd(string $str): self{
		$this->queryEnd = $str;
		return $this;
	}

	/**
     * Split search query value by space.
     */
	public function split(): void{
		if(strpos($this->searchQuery, " ") !== false) {
			$this->searchQuery = explode(" ", $this->searchQuery);
			return;
		}
		$this->searchQuery = [$this->searchQuery];
	}

	/**
     * Create SQL query from the specified pattern.
     *
     * @param string          $value query value
     * @return string query
     */

	private function format(string $value): string{
		$queryString = "";
		foreach($this->paramArray as $col){
			$sqlQuery = str_replace("query", $value, $this->operators);
			$queryString .=  $col . " {$this->queryStart} '{$sqlQuery}' {$this->queryEnd} ";
		}
		return $queryString;
	}

	/**
     * Get query from string
     * @return string query
     */
	private function getQueryFromString(): string{
		return rtrim($this->format($this->searchQuery) , " {$this->queryEnd} ");
	}

	/**
     * Get query from array 
     *
     * @param int  $index query index
     * @return string query
     */
	private function getQueryFromArray(int $index = 0): string {
		return rtrim($this->format($this->searchQuery[$index]) , " {$this->queryEnd} ");;
	}

	/**
     * Determine which search method to use while creating query.
     *
     * @return string SQL query
     */
	private function buildSearchQueryMethod(): string{
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
					$sql .= $this->getQueryFromArray($i);
					if ($i != $arrayCount - 1) { 
						$sql .=  " {$this->queryEnd} ";
					}
				}
			} else {
				$sql .= $this->getQueryFromString();
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
						$this->QueryCondition .= $this->buildSearchQueryMethod(); 
						$this->QueryCondition .= " )";
					break; 
				
					case self::AND: 
						$this->setStart(self::LIKE);
						$this->setEnd(self::AND);
						$this->QueryCondition .= $this->buildSearchQueryMethod(); 
						$this->QueryCondition .= " )";
					break; 
				
					case self::NAND: 
						$this->setStart(self::NOT_LIKE);
						$this->setEnd(self::AND);
						$this->QueryCondition .= $this->buildSearchQueryMethod(); 
						$this->QueryCondition .= " )";
					break; 
				
					case self::NOR: 
						$this->setStart(self::NOT_LIKE);
						$this->setEnd(self::OR);
						$this->QueryCondition .= $this->buildSearchQueryMethod(); 
						$this->QueryCondition .= " )";
					break; 
					default: 
						$this->setStart(self::LIKE);
						$this->setEnd(self::OR);
						$this->QueryCondition .= $this->buildSearchQueryMethod(); 
						$this->QueryCondition .= " )";
					break;
				}
			} 
		}
		return $this->QueryCondition;
	}
}
