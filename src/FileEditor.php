<?php namespace Autovel;

/**
 * A simple (and inefficient - TODO) file management class
 * for typical add, edit, replace, delete operations 
 */
class FileEditor 
{
	private $filename = '';
	private $fileExists = true;
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
			$this->fileExists = false;
		}

		$this->filename = $filename;
	}



	/**
	 * Returns true if the file actually exists, false otherwise.
	 */
	public function exists ()
	{
		return $this->fileExists;
	}


	/**
	 * Creates a new file. If the file already exists, errors out.
	 * If contents is not provided, creates empty file.
	 */
	public function create ($contents = null)
	{
		if ($this->fileExists === true) return null;

		if (empty($contents)) 
		{
			touch ($this->filename);
		}
		else 
		{
			file_put_contents($this->filename, $contents);
		}

		$this->fileExists = true;
		return $this;
	}



	/**
	 * Returns the entire contents of the file as text
	 */
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
	public function find ($needle)
	{
		if (empty($needle)) return null;

		$file = fopen($this->filename, "rb");
		$pos = 1;
		while ( ($buffer = fgets($file)) !== false) {
			if (stripos($buffer, $needle) !== false)
				return $pos;
			$pos = $pos + 1;
		}
		fclose($file);
		return null;
	}




	/**
	 * Inserts the given contents at the line number provided.
	 * Existing contents of the line are pushed below after the 
	 * new contents.
	 */
	public function insertAt ($lineNo, $contents)
	{
		if (empty($lineNo)) return null;

		//echo ("Line No:" . $lineNo);
		$f = fopen($this->filename, "r+b");

		// read lines with fgets() until you have reached the right one
		for ($i = 0; $i < $lineNo; $i++) fgets($f);
		$pos = ftell($f);                   // save current position
		$trailer = stream_get_contents($f); // read trailing data
		fseek($f, $pos);                    // go back
		ftruncate($f, $pos);                // truncate the file at current position
		fputs($f, $contents . "\n");        // add line
		fwrite($f, $trailer);               // restore trailing data

		fclose($f);

		return $this;
	}



	/**
	 * Replaces the contents of the given line number 
	 * with new content. If the new content is NULL,
	 * data at the provided line gets deleted.
	 */
	public function replace ($lineNo, $replaceWith = null)
	{
		if (empty($lineNo)) return null;

		$f = fopen($this->filename, "r+b");

		// read lines with fgets() until you have reached the right one
		for ($i = 0; $i < $lineNo - 1; $i++) fgets($f);
		$pos = ftell($f);                   // save this position
		fgets($f);							// skip the line to be deleted
		$trailer = stream_get_contents($f); // read trailing data
		fseek($f, $pos);                    // go back to the saved position
		ftruncate($f, $pos);                // truncate the file at the saved position
		if (! empty($replaceWith)) 
		{
			fputs($f, $replaceWith . "\n"); // add line
		}
		fwrite($f, $trailer);               // restore trailing data
		fclose($f);

		return $this;
	}
}