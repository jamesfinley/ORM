<?php

class DB {
	
	public $db = null;
	private $queries = array();
	private $where = array();
	private $having = array();
	private $select = '*';
	private $join = array();
	private $order = null;
	private $limit = null;
	private $offset = null;
	private $group = null;
	
	private $allow_manipulation = false;
	
	public static function Instance($host = null, $user = null, $pass = null, $database = null)
	{
		static $instance = null;
		if ($instance === null)
		{
			$instance = new DB($host, $user, $pass, $database);
		}
		return $instance;
	}
	
	private function __construct($host, $user, $pass, $database)
	{
		$db = new mysqli($host, $user, $pass, $database);
		$this->db = $db;
		$this->queries = array();
		$this->where = array();
		$this->having = array();
		$this->join = array();
		
		$this->allow_manipulation = false;
	}
	
	private function reset()
	{
		$this->where = array();
		$this->having = array();
		$this->select = '*';
		$this->join = array();
		$this->order = null;
		$this->limit = null;
		$this->offset = null;
		$this->group = null;
	}
	
	//$query can be either a field name or an associative array of key/value pairs
	public function where($query, $value = null)
	{
		if (!is_array($query) && $value)
		{
			$query = array(
				$query => $value
			);
		}
		if (is_array($query))
		{
			$supported_functions = array('FROM_UNIXTIME', 'UNIX_TIMESTAMP', 'NOW');
			
			foreach ($query as $key => $value)
			{
				$has_function = false;
				foreach ($supported_functions as $supported_function)
				{
					if (strpos($value, $supported_function.'(') !== false)
					{
						$has_function = true;
					}
				}
				$value = $has_function === false ? '"' . $this->db->real_escape_string($value) . '"' : $value;
				$query[$key] = $value;
			}
		
			foreach ($query as $key => $value)
			{
				$this->where[] = array($key, $value);
			}
		}
		else
		{
			$this->where[] = array($query);
		}
		
		return $this;
	}
	
	private function build_where()
	{
		if (count($this->where) > 0)
		{
			$query = ' WHERE ';
			$sets = array();
			foreach ($this->where as $where)
			{
				if (count($where) == 2)
				{
					$sets[] = $where[0] . ' = ' . $where[1];
				}
				else
				{
					$sets[] = $where[0];
				}
			}
			$query .= implode(' AND ', $sets);
			
			return $query;
		}
		return '';
	}
	
	//$query can be either a field name or an associative array of key/value pairs
	public function having($query, $value = null)
	{
		if (!is_array($query) && $value)
		{
			$query = array(
				$query => $value
			);
		}
		if (is_array($query))
		{
			foreach ($query as $key => $value)
			{
				$this->having[] = array($key, $value);
			}
		}
		else
		{
			$this->having[] = array($query);
		}
		
		return $this;
	}
	
	private function build_having()
	{
		if (count($this->having) > 0)
		{
			$query = ' HAVING ';
			$sets = array();
			foreach ($this->having as $where)
			{
				if (count($where) == 2)
				{
					$sets[] = $where[0] . ' = ' . $where[1];
				}
				else
				{
					$sets[] = $where[0];
				}
			}
			$query .= implode(' AND ', $sets);
			
			return $query;
		}
		return '';
	}
	
	public function select($select)
	{
		$this->select = $select;
		
		return $this;
	}
	
	public function join($table_name, $on, $type = '')
	{
		$this->join[] = array(
			'table_name' 	=> $table_name,
			'on' 			=> $on,
			'type' 			=> $type
		);
		
		return $this;
	}
	
	private function build_join()
	{
		if (count($this->join))
		{
			$joins = array();
			foreach ($this->join as $j)
			{
				$join = ($j['type'] != '' ? $j['type'] . ' ' : '') . 'JOIN ' . $j['table_name'] . ' ON ' . $j['on'];
				$joins[] = $join;
			}
			return ' '.implode(' ', $joins);
		}
		return '';
	}
	
	public function order($field, $order = 'ASC')
	{
		$this->order = $field . ' ' . $order;
		
		return $this;
	}
	
	private function build_order()
	{
		if ($this->order !== null)
		{
			return ' ORDER BY ' . $this->order;
		}
		return '';
	}
	
	public function group($field)
	{
		$this->group = $field;
		
		return $this;
	}
	
	private function build_group()
	{
		if ($this->group !== null)
		{
			return ' GROUP BY ' . $this->group;
		}
		return '';
	}
	
	public function limit($limit, $offset = 0)
	{
		$this->limit = $limit;
		$this->offset = $offset;
		
		return $this;
	}
	
	private function build_limit()
	{
		if ($this->limit !== null)
		{
			$limit = ' LIMIT ';
			if ($this->offset !== null)
			{
				$limit .= $this->offset . ', ';
			}
			$limit .= $this->limit;
			
			return $limit;
		}
		return '';
	}
	
	public function get($table_name, $where = null)
	{
		if ($where !== null)
		{
			$this->where($where);
		}
		
		$query = 'SELECT ' . $this->select . ' FROM ' . $table_name . $this->build_join() . $this->build_where() . $this->build_group() . $this->build_having() . $this->build_order() . $this->build_limit();
		
		$this->reset();
		
		return $this->query($query);
	}
	
	public function create($table_name, $data)
	{
		$keys = array();
		$values = array();
		
		$supported_functions = array('FROM_UNIXTIME', 'UNIX_TIMESTAMP', 'NOW');
		
		foreach ($data as $key => $value)
		{
			$has_function = false;
			foreach ($supported_functions as $supported_function)
			{
				if (strpos($value, $supported_function.'(') !== false)
				{
					$has_function = true;
				}
			}
			
			$keys[] = $key;
			$values[] = $has_function === false ? '"' . $this->db->real_escape_string($value) . '"' : $value;
		}
		
		$query = 'INSERT INTO ' . $table_name . '(' . implode(',', $keys) . ') VALUES (' . implode(',', $values) . ')';
		
		return $this->query($query);
	}
	
	public function update($table_name, $data, $where = null)
	{
		if ($where !== null)
		{
			$this->where($where);
		}
		
		$query = 'UPDATE ' . $table_name;
		
		$set = array();
		
		$supported_functions = array('FROM_UNIXTIME', 'UNIX_TIMESTAMP', 'NOW');
		
		$count = 0;
		foreach ($data as $key => $value)
		{
			$count++;
			
			$has_function = false;
			foreach ($supported_functions as $supported_function)
			{
				if (strpos($value, $supported_function.'(') !== false)
				{
					$has_function = true;
				}
			}
			$value = $has_function === false ? '"' . $this->db->real_escape_string($value) . '"' : $value;
			$set[] = $key . ' = ' . $value;
		}
		$set = implode(', ', $set);
		
		$query .= ($count > 0 ? ' SET ' . $set : '') . $this->build_where() . $this->build_order() . $this->build_limit();
		
		$this->reset();
		
		return $this->query($query);
	}
	
	public function destroy($table_name, $where = null)
	{
		if ($where !== null)
		{
			$this->where($where);
		}
		
		$query = 'DELETE FROM ' . $table_name . $this->build_where() . $this->build_order() . $this->build_limit();
		
		$this->reset();
		
		return $this->query($query);
	}
	
	/*! Database Manipulation */
	public function allow_manipulation($allow_manipulation)
	{
		$this->allow_manipulation = $allow_manipulation;
	}
	
	public function table_exists($table_name)
	{
		$table_name = $this->db->real_escape_string($table_name);
		$result = $this->query("SELECT 1 FROM {$table_name}");
		
		return !!$result->num_rows;
	}
	
	public function create_table($table_name, $include_if_not_exists = true)
	{
		if ($this->allow_manipulation)
		{
			$table_name = $this->db->real_escape_string($table_name);
			$query = "CREATE TABLE" . ($include_if_not_exists ? ' IF NOT EXISTS' : '') . " {$table_name} ";
			$this->query($query);
		}
	}
	
	public function rename_table($old_table_name, $new_table_name)
	{
		if ($this->allow_manipulation)
		{
			$old_table_name = $this->db->real_escape_string($old_table_name);
			$new_table_name = $this->db->real_escape_string($new_table_name);
			$query = "ALTER TABLE {$old_table_name} RENAME TO {$new_table_name}";
			$this->query($query);
		}
	}
	
	public function drop_table($table_name, $include_if_exists = true)
	{
		if ($this->allow_manipulation)
		{
			$table_name = $this->db->real_escape_string($table_name);
			$query = "DROP TABLE" . ($include_if_exists ? ' IF EXISTS' : '') . " {$table_name} ";
			$this->query($query);
		}
	}
	
	/* $fields = array(
	 *     'field_name' => array(
	 *         'type' => '',
	 *         'constraint' => '',
	 *         'unsigned' => true||false,
	 *         'default' => '',
	 *         'null' => true||false, //default is false, meaning NOT NULL
	 *         'auto_increment' => true||false
	 *     )
	 * )
	 */
	public function add_column($table_name, $fields)
	{
		if ($this->allow_manipulation)
		{
			$table_name = $this->db->real_escape_string($table_name);
			
			foreach ($fields as $name => $info)
			{
				if (is_array($info))
				{
					$field = "{$name} {$info['type']}" .
						($info['constraint'] ? "({$info['constraint']})" : '') .
						($info['null'] ? ' NULL' : ' NOT NULL') .
						($info['default'] ? " DEFAULT '" . $this->db->real_escape_string($info['default']) . "'" : '') .
						($info['auto_increment'] ? ' AUTO_INCREMENT' : '');
				}
				else
				{
					$field = "{$name} {$info}";
				}
				$query = "ALTER TABLE {$table_name} ADD {$field}";
				$this->query($query);
			}
		}
	}
	
	public function query($sql)
	{
		$this->queries[] = $sql;
		return $this->db->query($sql);
	}
	
	public function runtime_info()
	{
		return array(
			'total' => count($this->queries),
			'queries' => $this->queries
		);
	}
	
}
