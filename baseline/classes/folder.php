<?
class Folder
{
	private $path = null;

	function __construct($path = false, $mode = null, $create = false)
	{
		$mode=($mode==null ? 0700 : $mode);
		if (!is_dir($path) && $create)
		{
			$this->mkdirr($path, $mode);
		}
		$this->cd($path);
	}


	public function __get($var)
	{
		switch ($var)
		{
			case "path": return $this->path; break;
			case "size": return $this->dirsize(); break;
		}
		return false;
	}

	public function cd($desiredPath)
	{
		$desiredPath = realpath($desiredPath);
		$newPath = $this->isAbsolute($desiredPath) ? $desiredPath : $this->addPathElement($this->path, $desiredPath);
		$isDir = (is_dir($newPath) && file_exists($newPath)) ? $this->path = $newPath : false;
		return $isDir;
	}

	public function ls($sort = true, $noDotFiles = false)
	{
		$dirs = $files = array();
		$dir = opendir($this->path);
		if ($dir)
		{
			while (false !== ($n = readdir($dir)))
			{
				if ((!preg_match('#^\.+$#', $n) && $noDotFiles == false) || ($noDotFiles == true && !preg_match('#^\.(.*)$#', $n)))
				{
					if (is_dir($this->addPathElement($this->path, $n)))
					{
						$dirs[] = $n;
					}
					else
					{
						$files[] = $n;
					}
				}
			}

			if ($sort || $this->sort)
			{
				sort ($dirs);
				sort ($files);
			}
			closedir ($dir);
		}
		return array($dirs,$files);
	}

	public function find($regexp_pattern = '.*')
	{
		$data = $this->ls();

		if (!is_array($data))
		{
			return array();
		}

		list($dirs, $files) = $data;
		$found =  array();

		foreach ($files as $file)
		{
			if (preg_match("/^{$regexp_pattern}$/i", $file))
			{
				$found[] = $file;
			}
		}
		return $found;
	}

	public function findRecursive($pattern = '.*')
	{
		$startsOn = $this->path;
		$out = $this->_findRecursive($pattern);
		$this->cd($startsOn);
		return $out;
	}

	private function _findRecursive($pattern)
	{
		list($dirs, $files) = $this->ls();

		$found = array();
		foreach ($files as $file)
		{
			if (preg_match("/^{$pattern}$/i", $file))
			{
				$found[] = $this->addPathElement($this->path, $file);
			}
		}
		$start = $this->path;
		foreach ($dirs as $dir)
		{
			$this->cd($this->addPathElement($start, $dir));
			$found = array_merge($found, $this->findRecursive($pattern));
		}
		return $found;
	}


	private function isAbsolute($path)
	 {
		return preg_match('#^\/#', $path) || preg_match('#^[A-Z]:\\\#i', $path);
	}

	private function isSlashTerm($path)
	{
		return preg_match('#[\\\/]$#', $path) ? true : false;
	}

	function isWindowsPath($path)
	{
		return preg_match('#^[A-Z]:\\\#i', $path) ? true : false;
	}

	private function correctSlashFor($path)
	{
		return $this->isWindowsPath($path) ? '\\' : '/';
	}

	public function slashTerm($path)
	{
		return $path . ($this->isSlashTerm($path) ? null : $this->correctSlashFor($path));
	}

	private function addPathElement($path, $element) {
		return $this->slashTerm($path) . $element;
	}

	public function mkdirr($pathname, $mode = null)
	{
		if (is_dir($pathname) || empty($pathname))
		{
			return true;
		}

		if (is_file($pathname))
		{
			trigger_error('File exists', E_USER_WARNING);
			return false;
		}
		$nextPathname = substr($pathname, 0, strrpos($pathname, DIRECTORY_SEPARATOR));

		if ($this->mkdirr($nextPathname, $mode))
		{
			if (!file_exists($pathname))
			{
				umask (0);
				$mkdir = mkdir($pathname, $mode);
				return $mkdir;
			}
		}
		return false;
	}

	public function dirsize()
	{
		$size = 0;
		$directory = $this->slashTerm($this->path);
		$stack = array($directory);
		$count = count($stack);
		for ($i = 0, $j = $count; $i < $j; ++$i)
		{
			if (is_file($stack[$i]))
			{
				$size += filesize($stack[$i]);
			}
			elseif (is_dir($stack[$i]))
			{
				$dir = dir($stack[$i]);

				while (false !== ($entry = $dir->read()))
				{
					if ($entry == '.' || $entry == '..')
					{
						continue;
					}
					$add = $stack[$i] . $entry;

					if (is_dir($stack[$i] . $entry))
					{
						$add = $this->slashTerm($add);
					}
					$stack[ ]= $add;
				}
				$dir->close();
			}
			$j = count($stack);
		}
		return $size;
	}

}
?>