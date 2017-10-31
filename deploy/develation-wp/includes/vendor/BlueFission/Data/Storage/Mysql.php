<?php
use \BlueFission;
@include_once('Loader.php');
$loader = BlueFission\Loader::instance();
$loader->load('com.bluefission.develation.functions.common');
$loader->load('com.bluefission.develation.data.storage.DevStorage');
$loader->load('com.bluefission.develation.interfaces.data.IDevData');
$loader->load('com.bluefission.develation.connections.DevMysqlLink');
$loader->load('com.bluefission.develation.DevDateTime');

class Mysql extends Storage implements IData
{
	protected $_config = array(
		'location'=>'',
		'name'=>'',
		'fields'=>'',
		'ignore_null'=>false,
		'temporary'=>false,
		'set_defaults'=>false,
	);
	private $_last_row_affected;
	private $_result;
		
	//declare query parts
	private $_tables = array();
	private $_fields = array();
	private $_relations = array();
	private $_conditions = array();
	private $_order = array();
	private $_aggregate = array();
	private $_distinctions = array();
	private $_query, $_row_start, $_row_limit;	
	
	public function __construct( $config = null )
	{
		parent::__construct( $config );
	}
	
	public function activate()
	{
		$this->_source = new MysqlLink( );
		$this->_source->database( $this->config('location') );
		// load object fields and related data
		$this->fields();
			
		if ( !$this->_source ) 
			$this->status( self::STATUS_FAILED_INIT );
	}

	public function query()
	{
		return $this->_query;
	}
	
	public function write()
	{
		$db = $this->_source;
		$status = self::STATUS_FAILED;
		$keys = array();
		$success = true;

		if (!$this->tables() || !$this->fields())
			$this->create();

		$tables = $this->tables();
		$table = $tables[0];
		$table = isset( $tables[0] ) ? $tables[0] : $this->config(self::NAME_FIELD);
		
		foreach ($this->fields() as $table=>$column)
		{
			$name = $column['Field'];
			if ( $this->validate($name, $table) )
			{
				if  ( $column['Key'] == 'PRI' || $column['Key'] == 'UNI' )
				{
					if (!isset($keys[$table])) $keys[$table] = $name;
				}
			}
			else
			{
				$success = false;
			}
		}
		
		$tables = $this->tables();
		while ( ( $table = each($tables) ) && $success )
		{
			$table = $table['value'];
			$key = isset($keys[$table]) ? $keys[$table] : null;
			$db->config('key', $key);
			$db->config('table', $table);
			$db->config('ignore_null', $this->config('ignore_null'));
			$success = $db->query($this->_data);

			//$status = $success ? self::STATUS_FAILED : self::STATUS_SUCCESS;

			$status = $success ? self::STATUS_SUCCESS : $db->status();
			$this->status( $status );
			if (!$success)
				return false;
		}
	}
	
	public function read()
	{
		$db = $this->_source;

		$tables = $this->tables();
		$table = $tables[0];
		$fields = array();
		$data = $this->data();
		$active_fields = DevArray::toArray( $this->config('fields') );
		$field_info = $this->fields();
		
		$relations = $this->_relations;
		$using = array();
		$join = array();
		$on = array();
		
		$distinct = array();
		$where = array('1');
		$sort = array();
		
		foreach ($data as $a=>$b) 
		{
			if ($this->whereCase($table, $a, $b))
				$where[] = $this->whereCase($table, $a, $b);
			if ($this->distinctCase($table, $a))
				$distinct[] = $this->distinctCase($table, $a);
		}
		
		// Use Ordered Sort Cases
		foreach ( $this->_order as $a=>$b )
		{
			if ( $this->exists($a) )
			{
				$sort[] = $this->orderCase($table, $a);
			}
		}
		
		$left_join = '';
		$count = 1;
		
		foreach ($tables as $a) 
		{
			if ( $a != $table )
			{
				$join = $this->table($a);
				if (is_array($join)) 
				{
					$field = $this->arrayKeyIntersect($this->table($table), $join);
					foreach ($field as $b=>$c) 
					{ 
						if (in_array($b, $active_fields) || DevValue::isEmpty($active_fields)) $on[] = $table . ".$b  = $a.$b";
					}
	
					if (count($relations) > 0) 
					{
						$fields = $this->arrayKeyIntersect($relations, $join);
						foreach ($fields as $b=>$c) {
							$on[] = $table . "." . $relations[$b] . "  = $a.$b";
						}
					}
	
					for ($i = $count; $i < count($tables); $i++) 
					{
						$b = $tables[$i];
						if ($a != $b) {
							$join_2 = $this->table($b);
							if (is_array($join_2)) {
								$fields = $this->arrayKeyIntersect($this->table($a), $join_2);
								foreach ($fields as $c=>$d) {
									$on[] = $a . ".$c  = $b.$c";
								}
							}
							
							$join_2 = $this->arrayKeyIntersect($this->table($b), $relations);
							if (is_array($join_2)) {	
								$fields = $this->arrayKeyIntersect($this->tables($a), $join_2);
								foreach ($fields as $c=>$d) {
									$on[] = $a . ".$c  = $b.$c";
								}
							}
						}
					}
					$count++;
					
					$members = $this->arrayKeyIntersect($data, $join);
					
					foreach ($members as $b=>$c) 
					{
						if ($this->whereCase($a, $b, $c))
							$where[] = $this->whereCase($a, $b, $c);
						if ($this->orderCase($a, $b))
							$sort[] = $this->orderCase($a, $b);
					}
				}
			}
		}

		$left_join = '';
		if ( count ( $this->tables() ) > 1 )
			$left_join .= "INNER JOIN (" . implode(', ', array_slice($tables, 1)) . ") ON (" . implode(' AND ', $on) . ")";
		
		$select = array();
		foreach($active_fields as $a) 
		{
			if ($this->exists($a))
				$select[] = ($this->aggregateCase($table, $a)) ? $this->aggregateCase($table, $a) : $table.'.'.$a;
		}
		if (count($select) <= 0) 
		{
			$select[] = '*';
			foreach($this->_aggregate as $a=>$b) 
			{
				if ($this->exists($a))
					$select[] = ($this->aggregateCase($table, $a)) ? $this->aggregateCase($table, $a) : $table.'.'.$a;
			}
		}

		// Build query		
		$query = "SELECT " . implode(', ', $select) . " FROM `$table` $left_join WHERE " . implode(' AND ', $where); 

		if (count($distinct) > 0) $query .= " GROUP BY " . implode(', ', $distinct); 
		if (count($sort) > 0) $query .= " ORDER BY " . implode(', ', $sort); 

		$start = $this->start();
		$end = $this->end();

		$query .= ((DevValue::isNotNull($start)) ? " LIMIT " . $this->start() . ((DevValue::isNotNull($end)) ? ", " . $this->end() : '') : '');

		$db->query($query);
		$this->_query =$db->stats()['query'];

		$result = $db->result();
		$this->status( $result ? self::STATUS_SUCCESS : self::STATUS_FAILED );


		$this->_result = $result;

		if ($this->_result)	
		{
			$data = $this->_result->fetch_assoc();
			if ( $data )
			{
				$this->assign( $data );
				$this->_result->data_seek(0);
			}
		}
	}
	
	public function delete()
	{
		$tables = $this->tables();
		$table = $tables[0];
		$fields = array();
		$data = $this->data();
		$active_fields = DevArray::toArray( $this->config('fields') );
		$field_info = $this->fields();
		
		$relations = $this->_relations;
		$using = array();
		$join = array();
		$on = array();
		
		$distinct = array();
		$where = array('1');
		$sort = array();
		
		foreach ($data as $a=>$b) 
		{
			if ($this->whereCase($table, $a, $b))
				$where[] = $this->whereCase($table, $a, $b);
			if ($this->distinctCase($table, $a))
				$distinct[] = $this->distinctCase($table, $a);
		}
		
		// Use Ordered Sort Cases
		foreach ( $this->_order as $a=>$b )
		{
			if ( $this->exists($a) )
			{
				$sort = $this->orderCase($table, $a);
				$sort[] = $sort;
			}
		}	
		
		$left_join = '';
		$count = 1;
		
		foreach ($tables as $a) 
		{
			if ( $a != $table )
			{
				$join = $this->table($a);
				if (is_array($join)) 
				{
					$field = $this->arrayKeyIntersect($this->table($table), $join);
					foreach ($field as $b=>$c) 
					{ 
						if (in_array($b, $active_fields) || DevValue::isEmpty($active_fields)) $on[] = $table . ".$b  = $a.$b";
					}
	
					if (count($relations) > 0) 
					{
						$fields = $this->arrayKeyIntersect($relations, $join);
						foreach ($fields as $b=>$c) {
							$on[] = $table . "." . $relations[$b] . "  = $a.$b";
						}
					}
	
					for ($i = $count; $i < count($tables); $i++) 
					{
						$b = $tables[$i];
						if ($a != $b) {
							$join_2 = $this->table($b);
							if (is_array($join_2)) {
								$fields = $this->arrayKeyIntersect($this->table($a), $join_2);
								foreach ($fields as $c=>$d) {
									$on[] = $a . ".$c  = $b.$c";
								}
							}
							
							$join_2 = $this->arrayKeyIntersect($this->table($b), $relations);
							if (is_array($join_2)) {	
								$fields = $this->arrayKeyIntersect($this->tables($a), $join_2);
								foreach ($fields as $c=>$d) {
									$on[] = $a . ".$c  = $b.$c";
								}
							}
						}
					}
					$count++;
					
					$members = $this->arrayKeyIntersect(array_keys($data), $join);
		
					foreach ($members as $b=>$c) 
					{
						$where[] = $this->whereCase($a, $b, $c);
						$sort[] = $this->orderCase($a, $b);
					}			
				}
			}
		}

		$left_join = '';
		if ( count ( $this->tables() ) > 1 )
		$left_join = "INNER JOIN (" . implode(', ', array_slice($tables, 1)) . ") ON (" . implode(' AND ', $on) . ")";
		
		$select = array();
		foreach($active_fields as $a) if ($this->exists($a)) $select[] = $field_info[$a]['Table'].'.'.$a;
		if (count($select) <= 0) $select_r[] = '*';

		// Build query		
		//$query = "SELECT " . implode(', ', $select) . " FROM `$table` $left_join WHERE " . implode(' AND ', $where); 
		$query = "DELETE FROM `$table` $left_join WHERE " . implode(' AND ', $where);

		//if (count($distinct) > 0) $query .= " GROUP BY " . implode(', ', $distinct); 
		//if (count($sort) > 0) $query .= " ORDER BY " . implode(', ', $sort); 

		//$start = $this->start();
		//$end = $this->end();

		//$query .= ((DevValue::isNotNull($start)) ? " LIMIT " . $this->start() . ((DevValue::isNotNull($end)) ? ", " . $this->end() : '') : '');

		$db->query($query);
		$this->_query =$db->stats()['query'];
		$result = $db->result();
		$this->status( $result ? self::STATUS_SUCCESS : self::STATUS_FAILED );
		
		//$this->_result = $result;
	}
	
	private function create()
	{
		$db = $this->_source;
		//$tables = DevArray::toArray( $this->config(self::NAME_FIELD) ? $this->config(self::NAME_FIELD) : get_class($this) );
		$tables = DevArray::toArray( $this->config(self::NAME_FIELD) );
		$this->config(self::NAME_FIELD, $tables);

		if ( MysqlLink::tableExists( current( $this->config(self::NAME_FIELD) ) ) )
			return null;
		
		$types = array();
		$key = '';
		foreach ($this->_data as $a=>$b)
		{
			$type = '';
			if ($b)
			{
				if ( is_scalar($b))
				{
					if (is_numeric($b))
					{
						$type = is_float($b) ? "FLOAT" : "INT";
					}
					elseif (is_string($b))
					{
						if ( DevDateTime::string_is_date( $b ) )
							$type = "DATETIME";
						else
						{
							$length = DevValue::isNotNull($b) ? (int)(strlen($b)*1.3) : 90; 
							$type = "VARCHAR(".$length.")";
						}
					}
				}
				else
				{
					if (DevArray::isAssoc($b) || is_object($b))
					{
						$type = "TEXT";
					}
					else
					{
						if ($this->config('set_defaults'))
						{
							$type = "SET";
							$type .= "(".implode(',', $a).")";
						}
						else 
						{
							$type = "TEXT";	
						}
					}
				}
			}
			else
			{
				if ( $a == 'date' )
				{
					$type = "DATE";
				}
				elseif ( strtolower(substr( $a, -2)) == 'id' )
				{
					$type = "INT";
				}
				else
				{
					$type = "VARCHAR(90)";
				}  
			}
			$types[$a] = $type;
			
			if ($this->config('set_defaults') && is_scalar($b))
			{
				$type .= " DEFAULT ".MysqlLink::sanitize($b);
			}
			
			if ( strtolower(substr( $a, -2)) == 'id' && $type == "INT" && $key == '')
			{
				$key = $b;
				$type .= " NOT NULL AUTO_INCREMENT, PRIMARY KEY($key)";
				$this->config('key', $key);
			}
		}
		if ($key == '')
		{
			$key = $this->config('key');
			$type = $key . " INT NOT NULL AUTO_INCREMENT, PRIMARY KEY($key)";
		}
		
		$temp = $this->config('temporary') === true ? "TEMPORARY" : ""; 
		
		$query = "CREATE $temp TABLE IF NOT EXISTS ".$tables[0]."(";
		foreach ($types as $a=>$b)
		{
			$query .= " `$a` $b,";
		}
		$query = rtrim($query, ",");
		$query .= ")";
		$db->query($query);
		$this->_query =$db->stats()['query'];
		
		$result = $db->result();
		
		$this->_config[self::NAME_FIELD] = $this->tables();

		$status = ( $result ? self::STATUS_SUCCESS : self::STATUS_FAILED );
		$this->status($status);
	}
	
	// TODO : move to extended bulk mysql class
	public function result()
	{
		return $this->_result;
	}
	
	public function contents()
	{
		return $this->result()->fetch_assoc();
	}

	private function fields()
	{
		$db = $this->_source;
		//if (!$this->_fields || count( $this->config(self::NAME_FIELD) ) > 0 )
		if ( !$this->_fields )
		{
			$data = array();
			//$tables = DevArray::toArray( $this->config(self::NAME_FIELD) ? $this->config(self::NAME_FIELD) : get_class($this) );
			$tables = $this->config(self::NAME_FIELD) ? $this->config(self::NAME_FIELD) : ( $this->tables() ? $this->tables() : get_class($this) );
			$tables = DevArray::toArray( $tables );
			
			//if ( MysqlLink::tableExists( current( $tables ) ) )
				//return array();
			
			$this->perform( BlueFission\State::DRAFT );
			$active_fields = DevArray::toArray( $this->config('fields') );
			foreach ($tables as $table)
			{
				$query = "SHOW COLUMNS FROM `$table`";
				$db->query($query);
				$this->_query = $db->stats()['query'];
				$result = $db->result();
				if ($result)
				{
					while ($column = $result->fetch_assoc()) 
					{
						$fields[$column['Field']] = $column;
						if ( in_array($column['Field'], $active_fields) || $this->is(BlueFission\State::DRAFT) )
							$this->_data[$column['Field']] = isset( $this->_data[$column['Field']] ) ? $this->_data[$column['Field']] : $column['Default'];
					}
					$this->_fields[$table] = $fields;
				}
				$fields = null;
			}
			
			$this->_config[self::NAME_FIELD] = null;
			$this->halt( BlueFission\State::DRAFT );
			$this->perform( BlueFission\Event::CHANGE );
		}
		$fields = array();

		reset($this->_fields);
		while ($table = each($this->_fields))
		{
			$table = $table['value'];
			$fields = array_merge($fields, $table);
		}
		reset($this->_fields);

		return $fields;
	}
	
	private function tables()
	{
		$tables = array();
		foreach ( $this->_fields as $table=>$fields)
		{
			$tables[] = $table;
		}
		return $tables;
	}
	
	private function table( $name )
	{
		$table = isset( $this->_fields[$name] ) ? $this->_fields[$name] : array();
		return $table;
	}
	
	private function primary() 
	{
		$output = false;
		foreach ($this->fields() as $a) {
			if($a['Key'] == 'PRI') return $a['Field'];
			if($a['Key'] == 'MUL') return $a['Field'];
			if($a['Key'] == 'UNI') return $a['Field'];
			
		}
		return $output;
	}
	
	private function validate($field_name = null, $table = null) 
	{
		$fields = $this->fields();
		// if no table is specified, used the first available entry.
		$table = $table ? $table : ( isset( $tables[0] ) ? $tables[0] :current( DevArray::toArray( $this->config(self::NAME_FIELD) ) ) );
	
		$passed = true;
		
		if (isset($fields[$field_name])) {
			$field = $fields[$field_name];
			$type = strtolower($field['Type']);
				
			//If duplicate entry
			if ($field['Key'] == 'PRI' || $field['Key'] == 'UNI') 
			{
				if (self::inDB($field_name, $this->field($field_name), $table)) 
				{
					// In what case do we really need to know this?
					$this->status("A row having field '$field_name' with value '" . $this->field($field_name) . "' already exists.");
				}
				
			} else {					
				if ( $this->field($field_name) !== 0 && $this->field($field_name) == '' ) {
					if (!$field['Null'] || $field['Null'] == 'NO') {
						if (dev_is_substr($type, 'date')) {
							//$this->field($field_name, dev_join_date($field_name));
							$this->field($field_name, date('Y-m-d'));
							if (!is_string($this->field($field_name)) || !DevDateTime::string_is_date($this->field($field_name))) {
								$this->status("Field '$field_name' contains an inaccurate date format!");
								$passed = false;
							}
						} else {
							$this->status("Field '$field_name' cannot be empty!");
							$passed = false;
						}
					}
				} else {
					//Correct Datatype/Size
					if (dev_is_substr($type, 'int') || dev_is_substr($type, 'double') || dev_is_substr($type, 'float')) {
						if (!is_numeric($this->field($field_name))) {
							$this->status("Field '$field_name' must be numeric!");
							$passed = false;
						}
					}
					if (dev_is_substr($type, 'char') || dev_is_substr($type, 'text')) {
						if (!is_string($this->field($field_name))) {
							$this->status("Field '$field_name' is not text!");
							$passed = false;
						}
						if (isset($field['LENGTH']) && DevValue::isNotNull($field['LENGTH']) && strlen($this->field($field_name)) > $field['LENGTH'])  {
							$this->status("Field '$field_name' is greater than maximum allowed string length!");
							$passed = false;
						}
					}
					if (dev_is_substr($type, 'date')) {
						if (!is_string($this->field($field_name)) || !DevDateTime::string_is_date(($this->field($field_name)))) {
							$this->field($field_name, dev_join_date($field_name));
							if (!is_string($this->field($field_name)) || !DevDateTime::string_is_date($this->field($field_name))) {
								$this->status("Field '$field_name' contains an inaccurate date format!");
								$passed = false;
							}
						}
					}
					if (dev_is_substr($type, 'set')) {
						if (is_array($this->field($field_name))) {
							$this->field($field_name, implode(', ', $this->field($field_name)));
						} elseif (!is_string($this->field($field_name))) {
							$this->status("Field '$field_name' contains invalid input!");
							$passed = false;
						}
					}
				}
			}
		}		
		return $passed;
	}
	
	public function limit($start = 0, $end = '') 
	{
		$this->_row_start = $start;
		$this->_row_end = $end;
	}
	
	private function start () 
	{
		return $this->_row_start;
	}
	
	private function end () 
	{
		return $this->_row_end;
	}
	
	public function condition($member, $condition = null, $value = null) 
	{
		//if (!$this->exists($member)) return false;
		$values = array('=', '<=>', '>', '<', '>=', '<=', '<>', 'IS', 'IS NOT', 'LIKE', 'NOT LIKE');
		if (DevValue::isNull($condition) && DevValue::isNull($value))
		{
			foreach ($this->_conditions as $a=>$b) {
				foreach (explode(',', $a) as $c) {
					if (trim($c) == $member) return $b;
				}
			}
		}
		if ( DevValue::isNotEmpty( $value ) ) 
		{
			if ( !in_array(strtoupper($condition), $values)) return false;
			$this->_conditions[$member] = $condition;
			if (strpos($member, ',')) 
			{
				$member_r = explode(',', $member);
				foreach ($member_r as $a) $this->field($a, $value);
			} else {
				$this->field($member, $value);
			}
		}
	}
	
	public function order($member, $order = null) 
	{
		//if (!$this->exists($member)) return false;
		$values = array('ASC', 'DESC');
		if (DevValue::isNull($order))
		{
			foreach ($this->_order as $a=>$b) {
				foreach (explode(',', $a) as $c) {
					if (trim($c) == $member) return $b;
				}
			}
		}
		if ( !in_array(strtoupper($order), $values)) return false;
		$this->_order[$member] = $order;
	}
	
	public function aggregate($member, $function = null) 
	{
		//if (!$this->exists($member)) return false;

		$values = array('AVG', 'BIT_AND', 'BIT_OR', 'BIT_XOR', 'COUNT', 'GROUP_CONCAT', 'MAX', 'MIN', 'STD', 'STDDEV_POP', 'STDDEV_SAMP', 'STDDEV', 'SUM', 'VAR_POP', 'VAR_SAMP', 'VARIANCE');

		if (DevValue::isNull($function))
		{
			return $this->_aggregate[$member];
		}

		if ( !in_array(strtoupper($function), $values)) return false;
		$this->_aggregate[$member] = $function;
	}
	
	public function relation($member, $field = null) 
	{
		//if (!$this->exists($member)) return false;
		
		if (DevValue::isNull($field))
			return $this->_relations[$member];
		
		$this->_relations[$field] = $member;
	}
	
	public function distinction($member) 
	{
		$this->_distinctions[] = $member;
	}
	
	private function conditionKey($member) 
	{
		if (!$this->exists($member)) return false;
		
		foreach ($this->_conditions as $a=>$b) {
			foreach (explode(',', $a) as $c) {
				if (trim($c) == $member) return $a;
			}
		}
	}
	
	private function whereCase($table = '', $member, $value = '') 
	{
		$tables = $this->tables();
		$table = ( DevValue:isNull( $table ) ) ? $tables[0] : $table;
		$where = '';
		$where_r = array();

		$fields = $this->table($table);
	
		$condition = $this->condition($member);
		$condition_str = is_array( $condition ) ? $condition[0] : $condition; 
		if ( DevValue::isNotEmpty( $this->field($member) ) && array_key_exists($member, $fields) ) 
		{
			//Allow for fulltext searches
			if ( strtoupper( $condition_str ) == 'MATCH' ) 
			{
				$match_var = $this->conditionKey( $member );
				if ( strpos( $match_var, ',' ) ) 
				{
					$match_r = explode( ',', $match_var );
					foreach ( $match_r as $c=>$d ) 
					{
						$match_r[$c] = "$table." . trim($d);
					}
					$match_str = implode(', ', $match_r);
				} 
				elseif ( array_key_exists( $member, $this->_conditions ) ) 
				{
					$match_str = "$table.$member";
				}
				if ( is_array( $value ) ) 
				{
					foreach ( $value as $a ) 
					{
						if ( DevValue::isNotNull( $a ) ) 
						{
							$where_r[] = "MATCH($match_str) AGAINST (" . MysqlLink::sanitize($a) . ")";
						}
					}
					$where = implode(' OR ', $where_r);
				} 
				else 
				{
					$where = "MATCH($match_str) AGAINST (" . MysqlLink::sanitize($value) . ")";
				}
			} 
			elseif ( strtoupper( $condition_str ) == 'IN' ) 
			{
				if ( is_array( $value ) ) 
				{
					foreach ( $value as $a )
					{
						if ( DevValue::isNotNull( $a ) ) 
						{
							$where_r[] = $table . ".$member " . ((array_key_exists($member, $this->_conditions)) ? "$condition ": "= ") . $a;
						}
						$where = implode( ' OR ', $where_r );
					}
				} 
				else 
				{
					$where = $table . ".$member " . ((array_key_exists($member, $this->_conditions)) ? $condition : " = ") . "( $value )";
				}
			} 
			elseif ( strtoupper( $condition_str ) == 'NOT IN' ) 
			{
				if ( is_array( $value ) ) 
				{
					foreach ( $value as $a )
					{
						if ( DevValue::isNotNull( $a ) ) 
						{
							$where_r[] = $table . ".$member " . ((array_key_exists($member, $this->_conditions)) ? "$condition ": "= ") . $a;
						}
						$where = implode( ' OR ', $where_r );
					}
				} 
				else 
				{
					$where = $table . ".$member " . ((array_key_exists($member, $this->_conditions)) ? $condition : " = ") . "( $value )";
				}
			} 
			else 
			{
				if ( is_array( $value ) ) 
				{
					$count = 0;
					foreach ( $value as $a ) 
					{
						if ( DevValue::isNotNull( $a ) ) 
						{
							$temp_where = '';
							$condition_str = ((array_key_exists($member, $this->_conditions)) ? ((is_array($condition)) ? $condition[$count] : $condition) : " = ");
							
							if ( $condition_str == 'Like' || $condition_str == '^' ) 
							{
								$a = "$a%";
								$condition_str = "LIKE";
							}
							elseif ( $condition_str == 'likE' || $condition_str == '$' ) 
							{
								$a = "%$a";
								$condition_str = "LIKE";
							}
							elseif ( strtoupper( $condition_str ) == 'LIKE' || $condition_str == "*" ) 
							{
								$a = "%$a%";
								$condition_str = "LIKE";
							}
							elseif ( strtoupper( $condition_str ) == 'NOT LIKE' || $condition_str == "!" ) 
							{
								$a = "%$a%";
								$condition_str = "NOT LIKE";
							}
							
							$temp_where = $table . ".$member " . $condition_str;
							$temp_where .= MysqlLink::sanitize( $a );	
							$where_r[] = $temp_where;
							$count++;
						}
					}
					$where = implode( ( is_array( $condition ) ) ? ' AND ' : ' OR ', $where_r );
				} 
				else 
				{
					//$where = $table . ".$member " . ( ( array_key_exists( $member, $this->_conditions ) ) ? $condition : " = " );
					$where = $table . ".$member " . ($condition ? $condition : ' = ');
					if ( $condition_str == 'Like' ) 
					{
						$value = "$value%";
					}
					elseif ( $condition_str == 'likE' ) 
					{
						$value = "%$value";
					}
					elseif ( strtoupper( $condition_str ) == 'LIKE' ) 
					{
						$value = "%$value%";
					}
					elseif ( strtoupper( $condition_str ) == 'NOT LIKE' ) 
					{
						$value = "%$value%";
					}
					$where .= MysqlLink::sanitize( $value );	
				}
			}
			if ( DevValue::isNotNull( $where ) ) $where = "($where) ";
		}

		return $where;
	}
	
	private function orderCase($table = null, $member) 
	{
		$tables = $this->tables();
		$table = (DevValue::isNull($table)) ? $tables[0] : $table;
		$sort = null;
		$members = $this->table($table);
		
		if (array_key_exists($member, $this->_order) && array_key_exists($member, $members) ) 
		{
			if (strtoupper($this->order($member)) == 'RAND()') $sort = " " . $this->order($member);
			else $sort = $table . ".$member " . $this->order($member);
		}
		
		return $sort;
	}

	private function aggregateCase($table = null, $member) 
	{
		$tables = $this->tables();
		$table = (DevValue::isNull($table)) ? $tables[0] : $table;
		$agg = null;
		$members = $this->table($table);
		
		if (array_key_exists($member, $this->_aggregate) && array_key_exists($member, $members) ) 
		{
			$agg = $this->aggregate($member) . "(" . $table . ".$member " . ")";
		}
		
		return $agg;
	}
	
	private function distinctCase($table = null, $member) 
	{
		$tables = $this->tables();
		$table = (DevValue::isNull($table)) ? $tables[0] : $table;
		$distinct = '';
		if (in_array($member, $this->_distinctions)) 
		{
			$distinct = ' ' . $table . ".$member ";
		}
		return $distinct;
	}
	
	private function arrayKeyIntersect($arr1, $arr2) 
	{
		$array = array();
		if (DevValue::isNotNull($arr2)) {
			foreach ($arr1 as $a=>$b) if (array_key_exists ( $a, $arr2)) $array[$a] = $b;
		}
		return $array;
	}
	
	public function reset()
	{
		$this->_conditions = array();
		$this->_distinctions = array();
		$this->_aggregate = array();
		$this->_row_start = array();
		$this->_row_end = array();
		$this->_order = array();
		$this->_query = null;
	}
	
	public function exists($var) 
	{
		$fields = $this->fields();
		
		$active_fields = $this->config('fields');
		if ($var != '' && array_key_exists( $var, $fields ) ) 
		{
			if (DevValue::isEmpty($active_fields) || in_array($var, $active_fields)) 
			{
				return true;
			}	
			//otherwise, proceed as normal
			else
			{
				return false;
			}
		}
		 else 
		{
			return false;
		}
		return false;
	}

	public function error()
	{
		$db = $this->_source;
		return $db->status();
	}

	public static function inDB( $field, $value, $table ) {
		$db = new MysqlLink( array( 'table'=>$table ) );
		if ( DevValue::isNotNull ($value) )
		{ 
			//$db->field($field, $value);
			$db->query("select from $table where $field = $value");
			$result = $db->result();
			if ($result && count($result->num_rows > 0)) 
			{
				return true;
			}
		}
		return false;
	}
}