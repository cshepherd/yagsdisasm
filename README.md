yagsdisasm - Yet Another GS Disassembler
========================================

yagsdisasm is written in PHP, which may be an unfortunate choice of languages to many, but is nearly-universally available and makes for a product that's easy to modify, when written properly.

the disassembly engine is largely inspired by Andy McFadden's Ciderpress disassembly engine, which is why we copy his license terms in LICENSE.txt

as of 5/25/2016, the project is only a couple hours old and is a proof of concept. future revisions to come later

### Example Run:

	$ php yagsdisasm.php -x 0 -m 0 -e 0 -f /Applications/Emulation/Apple\ IIGS/images/WhatIs/Alien\ Mind/Alien\ Mind\ Dk1.2mg -s 1 -n 1 -k 1 -o 801| more
	                      ORG 000801
	                      MX %00

	000801-   0A          ASL
	000802-   A9 10 00    LDA #0010
	000805-   8D 9F 0A    STA 0A9F
	000808-   A9 00 01    LDA #0100
	00080B-   8D A1 0A    STA 0AA1
	00080E-   60          RTS
	00080F-   20 62 09    JSR 0962
	000812-   F4 00 00    PEA 0000
	000815-   F4 07 00    PEA 0007
	000818-   F4 00 20    PEA 2000
	00081B-   F4 00 40    PEA 4000
	00081E-   F4 00 00    PEA 0000
	000821-   F4 36 0A    PEA 0A36
	000824-   F4 00 00    PEA 0000
	000827-   F4 3A 0A    PEA 0A3A
	00082A-   A2 03 27    LDX #2703
	00082D-   22 00 00 E1 JSL E10000          ; Toolbox: UnPackBytes(@Buff,BfSz,@StartPtr,@Sz):Size
	000831-   68          PLA
	000832-   8D 00 03    STA 0300
	000835-   60          RTS
	000836-   00 20       BRK 20
	000838-   E1 00       SBC (00,X)
	00083A-   00 80       BRK 80
	00083C-   20 67 09    JSR 0967
	00083F-   2C 10 C0    BIT C010            ; r:KBDSTRB
	000842-   AD 00 C0    LDA C000            ; r:KBD  w:CLR80COL
	000845-   30 13       BMI 085A {+13}
	000847-   AD 61 C0    LDA C061            ; r:BUTN0
	00084A-   30 0E       BMI 085A {+0E}
	00084C-   AD 62 C0    LDA C062            ; r:BUTN1
	00084F-   30 09       BMI 085A {+09}
	000851-   AD 27 C0    LDA C027            ; rw:KMSTATUS
	000854-   89 80 D0    BIT #D080
