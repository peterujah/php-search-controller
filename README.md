# PHPSearchController

```php 
$search = new Peterujah\NanoBlock\SearchController(Peterujah\NanoBlock\SearchController::OR);
$search->setOperators($search::HAVE_ANY_QUERY);
```

# Usages

```php 
$searchQuery = "PHP Code";

$search->setQuery($searchQuery)->toArray();
    if(empty($_GET["tag"])){
        $search->setParameter(array(
            'c.code_title', 
            'c.code_description', 
            'c.code_info', 
            'c.code_tags',
            'c.code_category'
        ));
    }else{
        $search->setTags("c.code_tags");
    }
    var_export($search->getQuery());
