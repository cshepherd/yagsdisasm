<?php
/**
 * Input_2MG - YAGSDisasm Input block device for .2MG image files
 *   from http://apple2.org.za/gswv/a2zine/Docs/DiskImage_2MG_Info.txt
 *
 */
class Input_2MG
{
    // have we been initialized yet?
	private $is_initialized = false;

	// source filename
	private $source_filename;

	// creator_id
	private $creator_id;

	// 2mg comment
	private $comment = '';

	// number of 512-byte blocks in image
	private $total_blocks;

	// header length
	private $header_length;

	// version number
	private $version_number;

	// image format
	private $image_format;

	// disk is locked?
	private $is_locked=false;

	// offset to image data
	private $image_offset;

	// DOS 3.3 Volume Number
	private $volume_number;

	// volume length in bytes
	private $total_bytes;

	// offset to comment
	private $comment_offset;

	// length of comment
	private $comment_length;

	// 2MG Creator data offset
	private $creator_data_offset;

	// 2MG Creator data length
	private $creator_data_length;

	// our file descriptor
	private $fp;

	public function __construct( $filename )
	{
		$fp = fopen( $filename, 'r' );
		if( !$fp )
		{
			throw new Exception( 'Input_2MG: Unable to open source file '.$filename );
		}

		$this->source_filename = $filename;

		// check for 2MG magic
		$magic = fread( $fp, 4 );
		if( $magic != '2IMG' )
		{
			throw new Exception( 'Input_2MG: source file not a 2MG file' );
		}

		// save creator id
		$this->creator_id = fread( $fp, 4 );

		// get header length (little-endian)
		$byte1 = fread( $fp, 1 );
		$byte2 = fread( $fp, 1 );
		$this->header_length = (ord( $byte2 )*256) + ord( $byte1 );

		// get version number (little-endian)
		$byte1 = fread( $fp, 1 );
		$byte2 = fread( $fp, 1 );
		$this->version_number = (ord( $byte2 )*256) + ord( $byte1 );

		// get image format (little-endian)
		$byte1 = fread( $fp, 1 );
		$byte2 = fread( $fp, 1 );
		$byte3 = fread( $fp, 1 );
		$byte4 = fread( $fp, 1 );
		$image_format_id = (ord( $byte4 )*16777216) + (ord( $byte3 )*65536) + (ord( $byte2 )*256) + ord( $byte1 );
		$known_format_ids = array(
			0	=> 'DOS 3.3',
			1	=> 'ProDOS',
			2	=> 'NIB',
		);
		$this->image_format = $known_format_ids[$image_format_id];

		// flags / volume number (little-endian)
		$byte1 = fread( $fp, 1 );
		$byte2 = fread( $fp, 1 );
		$byte3 = fread( $fp, 1 );
		$byte4 = fread( $fp, 1 );
		if( ord( $byte2 ) && 0x01 )
		{
			$this->volume_number = ord( $byte1 );
		} else {
			if( $image_format_id == 1 )
			{
				$this->volume_number = 254;
			}
		}
		if( ord( $byte4 ) && 0x80 )
		{
			$this->is_locked = true;
		} else {
			$this->is_locked = false;
		}

		// block count (little-endian)
		$byte1 = fread( $fp, 1 );
		$byte2 = fread( $fp, 1 );
		$byte3 = fread( $fp, 1 );
		$byte4 = fread( $fp, 1 );
		$this->total_blocks = (ord( $byte4 )*16777216) + (ord( $byte3 )*65536) + (ord( $byte2 )*256) + ord( $byte1 );

		// offset to image data (little-endian)
		$byte1 = fread( $fp, 1 );
		$byte2 = fread( $fp, 1 );
		$byte3 = fread( $fp, 1 );
		$byte4 = fread( $fp, 1 );
		$this->image_offset = (ord( $byte4 )*16777216) + (ord( $byte3 )*65536) + (ord( $byte2 )*256) + ord( $byte1 );

		// bytes of disk data (little-endian)
		$byte1 = fread( $fp, 1 );
		$byte2 = fread( $fp, 1 );
		$byte3 = fread( $fp, 1 );
		$byte4 = fread( $fp, 1 );
		$this->total_bytes = (ord( $byte4 )*16777216) + (ord( $byte3 )*65536) + (ord( $byte2 )*256) + ord( $byte1 );

		// offset to comment (little-endian)
		$byte1 = fread( $fp, 1 );
		$byte2 = fread( $fp, 1 );
		$byte3 = fread( $fp, 1 );
		$byte4 = fread( $fp, 1 );
		$this->comment_offset = (ord( $byte4 )*16777216) + (ord( $byte3 )*65536) + (ord( $byte2 )*256) + ord( $byte1 );

		// comment length (little-endian)
		$byte1 = fread( $fp, 1 );
		$byte2 = fread( $fp, 1 );
		$byte3 = fread( $fp, 1 );
		$byte4 = fread( $fp, 1 );
		$this->comment_length = (ord( $byte4 )*16777216) + (ord( $byte3 )*65536) + (ord( $byte2 )*256) + ord( $byte1 );

		// creator data offset (little-endian)
		$byte1 = fread( $fp, 1 );
		$byte2 = fread( $fp, 1 );
		$byte3 = fread( $fp, 1 );
		$byte4 = fread( $fp, 1 );
		$this->creator_data_offset = (ord( $byte4 )*16777216) + (ord( $byte3 )*65536) + (ord( $byte2 )*256) + ord( $byte1 );

		// creator data length (little-endian)
		$byte1 = fread( $fp, 1 );
		$byte2 = fread( $fp, 1 );
		$byte3 = fread( $fp, 1 );
		$byte4 = fread( $fp, 1 );
		$this->creator_data_length = (ord( $byte4 )*16777216) + (ord( $byte3 )*65536) + (ord( $byte2 )*256) + ord( $byte1 );

		// reserved bytes
		$reserved = fread( $fp, 8 );

		// get Comment string if present
		if( $this->comment_length > 0 )
		{
			fseek( $fp, $this->comment_offset, SEEK_SET );
			$this->comment = fread( $fp, $this->comment_length );
		}

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
		fseek( $this->fp, $this->image_offset, SEEK_SET );
		fseek( $this->fp, $starting_block*512, SEEK_CUR );
		$data = '';
		for( $blk=0;$blk<$num_blocks;$blk++ )
		{
			$data .= fread( $this->fp, 512 );
		}

		return $data;
	}

	/**
	 * printInfo() - Print some information about this 2MG image
	 */
	public function printInfo()
	{
		echo "Source Filename: ".$this->source_filename."\n";
		echo "     Creator ID: ".$this->creator_id."\n";
		echo "  Header Length: ".$this->header_length."\n";
		echo " Version Number: ".$this->version_number."\n";
		echo "   Image Format: ".$this->image_format."\n";
		if( $this->image_format == 'DOS 3.3' )
		{
			echo "  Volume Number: ".$this->volume_number."\n";
		}
		echo "      Is Locked? ";
		if( $this->is_locked )
		{
			echo "Yes\n";
		} else {
			echo "No\n";
		}
		echo "    Block Count: ".$this->total_blocks."\n";
		echo "    Total Bytes: ".$this->total_bytes."\n";
		if( $this->comment != '' )
		{
			echo "        Comment: ".$this->comment."\n";
		}
	}
}