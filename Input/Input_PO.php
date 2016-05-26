<?php
/**
 * Input_PO - YAGSDisasm Input block device for .PO image files
 *
 */
class Input_PO
{
    // have we been initialized yet?
	private $is_initialized = false;

	// source filename
	private $source_filename;

	// number of 512-byte blocks in image
	private $total_blocks;

	// volume length in bytes
	private $total_bytes;

	// our file descriptor
	private $fp;

	public function __construct( $filename )
	{
		$fp = fopen( $filename, 'r' );
		if( !$fp )
		{
			throw new Exception( 'Input_PO: Unable to open source file '.$filename );
		}

		// source filename
		$this->source_filename = $filename;

		// bytes of disk data
		$this->total_bytes = filesize( $filename );

		// block count (little-endian)
		$this->total_blocks = $this->total_bytes / 512;

		$this->is_initialized = true;
		$this->fp = $fp;
	}

	/**
	 * getBlocks( $starting_block, $num_blocks ) - Retrieve $num_blocks 512-byte blocks from the file, starting at $starting_block
	 * @param $starting_block int, $num_blocks int
	 * @return $data str
	 */
	public function getBlocks( $starting_block, $num_blocks )
	{
		fseek( $fp, $this->image_offset, SEEK_SET );
		fseek( $fp, $starting_block*512 );
		$data = '';
		for( $blk=0;$blk<$num_blocks;$blk++ )
		{
			$data .= fread( $fp, 512 );
		}

		return $data;
	}

	/**
	 * printInfo() - Print some information about this PO image
	 */
	public function printInfo()
	{
		echo "Source Filename: ".$this->source_filename."\n";
		echo "   Image Format: ProDOS\n";
		echo "    Block Count: ".$this->total_blocks."\n";
		echo "    Total Bytes: ".$this->total_bytes."\n";
	}
}