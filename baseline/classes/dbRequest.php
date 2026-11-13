<?

class DbRequest
{
	protected $action = 'select';
	protected $conditions = array();
	protected $joins = array();
	protected $fields = false;
	protected $tables = array();
	protected $data = array();
	protected $distinct = false;
	protected $limit = false;
	protected $offset = false;
	protected $orderBy = array();
	protected $groupBy = array();
	protected $having = array();
	protected $tablePrefix = '';
	
	public function __construct ()
	{
		$db = Db::getInstance();
		$tablePrefix = $db->tablePrefix;
	}
	
	public function select($fields)
	{
		if( is_string($fields) ) $this->fields = explode(',', $fields);
		else $this->fields = $fields;
		$this->action = 'select';
		return $this;
	}
	
	public function delete()
	{
		$this->action = 'delete';
		return $this;
	}
	
	public function insert($table, $data)
	{
		$this->tables[0] = $table;
		$this->data = $data;
		return $this;
	}
	
	public function update($table)
	{
		$this->action = 'update';
		$this->tables[0] = $table;
		return $this;
	}
	
	public function set($what)
	{
		$this->data = $what;
		return $this;
	}	
	
	public function from($tables)
	{
		if( is_string($tables) ) $this->tables = explode(',', $tables);
		else $this->tables = $tables;
		
		for($i=0;$i<sizeof($this->tables);$i++)
			$this->tables[$i] = $this->tablePrefix . $this->tables[$i];
		
		return $this;
	}
	
	public function distinct()
	{
		$this->distinct = true;
		return $this;
	}
	
	public function orderBy($fields)
	{
		if( is_string($fields) ) $this->orderBy = explode(',', $fields);
		else $this->orderBy = $fields;
		return $this;
	}
	
	public function groupBy($fields)
	{
		if( is_string($fields) ) $this->groupBy = explode(', ',$fields);
		else $this->groupBy = $fields;
		return $this;
	}
	
	public function limit($limit, $offset=null)
	{
		$this->limit = (int) $limit;
		if( !is_null($offset) ) $this->offset = (int) $offset;
		return $this;
	}
	
	public function offset ($offset)
	{
		$this->offset = (int) $offset;
		return $this;
	}
	
	public function where($key, $value=null, $type='and')
	{
		if (! is_array($key))
			$key = array($key => $value);
			
		foreach ($key as $k => $v)
		{
			$prefix = (empty($this->conditions)) ? '' : $type . ' ';
			
			if ( ! is_null($v) )
			{
				$v = Db::escape($v);
				if ( $this->hasLike($k) ) 
					$v = "%" . $v . "%";
				else if ( ! $this->hasOperator($k) )
					$k .= ' =';
				$v = " '" . $v . "'";
			}
			$this->conditions[] = $prefix.$k.$v;
		}
		return $this;
	}
	
	function having($key, $value = '', $type = 'and')
	{
		if ( ! is_array($key))
			$key = array($key => $value);

		foreach ($key as $k => $v)
		{
			$prefix = (count($this->having) == 0) ? '' : $type;

			if ($v != '')
				$v = ' '.Db::escape($v);
			$this->having[] = $prefix.$k.$v;
		}
		return $this;
	}
	
	public function orHaving($key, $value='')
	{
		return $this->having($key, $value, 'or');
	}
	
	public function orWhere($key, $value)
	{
		return $this->where($key, $value, 'or');
	}
	
	private function hasOperator($string)
	{
		return preg_match("/(\s|<|>|!|=|is null|is not null)/i", trim($string));
	}
	
	private function hasLike($string)
	{
		return strstr($string, ' like');
	}	
	
	public function join($table, $conditions, $type)
	{
		$this->joins[] = array( 'table' => $table, 'conditions' => $conditions, 'type' => $type );
		return $this;
	}
	
	
	public function toString()
	{
		$sql = '';	
		switch ($this->action)
		{
			case 'select':
				$sql = 'select ' . ($this->distinct ? 'distinct ' : '');
				$sql.= empty($this->fields) ? '*' : implode( ', ', $this->fields );
				$sql.= ' from ' . implode(', ', $this->tables). ' ';
				
				if(!empty($this->joins))
					foreach($this->joins as $join)
						$sql.= $join['type'] . ' join ' . $join['table'] . ' on ' . $join['conditions'] . ' ';
				
				$sql.= (empty($this->conditions) ? '' : ' where ' . implode(' ', $this->conditions));
				$sql.= (empty($this->groupBy) ? '' : ' group by ' . implode(', ', $this->groupBy));
				$sql.= (empty($this->orderBy) ? '' : ' order by ' . implode(', ', $this->orderBy));
				$sql.= ($this->limit ? ($this->offset ? ' limit ' . $this->offset . ', ' . $this->limit : ' limit ' . $this->limit ) : ($this->offset ? 'limit ' . $this->offset . ', -1': ''));
			break;
			
			case 'insert':
				$sql = ' insert into ' . $this->tables[0] . ' (';
				$fields = array_keys($this->data);
				$sql.= implode(', ', $fields);
				$sql.= ') values (';
				$values = array_values($this->data);
				foreach ($values as $value)
					$sql.= '"' . Db::escape( $value ) . '", ';
				$sql = substr($sql, 0, -2);
				$sql.= ')';
			break;
			
			case 'update':
				$sql = 'update '.$this->tables[0].' set';
				foreach ($this->data as $field => $value)
					$sql.= ' '.$field.'="'.Db::escape($value).'", ';
				$sql  = substr($q, 0, -2);
				$sql.= (empty($this->conditions) ? '' : implode(' ', $this->conditions));
			break;
			
		}
		return $sql;
	}
	
	

	
}

?>