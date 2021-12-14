# PHPSearchController

## Installation

Installation is super-easy via Composer:
```md
composer require peterujah/php-search-controller
```

```php 
use Peterujah\NanoBlock\SearchController;
$search = new SearchController(SearchController::OR);
$search->setOperators(SearchController::HAVE_ANY_QUERY);
```

# Usages

```php 
$searchQuery = "PHP Code";
$searchByTags = false;

$search->setQuery($searchQuery)->split();

if(!$searchByTags){
    $search->setParameter(array(
        'code_title', 
        'code_description', 
        'code_info'
    ));
}else{
    $search->setTags("code_tags");
}
//var_export($search->getQuery());
```
Set inital query and pass search query to your mysql connection

```php 
$search->setSQLQuery("SELECT * FROM code WHERE id = 1323");
$db->conn()->prepare($search->getQuery());
$db->conn()->execute();		
$resault = $db->conn()->getAll();
$db->conn()->free();
```
OR bulid it with other sql query like below in your mysql connection
```php 
$db->conn()->prepare("SELECT * FROM code " . $search->getQuery() . " AND id = 1323");
$db->conn()->execute();		
$resault = $db->conn()->getAll();
$db->conn()->free();
```

# Refrence

Specify search operator `$search->setOperators(SearchController::HAVE_ANY_QUERY)`

| Search Operators         | Description                                                                       |
|--------------------------|-----------------------------------------------------------------------------------|
| START_WITH_QUERY         | Finds any values that start with "query"                                          |
| END_WITH_QUERY           | Finds any values that end with "query"                                            |
| HAVE_ANY_QUERY           | Finds any values that have "query" in any position                                |
| HAVE_SECOND_QUERY        | Finds any values that have "query" in the second position                         |
| START_WITH_QUERY_2LENGTH | Finds any values that start with "query" and are at least 2 characters in length  |
| START_WITH_QUERY_3LENGTH | Finds any values that start with "query" and are at least 3 characters in length  |
| START_END_WITH_QUERY     | Finds any values that start with "query" and ends with "query"                    |


Initalise search class with a method `new SearchController(SearchController::OR)`

| Search Methods         | Description                                                                         |
|------------------------|-------------------------------------------------------------------------------------|
| OR                     | Retrieve result with any one of search query                                        |
| AND                    | Retrieve result with the exact of search quer                                       |
| NAND                   | Retrieve result without the exact search query                                      |
| NOR                    | Retrieve result without any on of the search query                                  |

