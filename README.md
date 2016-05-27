yagsdisasm - Yet Another GS Disassembler
========================================

yagsdisasm is written in PHP, which may be an unfortunate choice of languages to many, but is nearly-universally available and makes for a product that's easy to modify, when written properly.

the disassembly engine is largely inspired by Andy McFadden's Ciderpress disassembly engine, which is why we copy his license terms in LICENSE.txt

as of 5/27/2016, the project is gaining features such as processor flag tracking and more vector recognition, but still has a ways to go

### Example Run:

	$ php yagsdisasm.php -f ~/Downloads/GATE/The\ Gate\ Dk1.2mg -s0 -n1 -k1 -m1 -x1 -e1 -o801 | more
	                      ORG 000801
	                      MX %11

	000801-   EE F4 03    INC 03F4
	000804-   9C 34 C0    STZ C034            ; rw:CLOCKCTL [+border color]
	000807-   9C 22 C0    STZ C022            ; rw:TBCOLOR
	00080A-   EA          NOP
	00080B-   EA          NOP
	00080C-   EA          NOP
	00080D-   EA          NOP
	00080E-   AD 10 C0    LDA C010            ; r:KBDSTRB
	000811-   8D 00 C0    STA C000            ; r:KBD  w:CLR80COL
	000814-   8D 0C C0    STA C00C            ; w:CLR80VID
	000817-   9C 12 0E    STZ 0E12
	00081A-   AF 20 0F E1 LDA E10F20
	00081E-   8D 10 0E    STA 0E10
	000821-   AF 21 0F E1 LDA E10F21
	000825-   F0 07       BEQ 082E {+07}
	000827-   4F 20 0F E1 EOR E10F20
	00082B-   8D 12 0E    STA 0E12
	00082E-   18          CLC
	00082F-   FB          XCE
	000830-   C2 30       REP #30
	000832-   A9 80 FE    LDA #FE80
	000835-   8F 70 00 E1 STA E10070          ; Break vector
	000839-   A2 00 00    LDX #0000
	00083C-   A9 A0 A0    LDA #A0A0
	00083F-   9D 00 04    STA 0400,X
	000842-   E8          INX
	000843-   E8          INX
	000844-   E0 00 04    CPX #0400
	000847-   90 F3       BCC 083C {-0D}
	000849-   A2 00 A0    LDX #A000
	00084C-   CA          DEX
	00084D-   CA          DEX
	00084E-   9E 00 20    STZ 2000,X
	000851-   D0 F9       BNE 084C {-07}
	000853-   E2 30       SEP #30
	000855-   A9 00       LDA #00
	000857-   8D 22 C0    STA C022            ; rw:TBCOLOR
	00085A-   A9 00       LDA #00
	00085C-   8D 34 C0    STA C034            ; rw:CLOCKCTL [+border color]
	00085F-   C2 30       REP #30
	000861-   A2 00 00    LDX #0000
	000864-   A9 00 00    LDA #0000
	000867-   9F 00 7E E1 STA E17E00,X
	00086B-   E8          INX
	00086C-   E8          INX
	00086D-   E0 00 01    CPX #0100
	000870-   D0 F5       BNE 0867 {-0B}
	000872-   E2 30       SEP #30
	000874-   20 80 09    JSR 0980
	000877-   A9 14       LDA #14
	000879-   8D 78 09    STA 0978
	00087C-   9C 79 09    STZ 0979
	00087F-   E2 30       SEP #30
	000881-   38          SEC
	000882-   FB          XCE
	000883-   20 0D C5    JSR C50D            ; SmartPort Entry
	000886-   41          DB  41              ; Call Number
	000887-   6E 09       DW  096E            ; Pointer to parameters
	000889-   00          BRK
	00088A-   00          BRK
	00088B-   B0 F6       BCS 0883 {-0A}
	00088D-   18          CLC
	00088E-   FB          XCE
	00088F-   C2 30       REP #30
	000891-   EE 71 09    INC 0971
	000894-   EE 71 09    INC 0971
	000897-   EE 74 09    INC 0974
	00089A-   CE 78 09    DEC 0978
	00089D-   D0 E0       BNE 087F {-20}
	00089F-   8B          PHB
	0008A0-   F4 00 00    PEA 0000
	0008A3-   F4 01 00    PEA 0001
	0008A6-   F4 00 0A    PEA 0A00
	0008A9-   F4 10 00    PEA 0010
	0008AC-   F4 1E 00    PEA 001E
	0008AF-   22 00 A5 00 JSL 00A500
	0008B3-   22 00 0A 01 JSL 010A00
	0008B7-   18          CLC
	0008B8-   FB          XCE
	0008B9-   C2 30       REP #30
	0008BB-   8B          PHB
	0008BC-   F4 00 00    PEA 0000
	0008BF-   F4 01 00    PEA 0001
	0008C2-   F4 00 9E    PEA 9E00
	0008C5-   F4 01 00    PEA 0001
	0008C8-   F4 41 00    PEA 0041
	0008CB-   22 00 96 00 JSL 009600
	0008CF-   8B          PHB
	0008D0-   F4 01 00    PEA 0001
	0008D3-   F4 07 00    PEA 0007
	0008D6-   F4 00 20    PEA 2000
	0008D9-   F4 12 00    PEA 0012
	0008DC-   F4 2E 00    PEA 002E
	0008DF-   22 00 96 00 JSL 009600
	0008E3-   A9 20 20    LDA #2020
	0008E6-   A0 10 00    LDY #0010
	0008E9-   A2 00 00    LDX #0000
	0008EC-   9F 00 9D E1 STA E19D00,X
	
