<?

class File
{
	private $folder = null;
	private $name = null;

	public function __construct($path)
	{
		$this->folder = new Folder(dirname($path));
		$this->name = basename($path);
		if (!$this->exists())
		{
			if (!$this->create())
			{
				return false;
			}
		}
	}

	public function read()
	{
		$contents = file_get_contents($this->getFullPath());
		return $contents;
	}

	public function append($data)
	{
		return $this->write($data, 'a');
	}

	public function write($data, $mode = 'w')
	{
		$file = $this->fullPath;
		if (!($handle = fopen($file, $mode)))
		{
			return false;
		}

		if (!fwrite($handle, $data))
		{
			return false;
		}

		if (!fclose($handle))
		{
			return false;
		}
		return true;
	}

	public function __get($var)
	{
		switch($var)
		{
			case "name": return $this->name; break;
			case "size": return filesize($this->fullpath); break;
			case "fullPath": return  $this->folder->slashTerm($this->folder->path) . $this->name;
			case "extension":return File::getExtension($this->name);	break;
			case "lastAccess": return filemtime($this->fullPath); break;
			case "lastChange": return filemtime($this->fullPath); break;
			case "folder": return $this->folder; break;
			default : return false;
		}
		return false;
	}
	
	public static function getExtension($filename)
	{
		$ext = '';
		$parts = explode('.', $filename);

		if (count($parts) > 1)
		{
			$ext = array_pop($parts);
		}
		else
		{
			$ext = '';
		}
		return $ext;
	}

	private function create()
	{
		$dir = $this->folder->path;
		if (file_exists($dir) && is_dir($dir) && is_writable($dir) && !$this->exists()) {
			if (!touch($this->fullPath))
			{
				print ('Could not create '.$this->getName().'!');
				return false;
			}
			else
			{
				return true;
			}
		}
		else
		{
			print ('Could not create '.$this->getName().'!');
			return false;
		}
	}

	public function exists()
	{
		return file_exists($this->fullPath);
	}

	public function delete()
	{
		return  unlink($this->fullPath);
	}

	public function writable()
	{
		return is_writable($this->fullPath);
	}

	public function readable()
	{
		return is_readable($this->fullPath);
	}
	
	public static function toReadableSize($size)
	{
		switch($size)
		{
			case 0:
				return '0 байтов';
			case 1: 
				return '1 байт';
			case $size < 1024: 
				return $size . ' байт';
			case $size < 1024 * 1024: 
				return sprintf("%01.2f", $size / 1024, 0) . ' кб';
			case $size < 1024 * 1024 * 1024: 
				return sprintf("%01.2f", $size / 1024 / 1024, 2) . ' мб';
			case $size < 1024 * 1024 * 1024 * 1024:
				return sprintf("%01.2f", $size / 1024 / 1024 / 1024, 2) . ' гб';
			case $size < 1024 * 1024 * 1024 * 1024 * 1024:
				return sprintf("%01.2f", $size / 1024 / 1024 / 1024 / 1024, 2) . ' тб';
		}
	} 
}

?>