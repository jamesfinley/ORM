<?php

class Model {
	
	private $associations;
	private $table_name;
	private $table_alias;
	private $primary_key;
	private $hooks;
	
	private $base_filter;
	private $base_filter_joins;
	
	function __construct()
	{
		$this->init();
	}
	
	function init() {}
	
	
	function primary_key($primary_key = null)
	{
		if (empty($this->primary_key) || $primary_key)
		{
			$this->primary_key = $primary_key ? $primary_key : $this->table_name().'_id';
		}
		
		return $this->primary_key;
	}
	
	function table_name($table_name = null)
	{
		if (empty($this->table_name) || $table_name)
		{
			$generated_table_name = strtolower(get_class($this));
			$this->table_name = $table_name ? $table_name : $generated_table_name;
		}
		
		return $this->table_name;
	}
	
	function table_alias($table_alias = null)
	{
		if ($table_alias)
		{
			$this->table_alias = $table_alias;
		}
		
		return $this->table_alias;
	}
	
	function get($justAlias = false)
	{
		if ($justAlias)
		{
			return $this->table_alias() ? $this->table_alias() : $this->table_name();
		}
		return $this->table_name() . ($this->table_alias() ? ' ' . $this->table_alias() : '');
	}
	
	function base_filter($base_filter = null)
	{
		if (empty($this->base_filter) || $base_filter)
		{
			$this->base_filter = $base_filter ? $base_filter : array();
		}
		return $this->base_filter;
	}
	
	function base_filter_joins($table = null, $on = null)
	{
		if (empty($this->base_filter_joins))
		{
			$this->base_filter_joins = array();
		}
		if ($table && $on)
		{
			$this->base_filter_joins[] = array(
				'table' => $table,
				'on' => $on
			);
		}
		return $this->base_filter_joins;
	}
	
	/*! Associations */
	function associations()
	{
		return $this->associations;
	}
	function belongs_to($name, $options = null)
	{
		$this->associations[] = array(
			'name' => $name,
			'type' => 'belongs_to',
			'options' => $options
		);
	}
	
	function has_many($name, $options = null)
	{
		$this->associations[] = array(
			'name' => $name,
			'type' => 'has_many',
			'options' => $options
		);
	}
	
	function association($name)
	{
		foreach ($this->associations() as $association)
		{
			if ($association['name'] == $name)
			{
				return $association;
			}
		}
		return null;
	}
	
	/*! Hooks */
	function before_save($name)
	{
		$this->hooks['before_save'][] = $name;
	}
	function after_save($name)
	{
		$this->hooks['after_save'][] = $name;
	}
	function before_update($name)
	{
		$this->hooks['before_update'][] = $name;
	}
	function after_update($name)
	{
		$this->hooks['after_update'][] = $name;
	}
	function run_hook($hook, $row = null, $data = null)
	{
		foreach ($this->hooks[$hook] as $hook)
		{
			$data = $this->$hook($row, $data);
		}
		return $data;
	}
	
	/*! Process Methods */
	private function process_results($results)
	{
		$processed = array();
		while($row = $results->fetch_object())
		{
			$processed[] = $this->process_result($row);
		}
		
		return $processed;
	}
	
	private function process_result($row)
	{
		return new ModelRow($this, $row);
	}
	
	/*! CRUD Functions */
	public function create($data)
	{
		$clean_data = array();
		
		foreach ($data as $key => $value)
		{
			if (get_class($value) == 'ModelRow')
			{
				$primary_key = $value->model->primary_key();
				$id = $value->$primary_key;
				
				$clean_data[$primary_key] = $id;
			}
			else
			{
				$clean_data[$key] = $value;
			}
		}
		$data = $clean_data;
		
		$db = DB::Instance();
		$db->create($this->table_name(), $data);
		$id = $db->db->insert_id;
		
		return $this->find($id);
	}
	public function update($id, $data)
	{
		$row = $this->find($id);
		$row->update($data);
	}
	public function destroy($id)
	{
		$row = $this->find($id);
		$row->destory();
	}
	public function find($options = array())
	{
		$db = DB::Instance();
		
		//is $options a number
		if (is_numeric($options))
		{
			//return object for ID
			$id = $options;
			$results = $db->where($this->get(true) . '.' . $this->primary_key(), $id)->limit(1)->get($this->get());
			if ($results->num_rows)
			{
				$results = $this->process_results($results);
				return $results[0];
			}
			return null;
		}
		else
		{
			$db->where($this->base_filter());
			if (count($this->base_filter_joins()))
			{
				foreach ($this->base_filter_joins() as $join)
				{
					$db->join($join['table'], $join['on']);
				}
			}
			
			if ($where = value_for_key('where', $options))
			{
				$db->where($where);
			}
			if ($having = value_for_key('having', $options))
			{
				$db->having($having);
			}
			if ($limit = value_for_key('limit', $options))
			{
				$offset = value_for_key('offset', $options, 0);
				
				$db->limit($limit, $offset);
			}
			if ($order = value_for_key('order', $options))
			{
				$order = explode(' ', $order);
				
				if (count($order) == 2)
				{
					$db->order($order[0], $order[1]);
				}
				else
				{
					$db->order($order[0]);
				}
			}
			if ($select = value_for_key('select', $options))
			{
				$db->select($select);
			}
			
			$results = $this->process_results($db->get($this->get()));
			
			if ($include = value_for_key('include', $options))
			{
				foreach ($include as $key)
				{
					if ($association = $this->association($key))
					{
						if ($association['type'] == 'belongs_to')
						{
							$class_name = ucfirst($association['name']);
							$class = new $class_name;
							$primary_key = $class->primary_key();
							
							$ids = array();
							foreach ($results as $row) $ids[] = $row->$primary_key;
							$include_results = $class->process_results($db->where('( ' . $primary_key . ' IN (' . implode(',', $ids) . ') )')->get($class->table_name()));
							foreach ($include_results as $include_row)
							{
								foreach ($results as $row)
								{
									if ($row->$primary_key == $include_row->$primary_key)
									{
										$row->add_data($association['name'], $include_row);
									}
								}
							}
						}
					}
				}
			}
			
			return $results;
		}
	}
	
	public function first($options = array())
	{
		$options = array_merge(array(
			'limit' => 1,
			'order' => $this->created_at_key() . ' ASC'
		), $options);
		
		$results = $this->find($options);
		if (count($results))
		{
			return $results[0];
		}
		return null;
	}
	public function earliest() { return $this->last(); }
	
	public function last($options = array())
	{
		$options = array_merge(array(
			'limit' => 1,
			'order' => $this->created_at_key() . ' DESC'
		), $options);
		
		$results = $this->find($options);
		if (count($results))
		{
			return $results[0];
		}
		return null;
	}
	public function latest() { return $this->last(); }
	
	public function count($options = array())
	{
		$options['select'] = 'COUNT(' . $this->get(true) . '.' . $this->primary_key() . ') AS count';
		$results = $this->find($options);
		$row = $results[0];
		$count = $row->count;
		return $count;
	}
	
	/*function __call($method, $arguments)
	{
		
	}*/
	
}
