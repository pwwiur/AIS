<?php
	class database {
		private static $initialized = false;
		private static $pdo;
		protected static $database_type;
		protected static $charset;
		protected static $database_name;
		protected static $server;
		protected static $username;
		protected static $password;
		protected static $database_file;
		protected static $socket;
		protected static $port;
		protected static $prefix;
		protected static $logging;
		protected static $option = array();
		protected static $options = array();
		protected static $logs = array();
		protected static $debug_mode = false;

		function __construct(){
			self::init();
		}
		public static function init(
		    $options = [
        		'logging' => true,
        		'database_type' => DBTYPE,
        		'database_name' => DBNAME,
        		'server' => DBSERVER,
        		'username' => DBUSER,
        		'password' => DBUSERPASS,
        		'charset' => DBCHARSET,
        		"options" => [
        		    PDO::ATTR_PERSISTENT => true
        		]
    	    ]
    	) {
			if (!self::$initialized) {
                self::$initialized = true;

				$commands = array();
				$dsn = '';

				if (is_array($options)){
					foreach ($options as $option => $value)
					{
						self::${$option} = $value;
					}
				}
				else{
					return false;
				}
				if (isset(self::$port) && is_int(self::$port * 1)){
					$port = self::$port;
				}
				$type = strtolower(self::$database_type);
				$is_port = isset($port);
				if (isset($options[ 'prefix' ])){
					self::$prefix = $options[ 'prefix' ];
				}
				switch ($type){
					case 'mariadb':
						$type = 'mysql';
					case 'mysql':
						if (self::$socket){
							$dsn = $type . ':unix_socket=' . self::$socket . ';dbname=' . self::$database_name;
						}
						else{
							$dsn = $type . ':host=' . self::$server . ($is_port ? ';port=' . $port : '') . ';dbname=' . self::$database_name;
						}
						$commands[] = 'SET SQL_MODE=ANSI_QUOTES';
						break;
					case 'pgsql':
						$dsn = $type . ':host=' . self::$server . ($is_port ? ';port=' . $port : '') . ';dbname=' . self::$database_name;
						break;
					case 'sybase':
						$dsn = 'dblib:host=' . self::$server . ($is_port ? ':' . $port : '') . ';dbname=' . self::$database_name;
						break;
					case 'oracle':
						$dbname = self::$server ?
							'//' . self::$server . ($is_port ? ':' . $port : ':1521') . '/' . self::$database_name :
							self::$database_name;

						$dsn = 'oci:dbname=' . $dbname . (self::$charset ? ';charset=' . self::$charset : '');
						break;
					case 'mssql':
						$dsn = strstr(PHP_OS, 'WIN') ?
							'sqlsrv:server=' . self::$server . ($is_port ? ',' . $port : '') . ';database=' . self::$database_name :
							'dblib:host=' . self::$server . ($is_port ? ':' . $port : '') . ';dbname=' . self::$database_name;

						$commands[] = 'SET QUOTED_IDENTIFIER ON';
						break;
					case 'sqlite':
						$dsn = $type . ':' . self::$database_file;
						self::$username = NULL;
						self::$password = NULL;
						break;
				}
				if (in_array($type, array('mariadb', 'mysql', 'pgsql', 'sybase', 'mssql')) && self::$charset){
					$commands[] = "SET NAMES '" . self::$charset . "'";
				}

	            $tryCounter = 0;
	            do{
	                $tryCounter += 1;
	    			try {
	    			    $tryAgain = false;
	    				self::$pdo = new PDO(
	    					$dsn,
	    					self::$username,
	    					self::$password,
							self::$options
	    				);

	    			}
	    			catch (PDOException $e) {
	    				echo $e->getMessage();
						file_put_contents('db.txt', 22 . " " . $e->getMessage() .N, FILE_APPEND | LOCK_EX);
	    			    $tryAgain = $tryCounter < 16;
	    			}
	            } while($tryAgain);

				foreach ($commands as $value){
					self::$pdo->exec($value);
				}
			}
		}

		public static function reconnect(){

		}
		public static function query($query){
			if (self::$debug_mode){
				echo $query;
				self::$debug_mode = false;
				return false;
			}
			self::$logs[] = $query;
			return self::$pdo->query($query);
		}

		public static function exec($query, $log = true){
			if (self::$debug_mode){
				echo $query;
				self::$debug_mode = false;
				return false;
			}

			if($log) self::$logs[] = $query;
			return self::$pdo->exec($query);
		}

		public static function quote($string){
			return self::$pdo->quote($string);
		}

		protected static function table_quote($table){
			return '"' . self::$prefix . $table . '"';
		}

		protected static function column_quote($string){
			preg_match('/(\(JSON\)\s*|^#)?([a-zA-Z0-9_]*)\.([a-zA-Z0-9_]*)/', $string, $column_match);
			if (isset($column_match[ 2 ], $column_match[ 3 ])){
				return '"' . self::$prefix . $column_match[ 2 ] . '"."' . $column_match[ 3 ] . '"';
			}
			return '"' . $string . '"';
		}

		protected static function column_push(&$columns){
			if ($columns == '*'){
				return $columns;
			}
			if (is_string($columns)){
				$columns = array($columns);
			}
			$stack = array();
			foreach ($columns as $key => $value){
				if (is_array($value)){
					$stack[] = self::column_push($value);
				}
				else{
					preg_match('/([a-zA-Z0-9_\-\.]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $value, $match);
					if (isset($match[ 1 ], $match[ 2 ])){
						$stack[] = self::column_quote( $match[ 1 ] ) . ' AS ' . self::column_quote( $match[ 2 ] );

						$columns[ $key ] = $match[ 2 ];
					}
					else{
						$stack[] = self::column_quote( $value );
					}
				}
			}
			return implode(',', $stack);
		}

		protected static function array_quote($array){
			$temp = array();
			foreach ($array as $value){
				$temp[] = is_int($value) ? $value : self::$pdo->quote($value);
			}
			return implode(',', $temp);
		}

		protected static function inner_conjunct($data, $conjunctor, $outer_conjunctor){
			$haystack = array();
			foreach ($data as $value){
				$haystack[] = '(' . self::data_implode($value, $conjunctor) . ')';
			}
			return implode($outer_conjunctor . ' ', $haystack);
		}

		protected static function fn_quote($column, $string){
			return (strpos($column, '#') === 0 && preg_match('/^[A-Z0-9\_]*\([^)]*\)$/', $string)) ?
				$string :
				self::quote($string);
		}

		protected static function data_implode($data, $conjunctor, $outer_conjunctor = NULL){
			$wheres = array();
			foreach ($data as $key => $value){
				$type = gettype($value);
				if (preg_match("/^(AND|OR)(\s+#.*)?$/i", $key, $relation_match) && $type == 'array'){
					$wheres[] = 0 !== count(array_diff_key($value, array_keys(array_keys($value)))) ?
						'(' . self::data_implode($value, ' ' . $relation_match[ 1 ]) . ')' :
						'(' . self::inner_conjunct($value, ' ' . $relation_match[ 1 ], $conjunctor) . ')';
				}
				else{
					preg_match('/(#?)([\w\.\-]+)(\[(\>|\>\=|\<|\<\=|\!|\<\>|\>\<|\!?~)\])?/i', $key, $match);
					$column = self::column_quote($match[ 2 ]);
					if (isset($match[ 4 ])){
						$operator = $match[ 4 ];
						if ($operator == '!'){
							switch ($type){
								case 'NULL':
									$wheres[] = $column . ' IS NOT NULL';
									break;
								case 'array':
									$wheres[] = $column . ' NOT IN (' . self::array_quote($value) . ')';
									break;
								case 'integer':
								case 'double':
									$wheres[] = $column . ' != ' . $value;
									break;
								case 'boolean':
									$wheres[] = $column . ' != ' . ($value ? '1' : '0');
									break;
								case 'string':
									$wheres[] = $column . ' != ' . self::fn_quote($key, $value);
									break;
							}
						}

						if ($operator == '<>' || $operator == '><'){
							if ($type == 'array'){
								if ($operator == '><'){
									$column .= ' NOT';
								}
								if (is_numeric($value[ 0 ]) && is_numeric($value[ 1 ])){
									$wheres[] = '(' . $column . ' BETWEEN ' . $value[ 0 ] . ' AND ' . $value[ 1 ] . ')';
								}
								else{
									$wheres[] = '(' . $column . ' BETWEEN ' . self::quote($value[ 0 ]) . ' AND ' . self::quote($value[ 1 ]) . ')';
								}
							}
						}
						if ($operator == '~' || $operator == '!~'){
							if ($type != 'array'){
								$value = array($value);
							}
							$like_clauses = array();
							foreach ($value as $item){
								$item = strval($item);
								if (preg_match('/^(?!(%|\[|_])).+(?<!(%|\]|_))$/', $item)){
									$item = '%' . $item . '%';
								}
	                            elseif(substr($item, -1) == "_"){
	                                $item = $item . '%';
	                            }

								$like_clauses[] = $column . ($operator === '!~' ? ' NOT' : '') . ' LIKE ' . self::fn_quote($key, $item);
							}
							$wheres[] = implode(' OR ', $like_clauses);
						}
						if (in_array($operator, array('>', '>=', '<', '<='))){
							$condition = $column . ' ' . $operator . ' ';
							if (is_numeric($value)){
								$condition .= $value;
							}
							elseif (strpos($key, '#') === 0){
								$condition .= self::fn_quote($key, $value);
							}
							else{
								$condition .= self::quote($value);
							}

							$wheres[] = $condition;
						}
					}
					else{
						switch ($type){
							case 'NULL':
								$wheres[] = $column . ' IS NULL';
								break;

							case 'array':
								$wheres[] = $column . ' IN (' . self::array_quote($value) . ')';
								break;

							case 'integer':
							case 'double':
								$wheres[] = $column . ' = ' . $value;
								break;

							case 'boolean':
								$wheres[] = $column . ' = ' . ($value ? '1' : '0');
								break;

							case 'string':
								$wheres[] = $column . ' = ' . self::fn_quote($key, $value);
								break;
						}
					}
				}
			}
			return implode($conjunctor . ' ', $wheres);
		}

		public static function where_clause($where){
			$where_clause = '';
			if (is_array($where)){
				$where_keys = array_keys($where);
				$where_AND = preg_grep("/^AND\s*#?$/i", $where_keys);
				$where_OR = preg_grep("/^OR\s*#?$/i", $where_keys);
				$single_condition = array_diff_key($where, array_flip(
					array('AND', 'OR', 'GROUP', 'ORDER', 'HAVING', 'LIMIT', 'LIKE', 'MATCH')
				));
				if ($single_condition != array()){
					$condition = self::data_implode($single_condition, '');
					if ($condition != ''){
						$where_clause = ' WHERE ' . $condition;
					}
				}
				if (!empty($where_AND)){
					$value = array_values($where_AND);
					$where_clause = ' WHERE ' . self::data_implode($where[ $value[ 0 ] ], ' AND');
				}
				if (!empty($where_OR)){
					$value = array_values($where_OR);
					$where_clause = ' WHERE ' . self::data_implode($where[ $value[ 0 ] ], ' OR');
				}
				if (isset($where[ 'MATCH' ])){
					$MATCH = $where[ 'MATCH' ];
					if (is_array($MATCH) && isset($MATCH[ 'columns' ], $MATCH[ 'keyword' ])){
						$where_clause .= ($where_clause != '' ? ' AND ' : ' WHERE ') . ' MATCH ("' . str_replace('.', '"."', implode($MATCH[ 'columns' ], '", "')) . '") AGAINST (' . self::quote($MATCH[ 'keyword' ]) . ')';
					}
				}
				if (isset($where[ 'GROUP' ])){
					$where_clause .= ' GROUP BY ' . self::column_quote($where[ 'GROUP' ]);
					if (isset($where[ 'HAVING' ])){
						$where_clause .= ' HAVING ' . self::data_implode($where[ 'HAVING' ], ' AND');
					}
				}
				if (isset($where[ 'ORDER' ])){
					$ORDER = $where[ 'ORDER' ];
					if (is_array($ORDER)){
						$stack = array();
						foreach ($ORDER as $column => $value){
							if (is_array($value)){
								$stack[] = 'FIELD(' . self::column_quote($column) . ', ' . self::array_quote($value) . ')';
							}
							else if ($value === 'ASC' || $value === 'DESC'){
								$stack[] = self::column_quote($column) . ' ' . $value;
							}
							else if (is_int($column)){
								$stack[] = self::column_quote($value);
							}
							else if($column == "func") {
								$stack[] = $value;
							}
						}
						$where_clause .= ' ORDER BY ' . implode(',', $stack);
					}
					else{
						$where_clause .= ' ORDER BY ' . self::column_quote($ORDER);
					}
				}
				if (isset($where[ 'LIMIT' ])){
					$LIMIT = $where[ 'LIMIT' ];
					if (is_numeric($LIMIT)){
						$where_clause .= ' LIMIT ' . $LIMIT;
					}

					if (is_array($LIMIT) && is_numeric($LIMIT[ 0 ]) && is_numeric($LIMIT[ 1 ])){
						if (self::$database_type === 'pgsql'){
							$where_clause .= ' OFFSET ' . $LIMIT[ 0 ] . ' LIMIT ' . $LIMIT[ 1 ];
						}
						else{
							$where_clause .= ' LIMIT ' . $LIMIT[ 0 ] . ',' . $LIMIT[ 1 ];
						}
					}
				}
			}
			else{
				if ($where != NULL)	{
					$where_clause .= ' ' . $where;
				}
			}
			return $where_clause;
		}

		public static function vlog($title, $text = NULL) {
			if (!isset($text)) {
				$text = $title;
				$title = strand("6");
			}
			if(!is_string($title)) {
				$title = json_encode($title);
			}
			if(!is_string($text)) {
				$text = json_encode($text);
			}
			return self::insert("debug", ["title" => $title, "body" => $text, "time" => time::now()]);
		}

		protected static function select_context($table, $join, &$columns = NULL, $where = NULL, $column_fn = NULL){
			preg_match('/([a-zA-Z0-9_\-]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $table, $table_match);
			if (isset($table_match[ 1 ], $table_match[ 2 ])){
				$table = self::table_quote($table_match[ 1 ]);
				$table_query = self::table_quote($table_match[ 1 ]) . ' AS ' . self::table_quote($table_match[ 2 ]);
			}
			else{
				$table = self::table_quote($table);
				$table_query = $table;
			}
			$join_key = is_array($join) ? array_keys($join) : NULL;
			if (isset($join_key[ 0 ]) && strpos($join_key[ 0 ], '[') === 0){
				$table_join = array();
				$join_array = array(
					'>' => 'LEFT',
					'<' => 'RIGHT',
					'<>' => 'FULL',
					'><' => 'INNER'
				);
				foreach($join as $sub_table => $relation){
					preg_match('/(\[(\<|\>|\>\<|\<\>)\])?([a-zA-Z0-9_\-]*)\s?(\(([a-zA-Z0-9_\-]*)\))?/', $sub_table, $match);
					if ($match[ 2 ] != '' && $match[ 3 ] != ''){
						if (is_string($relation)){
							$relation = 'USING ("' . $relation . '")';
						}
						if (is_array($relation)){
							if (isset($relation[ 0 ])){
								$relation = 'USING ("' . implode('", "', $relation) . '")';
							}
							else{
								$joins = array();

								foreach ($relation as $key => $value){
									$joins[] = (
										strpos($key, '.') > 0 ?
											self::column_quote($key) :
											$table . '."' . $key . '"'
									) .
									' = ' .
									self::table_quote(isset($match[ 5 ]) ? $match[ 5 ] : $match[ 3 ]) . '."' . $value . '"';
								}

								$relation = 'ON ' . implode(' AND ', $joins);
							}
						}
						$table_name = self::table_quote($match[ 3 ]) . ' ';
						if (isset($match[ 5 ])){
							$table_name .= 'AS ' . self::table_quote($match[ 5 ]) . ' ';
						}
						$table_join[] = $join_array[ $match[ 2 ] ] . ' JOIN ' . $table_name . $relation;
					}
				}
				$table_query .= ' ' . implode(' ', $table_join);
			}
			else{
				if (is_NULL($columns)){
					if (is_NULL($where)){
						if (is_array($join) && isset($column_fn)){
							$where = $join;
							$columns = NULL;
						}
						else{
							$where = NULL;
							$columns = $join;
						}
					}
					else{
						$where = $join;
						$columns = NULL;
					}
				}
				else{
					$where = $columns;
					$columns = $join;
				}
			}
			if (isset($column_fn)){
				if ($column_fn == 1){
					$column = '1';
					if (is_NULL($where)){
						$where = $columns;
					}
				}
				else{
					if (empty($columns)){
						$columns = '*';
						$where = $join;
					}
					$column = $column_fn . '(' . self::column_push($columns) . ')';
				}
			}
			else{
				$column = self::column_push($columns);
			}
			return 'SELECT ' . $column . ' FROM ' . $table_query . self::where_clause($where);
		}

		protected static function data_map($index, $key, $value, $data, &$stack){
			if (is_array($value)){
				$sub_stack = array();
				foreach ($value as $sub_key => $sub_value){
					if (is_array($sub_value)){
						$current_stack = $stack[ $index ][ $key ];

						self::data_map(false, $sub_key, $sub_value, $data, $current_stack);

						$stack[ $index ][ $key ][ $sub_key ] = $current_stack[ 0 ][ $sub_key ];
					}
					else{
						self::data_map(false, preg_replace('/^[\w]*\./i', "", $sub_value), $sub_key, $data, $sub_stack);

						$stack[ $index ][ $key ] = $sub_stack;
					}
				}
			}
			else{
				if ($index !== false){
					$stack[ $index ][ $value ] = $data[ $value ];
				}
				else{
					if (preg_match('/[a-zA-Z0-9_\-\.]*\s*\(([a-zA-Z0-9_\-]*)\)/i', $key, $key_match)){
						$key = $key_match[ 1 ];
					}
					$stack[ $key ] = $data[ $key ];
				}
			}
		}

		public static function cselect($query, $shits = []){
			return self::custom_select($query, $shits);
		}
		public static function custom_select($query, $shits = []){
		    if(!empty($shits)){
				if(!is_array($shits)){
					$shits = [$shits];
				}
		        $query = self::$pdo->prepare($query);

                $query->execute($shits);
                self::$logs[] = $query;
		    }
		    else{
    		    $query = self::query($query);
    		    $stack = array();
    			$index = 0;
    			if (!$query)
    				return false;

		    }
		    return $query->fetchAll(PDO::FETCH_ASSOC);
		}

		public static function select($table, $join = "*", $columns = NULL, $where = NULL){
			$column = $where == NULL ? $join : $columns;
			$is_single_column = (is_string($column) && $column !== '*');
			$query = self::query(self::select_context($table, $join, $columns, $where));
			$stack = array();
			$index = 0;
			if (!$query){
				return false;
			}

			if ($columns === '*'){
				return $query->fetchAll(PDO::FETCH_ASSOC);
			}

			if ($is_single_column){
				return $query->fetchAll(PDO::FETCH_COLUMN);
			}

			while ($row = $query->fetch(PDO::FETCH_ASSOC)){
				foreach ($columns as $key => $value){
					if (is_array($value)){
						self::data_map($index, $key, $value, $row, $stack);
					}
					else{
						self::data_map($index, $key, preg_replace('/^[\w]*\./i', "", $value), $row, $stack);
					}
				}

				$index++;
			}
			return $stack;
		}

		public static function insert($table, $datas){
			$lastId = array();
			if (!isset($datas[ 0 ])){
				$datas = array($datas);
			}
			foreach ($datas as $data){
				$values = array();
				$columns = array();
				foreach ($data as $key => $value){
					$columns[] = self::column_quote(preg_replace("/^(\(JSON\)\s*|#)/i", "", $key));
					switch (gettype($value)){
						case 'NULL':
							$values[] = 'NULL';
							break;
						case 'array':
							preg_match("/\(JSON\)\s*([\w]+)/i", $key, $column_match);

							$values[] = isset($column_match[ 0 ]) ?
								self::quote(json_encode($value)) :
								self::quote(serialize($value));
							break;
						case 'boolean':
							$values[] = ($value ? '1' : '0');
							break;
						case 'integer':
						case 'double':
						case 'string':
							$values[] = self::fn_quote($key, $value);
							break;
					}
				}
				self::exec('INSERT INTO ' . self::table_quote($table) . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')');
				$lastId[] = self::$pdo->lastInsertId();
			}
			return count($lastId) > 1 ? $lastId : $lastId[ 0 ];
		}

		public static function update($table, $data, $where = NULL){
			$fields = array();
			if(self::count($table, $where) > 0){
				foreach ($data as $key => $value){
					preg_match('/([\w]+)(\[(\+|\-|\*|\/)\])?/i', $key, $match);
					if (isset($match[ 3 ])){
						if (is_numeric($value)){
							$fields[] = self::column_quote($match[ 1 ]) . ' = ' . self::column_quote($match[ 1 ]) . ' ' . $match[ 3 ] . ' ' . $value;
						}
					}
					else{
						$column = self::column_quote(preg_replace("/^(\(JSON\)\s*|#)/i", "", $key));

						switch (gettype($value)){
							case 'NULL':
								$fields[] = $column . ' = NULL';
								break;

							case 'array':
								preg_match("/\(JSON\)\s*([\w]+)/i", $key, $column_match);

								$fields[] = $column . ' = ' . self::quote(
										isset($column_match[ 0 ]) ? json_encode($value) : serialize($value)
									);
								break;

							case 'boolean':
								$fields[] = $column . ' = ' . ($value ? '1' : '0');
								break;

							case 'integer':
							case 'double':
							case 'string':
								$fields[] = $column . ' = ' . self::fn_quote($key, $value);
								break;
						}
					}
				}

				try{
					$result = self::exec('UPDATE ' . self::table_quote($table) . ' SET ' . implode(', ', $fields) . self::where_clause($where));
				}
				catch(Exception $e) {
					$result = false;
				}
			}
			else{
				$result = false;
			}
			return $result;
		}

		public static function delete($table, $where){
			return self::exec('DELETE FROM ' . self::table_quote($table) . self::where_clause($where));
		}

		public static function replace($table, $columns, $search = NULL, $replace = NULL, $where = NULL){
			if (is_array($columns)){
				$replace_query = array();
				foreach ($columns as $column => $replacements){
					foreach ($replacements as $replace_search => $replace_replacement){
						$replace_query[] = $column . ' = REPLACE(' . self::column_quote($column) . ', ' . self::quote($replace_search) . ', ' . self::quote($replace_replacement) . ')';
					}
				}

				$replace_query = implode(', ', $replace_query);
				$where = $search;
			}
			else{
				if (is_array($search)){
					$replace_query = array();
					foreach ($search as $replace_search => $replace_replacement){
						$replace_query[] = $columns . ' = REPLACE(' . self::column_quote($columns) . ', ' . self::quote($replace_search) . ', ' . self::quote($replace_replacement) . ')';
					}
					$replace_query = implode(', ', $replace_query);
					$where = $replace;
				}
				else{
					$replace_query = $columns . ' = REPLACE(' . self::column_quote($columns) . ', ' . self::quote($search) . ', ' . self::quote($replace) . ')';
				}
			}
			return self::exec('UPDATE ' . self::table_quote($table) . ' SET ' . $replace_query . self::where_clause($where));
		}

		public static function has($table, $join = NULL, $where = NULL){
			if($join == NULL){
			    if(empty($table))
			        return false;
			    $query = self::query("SHOW TABLES LIKE '" . $table . "'");
			    return $query ? $query->rowCount() : false;
			}
			else{
    			$column = NULL;
    			$query = self::query('SELECT EXISTS(' . self::select_context($table, $join, $column, $where, 1) . ')');

    			if ($query){
    				return $query->fetchColumn() === 1;
    			}
    			else{
    				return false;
    			}
			}
		}

		public static function get($table, $id, $where = []){
			$where["AND"]["id"] = $id;
			return self::select($table, "*", $where)[0];
		}

		public static function all($table, $where = NULL){
			return self::select($table, "*", $where);
		}

		public static function is($table, $columns, $where = NULL){
			return self::select($table, $columns, $where)[0] === "1";
		}

		public static function count($table, $join = NULL, $column = NULL, $where = NULL){
			$query = self::query(self::select_context($table, $join, $column, $where, 'COUNT'));
			return $query ? 0 + $query->fetchColumn() : false;
		}

		public static function max($table, $join, $column = NULL, $where = NULL){
			$query = self::query(self::select_context($table, $join, $column, $where, 'MAX'));
			if ($query){
				$max = $query->fetchColumn();
				return is_numeric($max) ? $max + 0 : $max;
			}
			else{
				return false;
			}
		}

		public static function min($table, $join, $column = NULL, $where = NULL){
			$query = self::query(self::select_context($table, $join, $column, $where, 'MIN'));
			if ($query){
				$min = $query->fetchColumn();

				return is_numeric($min) ? $min + 0 : $min;
			}
			else{
				return false;
			}
		}

		public static function avg($table, $join, $column = NULL, $where = NULL){
			$query = self::query(self::select_context($table, $join, $column, $where, 'AVG'));
			return $query ? 0 + $query->fetchColumn() : false;
		}

		public static function sum($table, $join, $column = NULL, $where = NULL){
			$query = self::query(self::select_context($table, $join, $column, $where, 'SUM'));
			return $query ? 0 + $query->fetchColumn() : false;
		}

		public static function rand($table, $join = null, $columns = null, $where = null) {
			$type = self::$database_type;
			$order = 'RANDOM()';
			if ($type === 'mysql') {
				$order = 'RAND()';
			}
			elseif ($type === 'mssql') {
				$order = 'NEWID()';
			}

			$order = ["func" => $order];
			if ($where === null) {
				if ($columns === null) {
					$columns = [
						'ORDER'  => $order
					];
				}
				else {
					$column = $join;
					unset($columns[ 'ORDER' ]);
					$columns[ 'ORDER' ] = $order;
				}
			}
			else {
				unset($where[ 'ORDER' ]);
				$where[ 'ORDER' ] = $order;
			}
			return self::select($table, $join, $columns, $where);
		}

		public static function action($actions){
			if (is_callable($actions)){
				self::$pdo->beginTransaction();
				$result = $actions(self);
				if ($result === false){
					self::$pdo->rollBack();
				}
				else{
					self::$pdo->commit();
				}
			}
			else{
				return false;
			}
		}

		public static function debug(){
			self::$debug_mode = true;
			return self;
		}

		public static function error(){
			return self::$pdo->errorInfo();
		}

		public static function last_query(){
			return end(self::$logs);
		}

		public static function log(){
			return self::$logs;
		}

		public static function info(){
			$output = array(
				'server' => 'SERVER_INFO',
				'driver' => 'DRIVER_NAME',
				'client' => 'CLIENT_VERSION',
				'version' => 'SERVER_VERSION',
				'connection' => 'CONNECTION_STATUS'
			);

			foreach ($output as $key => $value){
				$output[ $key ] = self::$pdo->getAttribute(constant('PDO::ATTR_' . $value));
			}
			return $output;
		}
	}
	database::init();