<?php
/**
 * Input_BIN - YAGSDisasm Input byte device for binary blobs
 *
 */
class Input_BIN
{
    // have we been initialized yet?
	private $is_initialized = false;

	// source filename
	private $source_filename;

	// volume length in bytes
	private $total_bytes;

	// our file descriptor
	private $fp;

	public function __construct( $filename )
	{
		$fp = fopen( $filename, 'r' );
		if( !$fp )
		{
			throw new Exception( 'Input_BIN: Unable to open source file '.$filename );
		}

		// source filename
		$this->source_filename = $filename;

		// bytes of disk data
		$this->total_bytes = filesize( $filename );

		$this->is_initialized = true;
		$this->fp = $fp;
	}

	/**
	 * getBytes( $starting_byte, $num_bytes ) - Retrieve $num_bytes bytes from the file, starting at $starting_byte
	 * @param $starting_byte int, $num_bytes int
	 * @return $data str
	 */
	public function getBytes( $starting_byte, $num_bytes )
	{
		if( $num_bytes == 'all' )
		{
			return file_get_contents( $this->source_filename );
		}

		fseek( $fp, $starting_byte, SEEK_SET );
		$data = '';
		$data .= fread( $fp, $num_bytes );

		return $data;
	}

	/**
	 * printInfo() - Print some information about this binary image
	 */
	public function printInfo()
	{
		echo "Source Filename: ".$this->source_filename."\n";
		echo "   Image Format: Binary\n";
		echo "    Total Bytes: ".$this->total_bytes."\n";
	}
}