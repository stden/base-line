<?

class Config
{
	private $vars=array();
	private $storeMethod = 'ini';

	public function __construct($filename,$read=true)
	{
		if ($read) $this->read($filename);
	}
	
	public function read($filename)
	{
		$file = new File($filename);
		if($file->exists())
		{
			switch($this->storeMethod)
			{
				case 'ini':
					if ($this->vars=parse_ini_file($filename,true)) return true;
					else throw new Exception('Could not parse '.$filename.'!');
				break;
				
				case 'serialized':
					if($this->vars = deserialize(file_get_content($filename))) return true;
					else throw new Exception('Could not parse '.$filename.'!');
				break;
				
				case 'xml':
					throw new Exception('Not implemented!');
				break;
				
			}
		}
		else throw new Exception('Could not find '.$filename.'!');
	}
	
	function readXml($xml)
	{
		$parser = xml_parser_create('UTF-8');
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); 
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); 
		xml_parse_into_struct($parser, $xml, $values);
		xml_parser_free($parser);

		$return = array(); 
		$stack = array(); 
		foreach($values as $val)
		{
			if($val['type'] == "open")
	  			array_push($stack, $val['tag']);
			elseif($val['type'] == "close")
				array_pop($stack);
			elseif($val['type'] == "complete")
			{
				array_push($stack, $val['tag']);
	  			setArrayValue($return, $stack, $val['value']);
				array_pop($stack);
			}
  		}
  		$this->vars = $return;
	}
	
	public function writeXml ($filename)
	{
		# code...
	}
	
	function writeIni($assoc_array)
	{
		$content = '';
		$sections = '';

		foreach ($assoc_array as $key => $item) 
		{
			if (is_array($item)) 
			{
				$sections .= "\n[{$key}]\n";
				foreach ($item as $key2 => $item2) 
				{
					if (is_numeric($item2) || is_bool($item2))
						$sections .= "{$key2} = {$item2}\n";
					else
						$sections .= "{$key2} = \"{$item2}\"\n";
				}       
			} 
			else 
			{
				if(is_numeric($item) || is_bool($item))
					$content .= "{$key} = {$item}\n";
				else
					$content .= "{$key} = \"{$item}\"\n";
			}
		}       
		$content .= $sections;
		$file=new File(SP . 'configs'. DS . $this->name . '.conf');
		return ($file->write($content,'w+'));
	}

	public function __get($var)
	{
		if(isset($this->vars[$var])) return $this->vars[$var];
		return false;
	}

	public function __set($var,$value)
	{
		if(isset($this->vars[$var]))
		{
			$this->vars[$var]=$value;
			return true;
		}
		return false;
	}
	
	public function toArray()
	{
		return $this->vars;
	}
}

?>