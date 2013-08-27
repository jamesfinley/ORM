<?php

class ModelRow {
	
	public $model;
	private $data;
	
	function __construct($model, $data)
	{
		$this->model = $model;
		$this->data = $data;
		
		foreach ($this->data as $key => $value)
		{
			$this->$key = $value;
		}
	}
	
	function add_data($key, $value)
	{
		$this->data->$key = $value;
		$this->$key = $value;
	}
	
	function touch()
	{
		$this->update();
	}
	
	function destroy()
	{
		//call before_update and before_save
		$data = $this->model->run_hook('before_destroy', $this);
		
		$db = DB::Instance();
		$primary_key = $this->model->primary_key();
		$db->where($primary_key, $this->$primary_key)->destroy($this->model->table_name());
		
		//call after_update and after_save
		$this->model->run_hook('after_destroy', $this);
	}
	
	function update($data = array())
	{
		//call before_update and before_save
		$data = $this->model->run_hook('before_update', $this, $data);
		$data = $this->model->run_hook('before_save', $this, $data);
		
		$db = DB::Instance();
		$primary_key = $this->model->primary_key();
		$db->where($primary_key, $this->$primary_key)->update($this->model->table_name(), $data);
		
		//call after_update and after_save
		$this->model->run_hook('after_update', $this, $data);
		$this->model->run_hook('after_save', $this, $data);
	}
	
	function __get($name)
	{
		if (property_exists($this->data, $name)) //check fields for row
		{
			return $this->data->$name;
		}
		elseif ($association = $this->model->association($name)) //check associations
		{
			if ($association['type'] == 'belongs_to')
			{
				$class_name = ucfirst($association['name']);
				$class = new $class_name;
				$primary_key = $class->primary_key();
				$id = $this->data->$primary_key;
				
				return $class->find($id);
			}
			elseif ($association['type'] == 'has_many')
			{
				$class_name = Inflector::singularize($association['name']);
				$class = new $class_name;
				//TODO: duplicate class
				
				if ($association['options'] !== null && $association['options']['through'])
				{
					$primary_key = $this->model->primary_key();
					$foreign_key = $class->primary_key();
					$table = $association['options']['through'];
					$on = $table . '.' . $foreign_key . ' = ' . $class->get(true) . '.' . $foreign_key;
					$class->base_filter_joins($table, $on);
					
					$id = $this->$primary_key;
					$class->base_filter(array($table . '.' . $primary_key => $id));
				}
				else
				{
					$primary_key = $this->model->primary_key();
					$id = $this->$primary_key;
					$class->base_filter(array($primary_key => $id));
				}
				
				return $class;
			}
		}
		elseif (method_exists($this->model, 'get_'.$name))
		{
			$name = 'get_'.$name;
			return $this->model->$name($this);
		}
	}
	
	function __call($name, $arguments)
	{
		if (method_exists($this->model, 'get_'.$name))
		{
			$name = 'get_'.$name;
			return call_user_func_array(array($this->model, $name), array_merge(array($this), $arguments));
		}
	}
	
}