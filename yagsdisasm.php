<?php
/*
 * YAGSDisasm - A 65C816 disassembler for Apple IIGS code and disk images
 *
 */
require_once( 'Input/Input_2MG.php' );
require_once( 'Input/Input_PO.php' );
require_once( 'Input/Input_BIN.php' );
require_once( 'Disassembler/Disassembler_65816.php' );
require_once( 'Personality/Personality_AppleIIGS.php' );

// version string
$version_str = '1.0a';

/*
 * begin options parsing here
 */
$options = getopt( 'p:e:x:m:f:s:n:o:Vvhk:' );

if( isset( $options['V'] ))
{
	echo "Version: {$version_str}\n";
	exit;
}

if( isset( $options['h'] ))
{
	echo <<<EOF
YAGSDisasm - A 65C816 disassembler for Apple IIGS code and disk images

usage: php yagsdisasm.php [options]
-p     specify machine personality (for auto commenting and formatting)
       iigs - Apple IIGS (this is presently the only target)
-e     value of E cpu flag (1 is emulation mode)
-x     value of X cpu flag (1 is short X/Y register)
-m     value of M cpu flag (1 is short accumulator)
-f     specifies input file (required)
-i     specifies format of input
       2mg - .2mg disk       (see -s, -n options)
	   po  - .po disk image  (see -s, -n options)
	   bin - binary blob     (see -s, -n options which will refer to bytes instead of blocks, default is to disassemble the whole thing)
	   default behavior is to guess by file extension
-s     starting disk block to disassemble (if input is binary blob, this is starting offset into file)
-n     number of disk blocks to disassemble (if input is binary blob, this is length to disassemble from file)
-k     skip this number of bytes from beginning when disassembling
-o     24-bit origin address, in hex (eg 002000)
       addresses shorter than 24 bits will be zero-filled
	   addresses longer than 24 bits will be truncated
-v     verbose/debugging info
-V     print version information and exit

EOF;
	exit;
}

// handle defaults and specification for filename and format
if( !isset( $options['f'] ))
{
	echo "Filename must be specified with -f\n";
	exit;
}

$filename = $options['f'];
$format = 'po';
$parts = explode( '.', $filename );
if( strtolower( $parts[count($parts)-1] ) == '2mg' )
{
	$format = '2mg';
}

if( isset( $options['i'] ))
{
	$known_type = false;
	if( $options['i'] == '2mg' )
	{
		$format = '2mg';
		$known_type = true;
	}
	if( $options['i'] == 'po' )
	{
		$format = 'po';
		$known_type = true;
	}
	if( $options['bin'] == 'bin' )
	{
		$format = 'bin';
		$known_type = true;
	}
	if( !$known_type )
	{
		echo "Unknown input image type: ".$options['i']."\n";
		exit;
	}
}

// handle defaults and specification for starting block and ending block
if( $format == 'bin' )
{
	$starting_block = 0;
	$num_blocks = 'all';
} else {
	$starting_block = 0;
	$num_blocks = 1;
}
if( isset( $options['s'] ))
{
	$starting_block = intval( $options['s'] );
}
if( isset( $options['n'] ))
{
	$num_blocks = intval( $options['n'] );
}

// handle defaults and specifications for M, X, E cpu flags
$m_flag = 1;
$x_flag = 1;
$e_flag = 1;
if( isset( $options['m'] ))
{
	$m_flag = intval( $options['m'] );
}
if( isset( $options['x'] ))
{
	$x_flag = intval( $options['x'] );
}
if( isset( $options['e'] ))
{
	$e_flag = intval( $options['e'] );
}

// handle verbosity
$verbose = false;
if( isset( $options['v'] ))
{
	$verbose = true;
}

// handle origin address
$origin = 0x1000;
if( isset( $options['o'] ))
{
	$origin = hexdec( $options['o'] );
}

// handle skip amount
$skip = 0;
if( isset( $options['k'] ))
{
	$skip = $options['k'];
}

/*
 * begin main operations here
 */
switch( $format )
{
	case 'bin': $fh = new Input_BIN( $filename );
				$data = $fh->getBlocks( $starting_block, $num_blocks );
				break;
	case '2mg': $fh = new Input_2MG( $filename );
				$data = $fh->getBlocks( $starting_block, $num_blocks );
				break;
	case 'po':  $fh = new Input_PO( $filename );
				$data = $fh->getBytes( $starting_block, $num_blocks );
				break;
}
if( $verbose )
{
	echo "Processed {$format} file {$filename}\n";
	$fh->printInfo();
}

if( $skip > 0 )
{
	$data = substr( $data, $skip );
}

$pers = new Personality_AppleIIGS;

$dh = new Disassembler_65816;
$dh->disassemble( $m_flag, $x_flag, $e_flag, $origin, $data, $pers );
