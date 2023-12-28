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
	private string $queryCondition = '';

	/**
     * @var string Search query algorithm that needs to be used
    */
	private string $searchAlgorithm;

	/**
     * @var string|array Search request query value
    */
	private string|array $searchQuery = '';


	/**
     * @var array MYSQL database table rows to search form
    */
	private array $paramArray = [];

	/**
     * @var string MYSQL database table row for tag value
    */
	private string $paramTags = '';

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
     * @param array $param columns
	 * 
	 * @return self
     */
	public function setParameter(array $param): self
	{
		$this->paramArray = $param;

		return $this;
	}

	/**
     * Set initial SQL queries.
     *
     * @param string $query query
	 * 
	 * @return self
     */
	public function setIniQuery(string $query): self
	{
		$this->queryCondition = $query;

		return $this;
	}

	/**
     * Set database search operator pattern.
     *
     * @param string $pattern name
	 * 
	 * @return self
     */
	public function setOperators(string $pattern): self
	{
		$this->operators = $pattern;

		return $this;
	}

	/**
     * Set database tag table column name.
     *
     * @param string $column name
	 * 
	 * @return self
     */
	public function setTags(string $column): self
	{
		$this->paramTags = $column;

		return $this;
	}

	/**
     * Set search query value.
     *
     * @param string $query query value
	 * 
     * @return self
    */
	public function setQuery(string $query): self
	{
		$this->searchQuery = strtolower(htmlspecialchars($query, ENT_QUOTES, "UTF-8"));

		return $this;
	}
	
	/**
     * Set query prefix string.
     *
     * @param string $start query prefix
	 * 
	 * @return self
     */
	public function setStart(string $start): self
	{
		$this->queryStart = $start;

		return $this;
	}

	/**
     * Set query suffix string.
     *
     * @param string  $end query suffix
	 * 
	 * @return self
     */
	public function setEnd(string $end): self
	{
		$this->queryEnd = $end;

		return $this;
	}

	/**
     * Split search query value by space.
	 * 
	 * @return void
     */
	public function split(): void
	{
		if(is_string($this->searchQuery) && strpos($this->searchQuery, " ") !== false) {
			$this->searchQuery = explode(" ", $this->searchQuery);
		}
		//$this->searchQuery = [$this->searchQuery];
	}

	/**
     * Create SQL query from the specified pattern.
     *
     * @param string $value query value
	 * 
     * @return string $query
     */
	private function format(string $value): string 
	{
		$query = "";
		foreach($this->paramArray as $col){
			$sqlQuery = str_replace("query", $value, $this->operators);
			$query .= "LOWER($col) {$this->queryStart} '{$sqlQuery}' {$this->queryEnd} ";
		}
		return $query;
	}

	/**
     * Build query string 
	 * 
     * @return string query
    */
	private function buildQuery(): string
	{
		return rtrim($this->format($this->searchQuery) , " {$this->queryEnd} ");
	}

	/**
     * Build query array 
	 * 
	 * @param int $index array index 
	 * 
     * @return string query
    */
	private function buildArrayQuery(int $index = 0): string
	{
		return rtrim($this->format($this->searchQuery[$index]) , " {$this->queryEnd} ");;
	}

	/**
     * Determine which search method to use while creating a query.
     *
     * @return string SQL query
    */
	private function buildSQL(): string
	{
		$sql = '';
		if($this->paramTags === ''){
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
		}else{
			if(is_array($this->searchQuery)) {
				foreach($this->searchQuery as $tag){
					$sql .= "FIND_IN_SET('{$tag}',{$this->paramTags}) {$this->queryEnd} ";
				}
				$sql = rtrim($sql , " {$this->queryEnd} ");
			}else{
				$sql .= "FIND_IN_SET('{$this->searchQuery}',{$this->paramTags})"; 
			}
		}
		return $sql;
	}

	/**
     * Execute search query.
     *
     * @return string SQL query
    */
	public function getQuery(): string
	{
		if ($this->searchQuery === '' || $this->searchQuery === []){ 
			return $this->queryCondition;
		}

		if($this->queryCondition === ''){
			$this->queryCondition .=  " WHERE (";
		}else{
			$this->queryCondition .= " AND (";
		}
		
		switch ($this->searchAlgorithm){
			
			case self::OR: 
				$this->setStart(self::LIKE);
				$this->setEnd(self::OR);
				$this->queryCondition .= $this->buildSQL(); 
				$this->queryCondition .= " )";
			break; 
		
			case self::AND: 
				$this->setStart(self::LIKE);
				$this->setEnd(self::AND);
				$this->queryCondition .= $this->buildSQL(); 
				$this->queryCondition .= " )";
			break; 
		
			case self::NAND: 
				$this->setStart(self::NOT_LIKE);
				$this->setEnd(self::AND);
				$this->queryCondition .= $this->buildSQL(); 
				$this->queryCondition .= " )";
			break; 
		
			case self::NOR: 
				$this->setStart(self::NOT_LIKE);
				$this->setEnd(self::OR);
				$this->queryCondition .= $this->buildSQL(); 
				$this->queryCondition .= " )";
			break; 
			default: 
				$this->setStart(self::LIKE);
				$this->setEnd(self::OR);
				$this->queryCondition .= $this->buildSQL(); 
				$this->queryCondition .= " )";
			break;
		}
	
		return $this->queryCondition;
	}
}
