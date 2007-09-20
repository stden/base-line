<?

class Db
{
	private static $link = false;
	private static $_instance;
	public static $lastInsertId;
	public static $statistics = array(
										'queriesExecuted' => 0,
										'queries' => array(),
										'totalExecutionTime' => 0
									  );
	public static $logger = false;
	private static $config;
	public static $tablePrefix = '';

	private function __construct()
	{
		try { $config = new Config( ApplicationPath . DS . 'configs' . DS . 'db.conf' ); }
		catch(Exception $e) { die('Could not find db.conf!'); }
		$this->config = $config;
		$this->tablePrefix = $config->tablePrefix;
	}

	private function __clone(){}

	public static function getInstance()
	{
		if(is_null(self::$_instance))
			self::$_instance = new self();
		return self::$_instance;
	}

	public static function escape($text)
	{
		if (get_magic_quotes_gpc() == 0) return mysql_real_escape_string($text, $this->link);
		return $text;
	}

	private function connect()
	{
		$this->link = @mysql_connect($this->config->hostname,$this->config->username,$this->config->password);
		if (!$this->link)  return false;
		mysql_query('set names utf8', $this->link);
		$result = @mysql_select_db($this->config->database, $this->link);
		if (!$result) return false; 
		return true;
	}

	private function useConnection()
	{
		if(!$this->link)
			return $this->connect();
		return true;
	}

	private function execute($query)
	{
		if( ! is_string($query) ) $query = $query->toString();
		
		if($this->useConnection())
		{
			$start = microtime(true);
			$result = @mysql_query($query, $this->link);
			$finish = microtime(true);
			
			$time = $finish - $start;
			
			if($result)
			{
				Db::$statistics['queriesExecuted']++;
				Db::$statistics['queries'][] = array( 'query' => $query, 'time'=> $time );
				return $result;
			}
		}
		$this->error();
		return false;
	}


	public function getValue($query)
	{
		$result = $this->execute($query);
		return (mysql_num_rows($res) == 0? false : mysql_result($result, mysql_field_name($result,0)));
	}

	public function getArray($query)
	{
		if(!($result=$this->execute($query))) return false;
		$table = array();
		while ($row = mysql_fetch_assoc($result)) $table[]=$row;
		mysql_free_result($result);
		return($table);
	}

	public function getRow($query)
	{
		if (!($result = $this->execute($query))) return false;
		return mysql_fetch_assoc($result);
	}

	public function getCol($query)
	{
		if(!($result = $this->execute($query))) return false;
		$table = array();
		while (($row = mysql_fetch_assoc($result)))  $table[] = $row[mysql_field_name($result,0)];
		mysql_free_result($result);
		return($table);
	}
	
	public function error()
	{
		$message = 'Db:'.mysql_errno($this->link) . ': ' . mysql_error($this->link) . " in query : $query"
		
		if(Db::logger)
			Db::logger->add('mysql', $message);
		else echo $message;
	}
	
}

?>