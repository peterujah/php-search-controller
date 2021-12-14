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
	const START_WITH_QUERY = "query%";
	const END_WITH_QUERY = "%query";
	const HAVE_ANY_QUERY = "%query%";
	const HAVE_SECOND_QUERY = "_query%";
	const START_WITH_QUERY_2LENGTH = "query_%";
	const START_WITH_QUERY_3LENGTH = "query__%";
	const START_END_WITH_QUERY = "query%query";

	/**
     * @var string SearchController algorithms 
    */
	const OR = "OR";
	const AND = "AND";
	const NAND = "NAND";
	const NOR = "NOR";

	/**
     * @var string SQL search keywords 
    */
	const LIKE = "LIKE";
	const NOT_LIKE = "NOT LIKE";

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
	private $paramArray = array();

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

	public function __construct($algorithm = self::OR) {
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
	public function setParameter($param=array()){
		$this->paramArray = $param;
	}

	/**
     * Set initial SQL queries.
     *
     * @param string          $query query
     */
	public function setSQLQuery($query){
		$this->QueryCondition = $query;
	}

	/**
     * Set database search operator pattern.
     *
     * @param string          $pattern name
     */
	public function setOperators($pattern){
		$this->operators = $pattern;
	}

	/**
     * Set database tag table column name.
     *
     * @param string          $column name
     */
	public function setTags($column){
		$this->paramTags = $column;
	}

	/**
     * Set search query value.
     *
     * @param string          $query query value
     * @return object|SearchController 
     */
	public function setQuery($query){
		$this->searchQuery = htmlspecialchars($query, ENT_QUOTES, "UTF-8");
		return $this;
	}
	
	/**
     * Set query prefix string.
     *
     * @param string          $str query prefix
     */
	public function setStart($str){
		$this->queryStart = $str;
	}

	/**
     * Set query suffix string.
     *
     * @param string          $str query suffix
     */
	public function setEnd($str){
		$this->queryEnd = $str;
	}

	/**
     * Split search query value by space.
     */
	public function split(){
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

	private function format($value) {
		$queryString = "";
		foreach($this->paramArray as $col){
			switch ($this->operators){ 
				case self::START_WITH_QUERY: 
					$queryString .=  $col . " {$this->queryStart} '{$value}%' {$this->queryEnd} ";
				break; 
				case self::END_WITH_QUERY: 
					$queryString .= $col . " {$this->queryStart} '%{$value}' {$this->queryEnd} ";
				break; 
				case self::HAVE_ANY_QUERY: 
					$queryString .= $col . " {$this->queryStart} '%{$value}%' {$this->queryEnd} ";
				break; 
				case self::HAVE_SECOND_QUERY: 
					$queryString .= $col . " {$this->queryStart} '_{$value}%' {$this->queryEnd} ";
				break;
				case self::START_WITH_QUERY_2LENGTH: 
					$queryString .= $col . " {$this->queryStart} '{$value}_%' {$this->queryEnd} ";
				break;
				case self::START_WITH_QUERY_3LENGTH: 
					$queryString .= $col . " {$this->queryStart} '{$value}__%' {$this->queryEnd} ";
				break;
				case self::START_END_WITH_QUERY: 
					$queryString .= $col . " {$this->queryStart} '{$value}%{$value}' {$this->queryEnd} ";
				break;
				default:
					$queryString .= $col . " {$this->queryStart} '%{$value}%' {$this->queryEnd} ";
				break;
			}
		}
		return $queryString;
	}

	private function buildQuery(){
		return rtrim($this->format($this->searchQuery) , " {$this->queryEnd} ");
	}

	private function buildArrayQuery($i = 0){
		return rtrim($this->format($this->searchQuery[$i]) , " {$this->queryEnd} ");;
	}

	/**
     * Determine which search method to use while creating query.
     *
     * @return string SQL query
     */
	private function buildSQL(){
		$sql = "";
		if(!empty($this->paramTags)){
			if(is_array($this->searchQuery)) {
				foreach($this->searchQuery as $tag){
					$sql .= "FIND_IN_SET('{$tag}',{$this->paramTags}) {$this->queryEnd} "; //OR
				}
				$sql = rtrim($sql , " {$this->queryEnd} "); //OR
			}else{
				$sql .= "FIND_IN_SET('{$this->searchQuery}',{$this->paramTags})"; 
			}
		}else{
			if(is_array($this->searchQuery)) {
				$arrayCount = count($this->searchQuery); 
				for ($i = 0; $i < $arrayCount; $i++) {
					$sql .= $this->buildArrayQuery($i);
					if ($i != $arrayCount - 1) { 
						$sql .=  " {$this->queryEnd} "; //OR
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
	public function getQuery(){
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
