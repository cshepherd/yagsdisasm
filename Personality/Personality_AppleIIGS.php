<?php
/**
 * Personality_AppleIIGS - Handle things specific to the Apple IIGS computer (for auto-documentation)
 */
class Personality_AppleIIGS
{
	private $inside_prodos = 0;
	private $inside_prodos16 = 0;
	private $inside_smartport = 0;

	/**
	 * getlines( $fp, $numlines ) - Parse some lines out of the NiftyList file
	 *
	 * @param $fp res, $numlines int
	 * @return array
	 */
	private function getlines( $fp, $numlines )
	{
		$retarr = array();

		for($x=0;$x<$numlines;$x++)
		{
			$line = trim( fgets( $fp ));
			$addr = substr( $line, 0, 4 );
			$data = substr( $line, 5 );
			$retarr[$addr] = $data;
		}

		return $retarr;
	}

	public function __construct()
	{
		$fp = fopen( 'NList.Data.TXT', 'r' );
		if( !$fp )
		{
			throw new Exception( "Personality_AppleIIGS: Couldn't open NiftyList data file" );
		}

		// parse some stuff out of the NiftyList data file
		$throwaway  = $this->getlines( $fp, 4 );
		$this->nlist_prodos8   = $this->getlines( $fp, 33-5+1 );
		$throwaway  = $this->getlines( $fp, 1 );
		$this->nlist_prodos16  = $this->getlines( $fp, 151-35+1 );
		$throwaway  = $this->getlines( $fp, 1 );
		$this->nlist_toolbox   = $this->getlines( $fp, 1544-153+1 );
		$throwaway  = $this->getlines( $fp, 1 );
		$this->nlist_e1xxxx    = $this->getlines( $fp, 1620-1546+1 );
		$throwaway  = $this->getlines( $fp, 1 );
		$this->nlist_e0xxxx    = $this->getlines( $fp, 1637-1622+1 );
		$throwaway  = $this->getlines( $fp, 1 );
		$this->nlist_ssf8rom   = $this->getlines( $fp, 1911-1639+1 );
	}

	/**
	 * check( $last_instruction, $this_instruction ) - Check if something comment-worthy has occurred
	 *   (after instruction decoding)
	 *
	 * @param $last_instruction str, $this_isntruction str
	 * @return mixed
	 */
	public function check( $last_instruction, $this_instruction, $disasm )
	{
		$comment = false;

		// m/x/e flags
		if( $this_instruction == 'XCE' )
		{
			if( $last_instruction == 'CLC' )
			{
				$disasm->clear_e_flag();
			}
			if( $last_instruction == 'SEC' )
			{
				$disasm->set_e_flag();
			}
		}
		$opcode = substr( $this_instruction, 0, 3 );
		if( in_array( $opcode, array( 'REP', 'SEP' )))
		{
			$cpu_flags = substr( $this_instruction, -2 );
			// 0x10 - index flags (X status register)
			if( $cpu_flags && 0x10 )
			{
				if( $opcode == 'REP' )
				{
					$disasm->clear_x_flag();
				} else {
					$disasm->set_x_flag();
				}
			}
			// 0x40 - accumulator (M status register)
			if( $cpu_flags && 0x40 )
			{
				if( $opcode == 'REP' )
				{
					$disasm->clear_m_flag();
				} else {
					$disasm->set_m_flag();
				}
			}
		}

		// SmartPort
		if( $this->inside_smartport > 0 )
		{
			if( $this->inside_smartport == 1 )
			{
				$comment = 'Pointer to parameters';
				$this->inside_smartport -= 1;
			} else {
				$call_number = substr( $this_instruction, -2 );
				$comment = 'Call Number';
				// TODO extended smartport call support is broke
				if( substr( $call_number, 0, 1 ) == '4' )
				{
					// extended smartport call: address param is long
					$disasm->do_raw_bytes( array(4) );
				} else {
					// non-extended smartport call: address param is 16 bits
					$disasm->do_raw_bytes( array(2) );
				}
				$this->inside_smartport -= 1;				
			}
		}
		if( in_array( $this_instruction, array( 'JSR C50D', 'JSR C50A', 'JSR C70D', 'JSR C70A' )))
		{
			$this->inside_smartport = 2;
			$disasm->do_raw_bytes( array(1,2) );
			$comment = 'SmartPort Entry';
		}

		// ProDOS 8
		if( $this->inside_prodos > 0 )
		{
			if( $this->inside_prodos == 1 )
			{
				$comment = 'Pointer to parameters';
				$this->inside_prodos -= 1;
			} else {
				$call_number = substr( $this_instruction, -2 );
				$comment = $this->nlist_prodos8[$call_number];
				$this->inside_prodos -= 1;				
			}
		}
		if( $this_instruction == 'JSR BF00' )
		{
			$this->inside_prodos = 2;
			$disasm->do_raw_bytes( array(1,2) );
			$comment = 'ProDOS 8 MLI';
		}

		// ProDOS 16 / GS/OS inline
		if( $this->inside_gsos > 0 )
		{
			if( $this->inside_gsos == 1 )
			{
				$comment = 'Pointer to parameters';
				$this->inside_gsos -= 1;
			} else {
				$call_number = substr( $this_instruction, -4 );
				$comment = $this->nlist_prodos16[$call_number];
				$this->inside_gsos -= 1;				
			}
		}
		if( $this_instruction == 'JSL E100A8' )
		{
			$this->inside_gsos = 2;
			$disasm->do_raw_bytes( array(2,4) );
			$comment = 'GS/OS In-Line MLI';
		}

		// softswitches and 8-bit firmware
		$addr_part = substr( $this_instruction, -5 );
		if( $addr_part[0] == ' ' )
		{
			if( $this->nlist_ssf8rom[substr($addr_part,1)] )
			{
				$comment = $this->nlist_ssf8rom[substr($addr_part,1)];
			}
		}

		// E1xxxx vectors
		$parts = explode( ' ', $this_instruction );
		if(( $parts[1] ) && ( strlen( $parts[1] ) == 6 ) && ( substr( $parts[1], 0, 2 ) == 'E1' ))
		{
			$vector = substr( $parts[1], -4 );
			$comment = $this->nlist_e1xxxx[$vector];
		}
		
		// E0xxxx vectors
		if(( $parts[1] ) && ( strlen( $parts[1] ) == 6 ) && ( substr( $parts[1], 0, 2 ) == 'E0' ))
		{
			$vector = substr( $parts[1], -4 );
			$comment = $this->nlist_e0xxxx[$vector];
		}

		// toolbox entry
		if( $this_instruction == 'JSL E10000' )
		{
			if( substr( $last_instruction, 0, 3 ) == 'LDX' )
			{
				$toolcall_number = substr( $last_instruction, -4 );

				if( $this->nlist_toolbox[$toolcall_number] )
				{
					$comment = 'Toolbox: '.$this->nlist_toolbox[$toolcall_number];
				}
			}
		}

		return $comment;
	}
}