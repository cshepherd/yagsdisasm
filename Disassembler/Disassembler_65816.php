<?php
/**
 * Disassembler_65816 - A 65816 disassembler, of course
 * loosely references Andy McFadden's Ciderpress disassembler work at
 *   https://github.com/fadden/ciderpress/blob/master/reformat/DisasmTable.cpp
 */
class Disassembler_65816
{
	/**
	 * 65816 X status register
	 */
	private $x_flag;

	/**
	 * 65816 M status register
	 */
	private $m_flag;

	/**
	 * 65816 emulation mode flag
	 */
	private $e_flag;

	/**
	 * program counter
	 */
	private $pc;

	/**
	 * array of byte groups to not disassemble
	 */
	private $bytes_queue = array();

	/**
	 * set_x_flag() - set 65816 'X' processor status flag
	 *
	 */
	public function set_x_flag()
	{
		$this->x_flag = 1;
	}

	/**
	 * clear_x_flag() - clear 65816 'X' processor status flag
	 *
	 */
	public function clear_x_flag()
	{
		$this->x_flag = 0;
	}

	/**
	 * set_m_flag() - set 65816 'M' processor status flag
	 *
	 */
	public function set_m_flag()
	{
		$this->m_flag = 1;
	}

	/**
	 * clear_m_flag() - clear 65816 'M' processor status flag
	 *
	 */
	public function clear_m_flag()
	{
		$this->m_flag = 0;
	}

	/**
	 * set_e_flag() - set 65816 'E' processor status flag
	 *
	 */
	public function set_e_flag()
	{
		$this->e_flag = 1;
	}

	/**
	 * clear_e_flag() - clear 65816 'E' processor status flag
	 *
	 */
	public function clear_e_flag()
	{
		$this->e_flag = 0;
	}

	/**
	 * raw_bytes() - personality tells us not to disassemble the next $num_bytes bytes
	 */
	public function do_raw_bytes( $bytes_queue )
	{
		$this->bytes_queue = $bytes_queue;
	}

	/**
	 * initialization here
	 */
	public function __construct()
	{
		$this->opcodes = array(
			0x00 => array( 'BRK', 'StackInt' ),
			0x01 => array( 'ORA', 'DPIndexXInd' ),
			0x02 => array( 'COP', 'StackInt' ),
			0x03 => array( 'ORA', 'StackRel' ),
			0x04 => array( 'TSB', 'DP' ),
			0x05 => array( 'ORA', 'DP' ),
			0x06 => array( 'ASL', 'DP' ),
			0x07 => array( 'ORA', 'DPIndLong' ),
			0x08 => array( 'PHP', 'StackPush' ),
			0x09 => array( 'ORA', 'Imm' ),
			0x0A => array( 'ASL', 'Acc' ),
			0x0B => array( 'PHD', 'StackPush' ),
			0x0C => array( 'TSB', 'Abs' ),
			0x0D => array( 'ORA', 'Abs' ),
			0x0E => array( 'ASL', 'Abs' ),
			0x0F => array( 'ORA', 'AbsLong' ),

			0x10 => array( 'BPL', 'PCRel' ),
			0x11 => array( 'ORA', 'DPIndIndexY' ),
			0x12 => array( 'ORA', 'DPInd' ),
			0x13 => array( 'ORA', 'StackRelIndexY' ),
			0x14 => array( 'TRB', 'DP' ),
			0x15 => array( 'ORA', 'DPIndexX' ),
			0x16 => array( 'ASL', 'DPIndexX' ),
			0x17 => array( 'ORA', 'DPIndexYLong' ),
			0x18 => array( 'CLC', 'Implied' ),
			0x19 => array( 'ORA', 'AbsIndexY' ),
			0x1A => array( 'INC', 'Acc' ),
			0x1B => array( 'TCS', 'Implied' ),
			0x1C => array( 'TRB', 'Abs' ),
			0x1D => array( 'ORA', 'AbsIndexX' ),
			0x1E => array( 'ASL', 'AbsIndexX' ),
			0x1F => array( 'ORA', 'AbsIndexXLong' ),

			0x20 => array( 'JSR', 'Abs' ),
			0x21 => array( 'AND', 'DPIndexXind' ),
			0x22 => array( 'JSL', 'AbsLong' ),
			0x23 => array( 'AND', 'StackRel' ),
			0x24 => array( 'BIT', 'DP' ),
			0x25 => array( 'AND', 'DP' ),
			0x26 => array( 'ROL', 'DP' ),
			0x27 => array( 'AND', 'DPIndLong' ),
			0x28 => array( 'PLP', 'StackPull' ),
			0x29 => array( 'AND', 'Imm' ),
			0x2A => array( 'ROL', 'Acc' ),
			0x2B => array( 'PLD', 'StackPull' ),
			0x2C => array( 'BIT', 'Abs' ),
			0x2D => array( 'AND', 'Abs' ),
			0x2E => array( 'ROL', 'Abs' ),
			0x2F => array( 'AND', 'AbsLong' ),

			0x30 => array( 'BMI', 'PCRel' ),
			0x31 => array( 'AND', 'DPIndIndexY' ),
			0x32 => array( 'AND', 'DPInd' ),
			0x33 => array( 'AND', 'StackRelIndexY' ),
			0x34 => array( 'BIT', 'DPIndexX' ),
			0x35 => array( 'AND', 'DPIndexX' ),
			0x36 => array( 'ROL', 'DPIndexX' ),
			0x37 => array( 'AND', 'DPIndIndexYLong' ),
			0x38 => array( 'SEC', 'Implied' ),
			0x39 => array( 'AND', 'AbsIndexY' ),
			0x3A => array( 'DEC', 'Acc' ),
			0x3B => array( 'TSC', 'Implied' ),
			0x3C => array( 'BIT', 'AbsIndexX' ),
			0x3D => array( 'AND', 'AbsIndexX' ),
			0x3E => array( 'ROL', 'AbsIndexX' ),
			0x3F => array( 'AND', 'AbsIndexXLong' ),

			0x40 => array( 'RTI', 'StackRTI' ),
			0x41 => array( 'EOR', 'DPIndexXind' ),
			0x42 => array( 'WDM', 'WDM' ),
			0x43 => array( 'EOR', 'StackRel' ),
			0x44 => array( 'MVP', 'BlockMove' ),
			0x45 => array( 'EOR', 'DP' ),
			0x46 => array( 'LSR', 'DP' ),
			0x47 => array( 'EOR', 'DPIndLong' ),
			0x48 => array( 'PHA', 'StackPush' ),
			0x49 => array( 'EOR', 'Imm' ),
			0x4A => array( 'LSR', 'Acc' ),
			0x4B => array( 'PHK', 'StackPush' ),
			0x4C => array( 'JMP', 'Abs' ),
			0x4D => array( 'EOR', 'Abs' ),
			0x4E => array( 'LSR', 'Abs' ),
			0x4F => array( 'EOR', 'AbsLong' ),

			0x50 => array( 'BVC', 'PCRel' ),
			0x51 => array( 'EOR', 'DPIndIndexY' ),
			0x52 => array( 'EOR', 'DPInd' ),
			0x53 => array( 'EOR', 'StackRelIndexY' ),
			0x54 => array( 'MVN', 'BlockMove' ),
			0x55 => array( 'EOR', 'DPIndexX' ),
			0x56 => array( 'LSR', 'DPIndexX' ),
			0x57 => array( 'EOR', 'DPIndIndexYLong' ),
			0x58 => array( 'CLI', 'Implied' ),
			0x59 => array( 'EOR', 'AbsIndexY' ),
			0x5A => array( 'PHY', 'StackPush' ),
			0x5B => array( 'TCD', 'Implied' ),
			0x5C => array( 'JMP', 'AbsLong' ),
			0x5D => array( 'EOR', 'AbsIndexX' ),
			0x5E => array( 'LSR', 'AbsIndexX' ),
			0x5F => array( 'EOR', 'AbsIndexXLong' ),

			0x60 => array( 'RTS', 'StackRTS' ),
			0x61 => array( 'ADC', 'DPIndexXInd' ),
			0x62 => array( 'PER', 'StackPCRel' ),
			0x63 => array( 'ADC', 'StackRel' ),
			0x64 => array( 'STZ', 'DP' ),
			0x65 => array( 'ADC', 'DP' ),
			0x66 => array( 'ROR', 'DP' ),
			0x67 => array( 'ADC', 'DPIndLong' ),
			0x68 => array( 'PLA', 'StackPull' ),
			0x69 => array( 'ADC', 'Imm' ),
			0x6A => array( 'ROR', 'Acc' ),
			0x6B => array( 'RTL', 'StackRTL' ),
			0x6C => array( 'JMP', 'AbsInd' ),
			0x6D => array( 'ADC', 'Abs' ),
			0x6E => array( 'ROR', 'Abs' ),
			0x6F => array( 'ADC', 'AbsLong' ),

			0x70 => array( 'BVS', 'PCRel' ),
			0x71 => array( 'ADC', 'DPIndIndexY' ),
			0x72 => array( 'ADC', 'DPInd' ),
			0x73 => array( 'ADC', 'StackRelIndexY' ),
			0x74 => array( 'STZ', 'DPIndexX' ),
			0x75 => array( 'ADC', 'DPIndexX' ),
			0x76 => array( 'ROR', 'DPIndexX' ),
			0x77 => array( 'ADC', 'DPIndIndexYLong' ),
			0x78 => array( 'SEI', 'Implied' ),
			0x79 => array( 'ADC', 'AbsIndexY' ),
			0x7A => array( 'PLY', 'StackPull' ),
			0x7B => array( 'TDC', 'Implied' ),
			0x7C => array( 'JMP', 'AbsIndexXInd' ),
			0x7D => array( 'ADC', 'AbsIndexX' ),
			0x7E => array( 'ROR', 'AbsIndexX' ),
			0x7F => array( 'ADC', 'AbsIndexXLong' ),

			0x80 => array( 'BRA', 'PCRel' ),
			0x81 => array( 'STA', 'DPIndexXind' ),
			0x82 => array( 'BRL', 'PCRelLong' ),
			0x83 => array( 'STA', 'StackRel' ),
			0x84 => array( 'STY', 'DP' ),
			0x85 => array( 'STA', 'DP' ),
			0x86 => array( 'STX', 'DP' ),
			0x87 => array( 'STA', 'DPIndLong' ),
			0x88 => array( 'DEY', 'Implied' ),
			0x89 => array( 'BIT', 'Imm' ),
			0x8A => array( 'TXA', 'Implied' ),
			0x8B => array( 'PHB', 'StackPush' ),
			0x8C => array( 'STY', 'Abs' ),
			0x8D => array( 'STA', 'Abs' ),
			0x8E => array( 'STX', 'Abs' ),
			0x8F => array( 'STA', 'AbsLong' ),

			0x90 => array( 'BCC', 'PCRel' ),
			0x91 => array( 'STA', 'DPIndIndexY' ),
			0x92 => array( 'STA', 'DPInd' ),
			0x93 => array( 'STA', 'StackRelIndexY' ),
			0x94 => array( 'STY', 'DPIndexX' ),
			0x95 => array( 'STA', 'DPIndexX' ),
			0x96 => array( 'STX', 'DPIndexY' ),
			0x97 => array( 'STA', 'DPIndIndexYLong' ),
			0x98 => array( 'TYA', 'Implied' ),
			0x99 => array( 'STA', 'AbsIndexY' ),
			0x9A => array( 'TXS', 'Implied' ),
			0x9B => array( 'TXY', 'Implied' ),
			0x9C => array( 'STZ', 'Abs' ),
			0x9D => array( 'STA', 'AbsIndexX' ),
			0x9E => array( 'STZ', 'AbsIndexX' ),
			0x9F => array( 'STA', 'AbsIndexXLong' ),

			0xA0 => array( 'LDY', 'Imm' ),
			0xA1 => array( 'LDA', 'DPIndexXInd' ),
			0xA2 => array( 'LDX', 'Imm' ),
			0xA3 => array( 'LDA', 'StackRel' ),
			0xA4 => array( 'LDY', 'DP' ),
			0xA5 => array( 'LDA', 'DP' ),
			0xA6 => array( 'LDX', 'DP' ),
			0xA7 => array( 'LDA', 'DPIndLong' ),
			0xA8 => array( 'TAY', 'Implied' ),
			0xA9 => array( 'LDA', 'Imm' ),
			0xAA => array( 'TAX', 'Implied' ),
			0xAB => array( 'PLB', 'StackPull' ),
			0xAC => array( 'LDY', 'Abs' ),
			0xAD => array( 'LDA', 'Abs' ),
			0xAE => array( 'LDX', 'Abs' ),
			0xAF => array( 'LDA', 'AbsLong' ),

			0xB0 => array( 'BCS', 'PCRel' ),
			0xB1 => array( 'LDA', 'DPIndIndexY' ),
			0xB2 => array( 'LDA', 'DPInd' ),
			0xB3 => array( 'LDA', 'StackRelIndexY' ),
			0xB4 => array( 'LDY', 'DPIndexX' ),
			0xB5 => array( 'LDA', 'DPIndexX' ),
			0xB6 => array( 'LDX', 'DPIndexY' ),
			0xB7 => array( 'LDA', 'DPIndIndexYLong' ),
			0xB8 => array( 'CLV', 'Implied' ),
			0xB9 => array( 'LDA', 'AbsIndexY' ),
			0xBA => array( 'TSX', 'Implied' ),
			0xBB => array( 'TYX', 'Implied' ),
			0xBC => array( 'LDY', 'AbsIndexX' ),
			0xBD => array( 'LDA', 'AbsIndexX' ),
			0xBE => array( 'LDX', 'AbsIndexY' ),
			0xBF => array( 'LDA', 'AbsIndexXLong' ),

			0xC0 => array( 'CPY', 'Imm' ),
			0xC1 => array( 'CMP', 'DPIndexXInd' ),
			0xC2 => array( 'REP', 'Imm' ),
			0xC3 => array( 'CMP', 'StackRel' ),
			0xC4 => array( 'CPY', 'DP' ),
			0xC5 => array( 'CMP', 'DP' ),
			0xC6 => array( 'DEC', 'DP' ),
			0xC7 => array( 'CMP', 'DPIndLong' ),
			0xC8 => array( 'INY', 'Implied' ),
			0xC9 => array( 'CMP', 'Imm' ),
			0xCA => array( 'DEX', 'Implied' ),
			0xCB => array( 'WAI', 'Implied' ),
			0xCC => array( 'CPY', 'Abs' ),
			0xCD => array( 'CMP', 'Abs' ),
			0xCE => array( 'DEC', 'Abs' ),
			0xCF => array( 'CMP', 'AbsLong' ),

			0xD0 => array( 'BNE', 'PCRel' ),
			0xD1 => array( 'CMP', 'DPIndIndexY' ),
			0xD2 => array( 'CMP', 'DPInd' ),
			0xD3 => array( 'CMP', 'StackRelIndexY' ),
			0xD4 => array( 'PEI', 'StackDPInd' ),
			0xD5 => array( 'CMP', 'DPIndexX' ),
			0xD6 => array( 'DEC', 'DPIndexX' ),
			0xD7 => array( 'CMP', 'DPIndIndexYLong' ),
			0xD8 => array( 'CLD', 'Implied' ),
			0xD9 => array( 'CMP', 'AbsIndexY' ),
			0xDA => array( 'PHX', 'StackPush' ),
			0xDB => array( 'STP', 'Implied' ),
			0xDC => array( 'JML', 'AbsIndLong' ),
			0xDD => array( 'CMP', 'AbsIndexX' ),
			0xDE => array( 'DEC', 'AbsIndexX' ),
			0xDF => array( 'CMP', 'AbsIndexXLong' ),

			0xE0 => array( 'CPX', 'Imm' ),
			0xE1 => array( 'SBC', 'DPIndexXInd' ),
			0xE2 => array( 'SEP', 'Imm' ),
			0xE3 => array( 'SBC', 'StackRel' ),
			0xE4 => array( 'CPX', 'DP' ),
			0xE5 => array( 'SBC', 'DP' ),
			0xE6 => array( 'INC', 'DP' ),
			0xE7 => array( 'SBC', 'DPIndLong' ),
			0xE8 => array( 'INX', 'Implied' ),
			0xE9 => array( 'SBC', 'Imm' ),
			0xEA => array( 'NOP', 'Implied' ),
			0xEB => array( 'XBA', 'Implied' ),
			0xEC => array( 'CPX', 'Abs' ),
			0xED => array( 'SBC', 'Abs' ),
			0xEE => array( 'INC', 'Abs' ),
			0xEF => array( 'SBC', 'AbsLong' ),

			0xF0 => array( 'BEQ', 'PCRel' ),
			0xF1 => array( 'SBC', 'DPIndIndexY' ),
			0xF2 => array( 'SBC', 'DPInd' ),
			0xF3 => array( 'SBC', 'StackRelIndexY' ),
			0xF4 => array( 'PEA', 'StackAbs' ),
			0xF5 => array( 'SBC', 'DPIndexX' ),
			0xF6 => array( 'INC', 'DPIndexX' ),
			0xF7 => array( 'SBC', 'DPIndIndexYLong' ),
			0xF8 => array( 'SED', 'Implied' ),
			0xF9 => array( 'SBC', 'AbsIndexY' ),
			0xFA => array( 'PLX', 'StackPull' ),
			0xFB => array( 'XCE', 'Implied' ),
			0xFC => array( 'JSR', 'AbsIndexXInd' ),
			0xFD => array( 'SBC', 'AbsIndexX' ),
			0xFE => array( 'INC', 'AbsIndexX' ),
			0xFF => array( 'SBC', 'AbsIndexXLong' ),
		);
	}

	/**
	 * rel_offset( $offset ) - compute absolute offset vs program counter for a branch offset
	 *
	 * @param $offset int
	 */
	private function rel_offset( $offset, $offset2 )
	{
		if( $offset > 127 )
		{
			$result = $this->pc - (256-$offset);
		} else {
			$result = $this->pc + $offset;
		}

		$result += $offset2+1;

		return $result;
	}

	/**
	 * rel_long_offset( $offset, $offset2 ) - compute absolute offset vs program counter for a branch offset
	 *
	 * @param $offset int, $offset2 int
	 */
	private function rel_long_offset( $offset, $offset2 )
	{
		if( $offset > 32767 )
		{
			$result = $this->pc - (65536-$offset);
		} else {
			$result = $this->pc + $offset;
		}

		$result += $offset2+1;

		return $result;
	}

	/**
	 * print_instruction( $opcode_txt, $opcode, $mode, $arg_bytes ) - Output text-friendly disassembled output for one instruction
	 *
	 * @param $opcode_txt str, $opcode int, $mode str, $arg_bytes str
	 */
	public function print_instruction( $opcode_txt, $opcode, $mode, $arg_bytes )
	{
		// first print the program counter and hex bytes
		switch( strlen( $arg_bytes ))
		{
			case 0:
				$output = sprintf( '%06X-   %02X          ', $this->pc, $opcode );
				break;
			case 1:
				$output = sprintf( '%06X-   %02X %02X       ', $this->pc, $opcode, ord($arg_bytes[0]) );
				break;
			case 2:
				$output = sprintf( '%06X-   %02X %02X %02X    ', $this->pc, $opcode, ord($arg_bytes[0]), ord($arg_bytes[1]) );
				break;
			case 3:
				$output = sprintf( '%06X-   %02X %02X %02X %02X ', $this->pc, $opcode, ord($arg_bytes[0]), ord($arg_bytes[1]), ord($arg_bytes[2]) );
				break;
		}

		// now the opcode
		$output .= $opcode_txt;

		// now the args depending on mode
		switch( $mode )
		{
			case 'Implied':
			case 'StackPull':
			case 'StackPush':
			case 'StackRTI':
			case 'StackRTS':
			case 'Acc':
				break;
			case 'DP':
				$output .= sprintf( ' %02X', ord($arg_bytes[0] ));
				break;
			case 'DPIndexX':
				$output .= sprintf( ' %02X,X', ord($arg_bytes[0] ));
				break;
			case 'DPIndexY':
				$output .= sprintf( ' %02X,Y', ord($arg_bytes[0] ));
				break;
			case 'DPIndexXInd':
				$output .= sprintf( ' (%02X,X)', ord($arg_bytes[0] ));
				break;
			case 'DPInd':
				$output .= sprintf( ' (%02X)', ord($arg_bytes[0] ));
				break;
			case 'DPIndIndexY':
				$output .= sprintf( ' (%02X),Y', ord($arg_bytes[0] ));
				break;
			case 'Imm':
				if( strlen( $arg_bytes ) == 2 )
				{
					$output .= sprintf( ' #%02X%02X', ord($arg_bytes[1]), ord($arg_bytes[0]));					
				} else {
					$output .= sprintf( ' #%02X', ord($arg_bytes[0] ));
				}
				break;	
			case 'PCRel':
				if( ord($arg_bytes[0]) > 127)
				{
					$output .= sprintf( ' %04X {-%02X}', $this->rel_offset( $this->pc, ord($arg_bytes[0]),strlen($arg_bytes)), 256-ord($arg_bytes[0]));
				} else {
					$output .= sprintf( ' %04X {+%02X}', $this->rel_offset( $this->pc, ord($arg_bytes[0]),strlen($arg_bytes)), ord($arg_bytes[0]));					
				}
				break;
			case 'Abs':
				$output .= sprintf( ' %02X%02X', ord($arg_bytes[1]), ord($arg_bytes[0]));
				break;
			case 'AbsIndexX':
				$output .= sprintf( ' %02X%02X,X', ord($arg_bytes[1]), ord($arg_bytes[0]));
				break;
			case 'AbsIndexY':
				$output .= sprintf( ' %02X%02X,Y', ord($arg_bytes[1]), ord($arg_bytes[0]));
				break;
			case 'AbsIndexXInd':
				$output .= sprintf( ' (%02X%02X,X)', ord($arg_bytes[1]), ord($arg_bytes[0]));
				break;
			case 'AbsInd':
				$output .= sprintf( ' (%02X%02X)', ord($arg_bytes[1]), ord($arg_bytes[0]));
				break;
			case 'WDM':
			case 'StackInt':
				if( strlen( $arg_bytes ) > 0 )
				{
					$output .= sprintf( ' %02X', ord($arg_bytes[0] ));
				}
				break;
			case 'AbsIndLong':
				$output .= sprintf( ' (%02X%02X)', ord($arg_bytes[1]), ord($arg_bytes[0]));
				break;
			case 'AbsLong':
				$output .= sprintf( ' %02X%02X%02X', ord($arg_bytes[2]), ord($arg_bytes[1]), ord($arg_bytes[0]));
				break;
			case 'AbsIndexXLong':
				$output .= sprintf( ' %02X%02X%02X,X', ord($arg_bytes[2]), ord($arg_bytes[1]), ord($arg_bytes[0]));
				break;
			case 'BlockMove':
				$output .= sprintf( ' %02X,%02X', ord($arg_bytes[1]), ord($arg_bytes[0])); 
				break;
			case 'DPIndLong':
				$output .= sprintf( ' [%02X]', ord($arg_bytes[0] ));
				break;
			case 'DPIndIndexYLong':
				$output .= sprintf( ' [%02X],Y', ord($arg_bytes[0] ));
				break;
			case 'StackPCRel':
			case 'PCRelLong':
				$offset = (ord($arg_bytes[1])*256)+ord($arg_bytes[0]);
				if( $offset > 32767 )
				{
					$output .= sprintf( ' %04X {-%02X}', $this->rel_long_offset( $this->pc, $offset, strlen($arg_bytes)), 65536-$offset);
				} else {
					$output .= sprintf( ' %04X {+%02X}', $this->rel_long_offset( $this->pc, $offset, strlen($arg_bytes)), $offset);					
				}
				break;
			case 'StackAbs':
				$output .= sprintf( ' %02X%02X', ord($arg_bytes[1]), ord($arg_bytes[0])); 
				break;
			case 'StackDPInd':
				$output .= sprintf( ' %02X', ord($arg_bytes[0])); 
				break;
			case 'StackRel':
				$output .= sprintf( ' %02X,S', ord($arg_bytes[0]));
				break;
			case 'StackRelIndexY':
				$output .= sprintf( ' (%02X,S),Y', ord($arg_bytes[0]));
				break;
		}

		return $output;
	}

	/**
	 * disassemble( $m_flag, $x_flag, $e_flag, $origin, $data, $personality ) - Disassemble some data
	 *
	 * @param $m_flag bool, $x_flag bool, $e_flag bool, $origin int, $data str, $personality obj
	 * @return mixed
	 */
	public function disassemble( $m_flag, $x_flag, $e_flag, $origin, $data, $personality )
	{
		$this->x_flag = $x_flag;
		$this->e_flag = $e_flag;
		$this->m_flag = $m_flag;

		$this->pc = $origin;
		$data_index = 0;
		$last_output = '';
		
		echo "                      ORG ".sprintf('%06X',$origin)."\n";
		if( $this->m_flag > 0)
		{
			$print_m = '1';
		} else {
			$print_m = '0';
		}
		if( $this->x_flag > 0 )
		{
			$print_x = '1';
		} else {
			$print_x = '0';
		}
		echo "                      MX %{$print_m}{$print_x}\n\n";

		while( $data_index < strlen( $data ))
		{
			$opcode = $data[$data_index];
			$opcode_txt = $this->opcodes[ord($opcode)][0];
			$mode = $this->opcodes[ord($opcode)][1];

			// get opcode width
			$width = 0;
			switch( $mode )
			{
				case 'Acc':
				case 'Implied':
				case 'StackPull':
				case 'StackPush':
				case 'StackRTI':
				case 'StackRTL':
				case 'StackRTS':
					$width = 1;
					break;
				case 'DP':
				case 'DPIndexX':
				case 'DPIndexY':
				case 'DPIndexXInd':
				case 'DPInd':
				case 'DPIndLong':
				case 'DPIndIndexY':
				case 'DPIndIndexYLong':
				case 'PCRel':
				case 'StackRel':
				case 'StackRelIndexY':
				case 'StackDPInd':
					$width = 2;
					break;
				case 'Abs':
				case 'AbsIndexX':
				case 'AbsIndexY':
				case 'AbsIndexXInd':
				case 'AbsInd':
				case 'AbsIndLong':
				case 'BlockMove':
				case 'PCRelLong':
				case 'StackAbs':
				case 'StackPCRel':
					$width = 3;
					break;
				case 'AbsLong':
				case 'AbsIndexXLong':
					$width = 4;
					break;
				case 'StackInt':
					if( $this->e_flag )
					{
						$width = 1;
					} else {
						$width = 2;
					}
					break;
				case 'Imm':
					$width = 2;
					if( $this->e_flag == 0 )
					{
						if( in_array( $opcode_txt, array( 'CPX', 'CPY', 'LDX', 'LDY' )))
						{
							if( $this->x_flag == 0 )
							{
								$width = 3;
							}
						} elseif( in_array( $opcode_txt, array( 'ADC', 'AND', 'BIT', 'CMP', 'EOR', 'LDA', 'ORA', 'SBC' )))
						{
							if( $this->m_flag == 0 )
							{
								$width = 3;
							}
						}			
					}
					break;
				case 'WDM':
					$width = 2;
					break;
				default:
					$width = 1;
					break;
			} // switch

			// fetch arguments if any
			$arg_bytes = '';
			for( $ind=1;$ind<$width;$ind++ )
			{
				$arg_bytes .= $data[$data_index + $ind];
			}

			$output = $this->print_instruction( $opcode_txt, ord($opcode), $mode, $arg_bytes );

			// Personality support
			$this_output = substr( $output, 22 );
			$comment = $personality->check( $last_output, $this_output, $this );
			if( $comment )
			{
				for($pad=strlen($output);$pad<42;$pad++)
				{
					$output .= ' ';
				}
				$output .= '; '.$comment;
			}
			$last_output = $this_output;

			echo "{$output}\n";
			$this->pc += $width;
			$data_index += $width;

			// raw bytes support, if the Personality module signalled them. hacky.
			foreach( $this->bytes_queue as $raw_bytes)
			{
				// if personality told us the next few bytes will be raw, then handle them that way
				while( $raw_bytes > 0 )
				{
					if( $raw_bytes == 1 )
					{
						$output = sprintf( '%06X-   %02X          DB ', $this->pc, ord( $data[$data_index] ));
						$output .= sprintf( ' %02X', ord( $data[$data_index] ));
						$width = 1;
					}
					if( $raw_bytes == 4 )
					{
						$output = sprintf( '%06X-   %02X %02X %02X %02X ', $this->pc, ord($data[$data_index]), ord($data[$data_index+1]), ord($data[$data_index+2]), ord($data[$data_index+3]) );
						$output .= sprintf( ' %02X%02X%02X%02X', ord($data[$data_index+3]), ord($data[$data_index+2]), ord($data[$data_index+1]), ord($data[$data_index]));
						$width = 4;
					}
					if( $raw_bytes > 1 )
					{
						$output = sprintf( '%06X-   %02X %02X       DW ', $this->pc, ord($data[$data_index]), ord($data[$data_index+1]) );
						$output .= sprintf( ' %02X%02X', ord($data[$data_index+1]), ord($data[$data_index]));
						$width = 2;
					}
					$raw_bytes -= $width;
					$this_output = substr( $output, 22 );
					$comment = $personality->check( $last_output, $this_output, $this );
					if( $comment )
					{
						for($pad=strlen($output);$pad<42;$pad++)
						{
							$output .= ' ';
						}
						$output .= '; '.$comment;
					}
					$last_output = $this_output;
					echo "{$output}\n";
					$this->pc += $width;
					$data_index += $width;
				}
			}
			$this->bytes_queue = array();

		} // for
	}
}
