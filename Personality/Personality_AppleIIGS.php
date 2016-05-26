<?php
/**
 * Personality_AppleIIGS - Handle things specific to the Apple IIGS computer (for auto-documentation)
 */
class Personality_AppleIIGS
{
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
	 *
	 * @param $last_instruction str, $this_isntruction str
	 * @return mixed
	 */
	public function check( $last_instruction, $this_instruction )
	{
		$comment = false;

		// softswitches and 8-bit firmware
		$addr_part = substr( $this_instruction, -5 );
		if( $addr_part[0] == ' ' )
		{
			if( $this->nlist_ssf8rom[substr($addr_part,1)] )
			{
				$comment = $this->nlist_ssf8rom[substr($addr_part,1)];
			}
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