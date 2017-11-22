<?php

class FileEditor 
{
	private $filename = '';
	private $contents = [];
	private $lines = -1;

	/**
	 * Constructor checks the file and if it exists
	 * sets the pointer to the file.
	 */
	public function __construct ($filename)
	{
		if (! file_exists($filename)) 
		{
			return null;
		}

		$this->filename = $filename;
	}


	public function get ()
	{
		return file_get_contents($this->filename);
	}


	/**
	 * Returns the total number of lines in the file
	 * Note: Line separators vary system to system.
	 */
	public function lines ()
	{
		if ($this->lines >= 0) return $this->lines; // cache

		$f = fopen($this->filename, 'rb');
		$this->lines = 0;
		while (!feof($f)) 
		{
			$this->lines += substr_count(fread($f, 8192), PHP_EOL);
		}
		fclose($f);
		return $this->lines;
	}



	/**
	 * Get the line number of the first occurance of needle
	 */
	public function findLine ($needle)
	{
		$file = fopen($this->filename, "rb");
		$pos = 1;
		while (($buffer = fgets($file)) !== false) 
		{
			if (stripos($buffer, $needle) !== false) 
				return $pos;
			$pos = $pos + 1;
		}
		fclose($file);
		return null;
	}

	private function _load_file ($filename)
	{
		//$this->contents = file ($filename);
	}

}