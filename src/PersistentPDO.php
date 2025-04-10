<?php

declare(strict_types=1);

namespace MazeDEV\DatabaseConnector;


use PDO;

class PersistentPDO 
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function getPDO() : PDO 
    {
        return $this->pdo;
    }

    /**
     * Fetches data from the database based on specified conditions.
     *
     * This function constructs and executes a SQL query to retrieve data from the database.
     *
     * @param string $field - The field in which to search for the $identifier.
     * @param string $table - The table in which to search.
     * @param array|string $conditions - An array oe string of conditions to filter the results.
     * @param array $joins - An array that defines which tables should be used for certain columns.
     * @param array $groupDetails - An array that defines how multiple row values can be grouped.
     * 
     * conditions are getting appended after "WHERE" don't include it.
     * 
     * The first condition should not include a 'logicalOperator' key, as there is no preceding condition to combine with, also it won't get used.
     *   Example:
     *   $conditions = [
     *      'name' => [
     *          'logicalOperator' => 'AND' // The logical operator to combine conditions: 'AND' or 'OR'
     *          'operator' => 'LIKE',  // The operation that this condition must match.
     *          'queue' => 'admin',    // The value to look for in the 'name' field.
     *          'wildcard' => 'both'   // The mode for character matching: before | after | both | none .
     *      ]
     *   ];
     * $conditions could be a @param string aswell, example: "name = 'test'"
     * @return string|null - The result of the query.
     */
    public function get(string $field, $table, array|string $conditions = [], array $joins = [], $groupDetails = [], $debug = false) : string|object|null
    {        
        //Generate sql and check if any condition is given
        $sql;
        $joins = $this->generateSQLJoins($joins);
        $finalConditionString = $this->generateConditionString($table, $conditions);
        $groups = "";

        if(!empty($groupDetails))
        {
            $groups = ",". $this->generateGroupingString($groupDetails['groups']);
        }


        $sql = "SELECT $table.*" . $groups . " FROM " . $table . $joins . $finalConditionString . ($groups != "" ? ' GROUP BY ' . $groupDetails['identifier'] . ' ' : ' ') . ';';

        if($debug)
        {
            var_dump($sql);
            exit;
        }

        $stmt = $this->pdo->prepare($sql);
        if (false === $stmt) 
        {
            throw new \Exception\RuntimeException('PDO error: ' . $e->getMessage());
        }

        $stmt->execute();
        $result = $stmt->fetchObject();

        if (! $result) 
        {
            return null;
        }

        if($field == '*')
        {
            return $result;
        }

        return strval($result->{$field});
    }

    /**
     * For further details, see $this->get(); as this method uses the same syntax.
     * This function will always return an array with all fields that got found with given conditions.
     * 
     * @return array|null - The result array of the query.
     */
    public function getAll($table, array|string $conditions = [], array $joins = [], $groupDetails = [], $orderBy = "", array $cols = [], $debug = false)
    {
        $sql;
        $joins = $this->generateSQLJoins($joins);
        $finalConditionString = $this->generateConditionString($table, $conditions);
        $groups = "";
        $orderString = "";
        if($orderBy != "")
        {
            $orderString = 'ORDER BY ' . $orderBy;
        }


        if(!empty($groupDetails))
        {
            $groups = ",". $this->generateGroupingString($groupDetails['groups']);
        }


        $select = "";

        if(!empty($cols))
        {
            foreach($cols as $col)
            {
                $select .= "$col, ";
            }
        }
        else
        {
            $select = "$table.*" ;
        }

        $select = rtrim($select, ', ');



        $sql = "SELECT " . $select . $groups . " FROM " . $table . $joins . $finalConditionString . ($groups != "" ? ' GROUP BY ' . $groupDetails['identifier'] . ' ' : ' ') . $orderString . ';';

        
        return $this->getAllBase($sql, $debug);
    }


     /**
     * getAllBase() expects a sql string as first argument and prepares, executes and returns just that.
     * This function will always return an array with all fields that got found with given conditions or NULL.
     * 
     * @param string $sql - The SQL Statement that should be executed
     * @param bool $debug - Wether or not the functiion should dump the current sql for debugging.
     * 
     * @return array|null - The result array of the query.
     */
    public function getAllBase($sql, $debug = false)
    {
        if($debug)
        {
            var_dump($sql);
            exit;
        }

        $stmt = $this->pdo->prepare($sql);

        if (false === $stmt) 
        {
            throw new \Exception\RuntimeException("PDO error: SQL Statement couldn't be prepared.");
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_OBJ);

        if(empty($results))
        {
            return null;
        }

        $output = [];
        $columnNames = array_keys(get_object_vars($results[0]));
        //We are assuming that every table always has a unique field "id" as identifier.
        foreach ($results as $result) 
        {
            $keyCol = "";
            foreach ($columnNames as $columnName) 
            {
                //The first entry will always be our row-id but to allow for configurability, we don't hardcode them.
                if ($keyCol === "" && strpos($columnName, 'Id') !== false) 
                {
                    $keyCol = $columnName;
                }

                //Current index is not set yet, so we save it as string at first.
                if(!isset($output[$result->{$keyCol}][$columnName]))
                {
                    $output[$result->{$keyCol}][$columnName] = strval($result->{$columnName});
                    continue;
                }
                
                if ( stristr(strval($output[$result->{$keyCol}][$columnName]), strval($result->{$columnName})) == false ) 
                { 
                    $output[$result->{$keyCol}][$columnName]  .=  "," . $result->{$columnName};
                }        
            }
        }

        return $output;
    }


    /**
     * Updates data in the database based on specific conditions
     * 
     * @param array $updates - An array with fields and the new values.
     * For further details, see $this->get(); as this method uses the same syntax for conditions.
     */
    public function update($table, array $updates, array|string $conditions = [], bool $debug = false) : bool
    {
        $sql = "UPDATE " . $table . " SET " . $this->generateUpdateSQLString($updates) . (($conditions === [] || $conditions === null) ? '' :  $this->generateConditionString($table, $conditions));

        if($debug)
        {
            var_dump($sql);
            exit;
        }

        $stmt = $this->pdo->prepare($sql);
        if ($stmt === null) 
        {
            throw new \Exception\RuntimeException('PDO error: ' . $e->getMessage());
        }
        
        return $stmt->execute();
    }

    /**
     * Inserts data in the database based on specific conditions
     * 
     * @param string $table - The table in which this entry should be added
     * @param array $inserts - An array with fields and values.
     * @return id of inserted row or false, if our statement ran into an issue.
     * Example:
     * $inserts = [
     * 
     *   'field' => 'value'
     * 
     * ]
     */
    public function insert($table, array $inserts, $debug = false) : int|bool
    {
        try 
        {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO " . $table . " " . $this->generateInsertString($inserts);

            if ($debug) 
            {
                var_dump($sql);
                exit;
            }

            $stmt = $this->pdo->prepare($sql);

            if (false === $stmt) 
            {
                throw new \Exception\RuntimeException('PDO error: ' . $this->pdo->errorInfo());
            }

            if ($stmt->execute()) 
            {
                $lastInsertId = (int) $this->pdo->lastInsertId();
                $this->pdo->commit();
                return $lastInsertId;
            }

            $this->pdo->rollBack();
            return false;
        } 
        catch (\Exception $e) 
        {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Deletes data in the database based on specific conditions
     * For further details about conditions, see get(); as this method uses syntax.
     */
    public function delete($table, array|string $conditions, $debug = false) : bool
    {
        $sql;
        $finalConditionString = $this->generateConditionString($table, $conditions);

        $sql = "DELETE FROM " . $table . $finalConditionString;
        
        if($debug)
        {
            var_dump($sql);
            exit;
        }

        $stmt = $this->pdo->prepare($sql);
        if (false === $stmt) 
        {
            throw new \Exception\RuntimeException('PDO error: ' . $e->getMessage());
        }
 
         return $stmt->execute();
    }


    #
    #
    #
    # Functions below this comment may be moved into a seperate utility class.
    #
    #
    #
    private function generateGroupingString($groups)
    {
        $groupedString = "";
        foreach($groups as $group)
        {
            if($groupedString === "")
            {
                $groupedString .= " GROUP_CONCAT(DISTINCT ".$group['for'].") AS " . $group['as'];
                continue;
            }
            $groupedString .= ", GROUP_CONCAT(DISTINCT ".$group['for'].") AS " . $group['as'];
        }
        return $groupedString;
    }

    private function generateInsertString(array $inserts) : string
    {

        $insertString = "";
        $valueString = "";
        foreach ($inserts as $key => $value) 
        {
            if($insertString === "")
            {
                $valueString .= "( '" . $value . "'";
                $insertString .= "( `" . $key . "`";
                continue;
            }
            $valueString .= ", '" . $value . "'";
            $insertString .= ", `" . $key . "`";
        }
        $valueString .= " )";
        $insertString .= " )";

        return $insertString . ' VALUES ' . $valueString;
    }
    
    private function generateUpdateSQLString(array $updates) : string
    {
        $updateString = "";

        foreach($updates as $field => $value)
        {
            if($value === "")
            {
                //empty fields will not get inserted
                continue;
            }
            if($value === null)
			{
				$value = "NULL";
			}
			else
			{
				$value = "'" . $value . "'";
			}
            if($updateString == "")
            {
                $updateString .= '`' . $field . "` = " .   $value ;
                continue;
            }

            $updateString .= ", `" . $field . "` = " . $value;
        }
        return $updateString;
    }

    /**
     * 
     * Appends all conditions, handles each of them as configured
     * 
     * @return string
     */
    public function generateConditionString($table, $conditions) : string
	{
		$bondConditions = "";

		if (!is_array($conditions)) {
			if ($conditions !== null && $conditions !== "") {
				return " WHERE $table." . $conditions;
			}
			return "";
		}

		if ($conditions === []) {
			return "";
		}

		foreach ($conditions as $data) {
			// Sonderfall: if-then-else-Bedingung
			if (isset($data['type']) && $data['type'] === 'conditionalFallback') {
				$logicOperator = $data['logicalOperator'] ?? '';

				$if = $data['if'];
				$ifField = $if['field'];
				$ifOperator = $if['operator'] ?? 'IS NOT';
				$ifValue = $if['queue'] ?? null;
				$ifTable = $if['tableOverride'] ?? $table;

				$ifCondition = "$ifTable.`$ifField` $ifOperator " . ($ifValue === null ? "NULL" : "'$ifValue'");

				$then = $data['then'];
				$thenTable = $then['tableOverride'] ?? $table;
				$thenWildcard = $this->buildWildcardValue($then['queue'] ?? '', $then['wildcard'] ?? 'none');
				$thenOperator = $then['operator'] ?? 'LIKE';
				$thenCondition = "$thenTable.`{$then['field']}` $thenOperator '$thenWildcard'";

				$else = $data['else'];
				$elseTable = $else['tableOverride'] ?? $table;
				$elseWildcard = $this->buildWildcardValue($else['queue'] ?? '', $else['wildcard'] ?? 'none');
				$elseOperator = $else['operator'] ?? 'LIKE';
				$elseCondition = "$elseTable.`{$else['field']}` $elseOperator '$elseWildcard'";

				$fullCondition = "(($ifCondition AND $thenCondition) OR (NOT($ifCondition) AND $elseCondition))";

				$bondConditions .= ($bondConditions === "" ? "" : " $logicOperator ") . $fullCondition;
				continue;
			}

			// Standardbedingung
			$field = $data['field'];
			$logicOperator = $data['logicalOperator'] ?? '';
			$operator = $data['operator'] ?? 'LIKE';
			$queue = $data['queue'] ?? '';
			$wildcard = (!isset($data['wildcard']) || $data['wildcard'] === "none") ? '' : $data['wildcard'];
			$tableOverride = (!isset($data['tableOverride']) || $data['tableOverride'] === "none") ? $table : $data['tableOverride'];

			if ($queue === null) {
				$queueString = "NULL";
			} else {
				$queue = $this->buildWildcardValue($queue, $wildcard);
				$queueString = "'$queue'";
			}

			$condition = "$tableOverride.`$field` $operator $queueString";

			$bondConditions .= ($bondConditions === "" ? "" : " $logicOperator ") . $condition;
		}

		return $bondConditions === "" ? "" : " WHERE " . $bondConditions;
	}
	
	private function buildWildcardValue($value, $wildcard)
	{
		if ($value === null) return '';
		switch ($wildcard) {
			case 'both':
				return '%' . $value . '%';
			case 'before':
				return '%' . $value;
			case 'after':
				return $value . '%';
			default:
				return $value;
		}
	}

    private function generateSQLJoins(array $joins = [])
    {
        $joinString = "";

        foreach ($joins as $join) 
        {
            if (isset($join['table']) && isset($join['on'])) 
            {
                $joinString .= " LEFT JOIN " . $join['table'] . " ON " . $join['on'];
            }
        }

        return $joinString;
    }

}