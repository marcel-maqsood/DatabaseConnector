# Database-Connector | PersistentPDO

You can install this package with the following command:
```composer require marcel-maqsood/database-connector```

## Configuration
Our Database-Connector has a very easy configuration:
```
'showSqlLog' => false, //allows the PersistentPDO object to track each SQL that went through its API.
'persistentpdo' => [
    'dsn' => 'mysql:host=localhost;dbname=report_portal;port=3306', //- DSN string to connect to your database.
    'username' => 'root', //- The username which you want to use to connect.
    'password' => 'root' //- The password that your database-user has.
],
```

Also, you can either add our PersistentPDO within any of your ConfigProviders or directly inside your applications ```config\autoload\dependencies.global.php```:
```
'dependencies' => 
[
    'aliases' => 
    [
    ],
    'invokables' => [],
    'factories' => 
    [
        PersistentPDO::class => PersistentPDOFactory::class,
        PDORepository::class => PDORepositoryFactory::class,
    ],
],
```
The syntax is the same for ConfigProviders but by adding it into your dependencies, you will always have access to it at any module of your application without thinking about its configuration again.


## Functionality: ##



#### get(): ####

Fetches data from the database based on specified conditions.

This function constructs and executes a SQL query to retrieve data from the database.

It receives the follwing params:

* ```string``` $field - The field in which to search for the $identifier.
* ```string``` $table - The table in which to search.
* ```array|string``` $conditions - An array oe string of conditions to filter the results.
* ```array``` $joins - An array that defines which tables should be used for certain columns.
* ```array``` $groupDetails - An array that defines how multiple row values can be grouped.
* ```boolean``` $debug - if the generated SQL should be printed for debugging.


##### conditions are getting appended after "WHERE" don't include it. #####

The first condition should not include a 'logicalOperator' key, as there is no preceding condition to combine with, also it won't get used if defined.
Example:
```
$conditions = [
    [
        'field' => 'tableCol',
        'logicalOperator' => 'AND' // The logical operator to combine conditions: 'AND' or 'OR' (wont be used, as its the first condition)
        'operator' => 'LIKE',  // The operation that this condition must match.
        'queue' => 'admin',    // The value to look for in the 'name' field.
        'wildcard' => 'both'   // The mode for character matching: before | after | both | none .
    ]
];
$conditions could be a ```string``` aswell, example: ```"name = 'test'"```
```
this function returns a ```string``` (field value) or ```null```, if no row was found matching your conditions.



#### getAll(): ####

For further details, see get(); as this method uses the same syntax.

The main difference between get() and getAll() is self explaining: getAll() fetches every row that matches your conditions instead of just the first one.

It returns either ```null``` or an ```array``` filled with keys and values where every value is a ```string```.



#### getAllBase() ####
The main difference between getAll() and getAllBasae() is : getAllBase() requires a SQL string instead of plain table-names, conditions, joins or anything.

This is extremly useful when you need to make edge-case queries like checks for items that are present in one table but not in another as this is not supported by our other functions and thus require custom SQL statements.

Just like getAll() - It returns either ```null``` or an ```array``` filled with keys and values where every value is a ```string```.



#### update() ####

Updates data in the database based on specific conditions.

It receives the follwing params:
* ```array``` $updates - An array with fields and the new values:
  ```
  [
    'fieldName' => 'value',
    //...
  ]
  ```
* ```boolean``` $debug - if the generated SQL should be printed for debugging.
* ```array|string``` $conditions - this function uses the same syntax as get().

This functions returns a bool: the result of the given statement.


#### insert() ####
Inserts data in the database based on specific conditions.

It receives the follwing params:

* ```string``` $table - The table in which this entry should be added
* ```array``` $inserts - An array with fields and values.
* ```boolean``` $debug - if the generated SQL should be printed for debugging.

$inserts array is defined like $updates array of update().

This function either returns the ID of the inserted row or false, if there was an issue with the statement.



#### delete() ####
    
Deletes data in the database based on specific conditions.

It receives the follwing params:
* ```string``` $table - The table in which this entry should be added
* ```array|string``` $conditions - this function uses the same syntax as get().

This functions returns a bool: the result of the given statement.



### Condition Array ###
As stated earlier, conditions can either be a string (x = y) without appended "WHERE" or a condition array, which can look something like this:
```
'conditions' => [
    [
        'field'           => 'COL',
        'logicalOperator' => 'OR',
        'operator'        => 'LIKE',
        'wildcard'        => 'both',
        'tableOverride'   => 'TABLE',
    ],
    [
        'type'            => 'conditionalFallback',
        'logicalOperator' => 'OR',
        'if' => [
            'field'         => 'COL',
            'operator'      => 'IS NOT',
            'queue'         => null,
            'tableOverride' => 'TABLE',
        ],
        'then' => [
            'field'         => 'COL',
            'operator'      => 'LIKE',
            'wildcard'      => 'both',
            'tableOverride' => 'TABLE',
        ],
        'else' => [
            'field'         => 'otherCOL',
            'operator'      => 'LIKE',
            'wildcard'      => 'both',
            'tableOverride' => 'otherTABLE',
        ],
    ],
]
```

This insures that you can either use simple conditions for only certain cols and tables or even conditions that need to contain some more logic, as: if ´COL´ IS NOT NULL then use ´COL´ otherwise use ´otherCOL´

