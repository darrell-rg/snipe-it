#!/usr/bin/env python3

#
#  https://www.jadaktech.com/documents-downloads/thingmagic-mercury-api-v1-37-0-80/?download
#
#
#
#


# Copyright (C) 2016 Enrico Rossi
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published
# by the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

import serial
import argparse

from dataclasses import dataclass
import pprint
import ipdb
pp = pprint.PrettyPrinter(indent=4)


@dataclass
class tagClass:
    metadata: bytes
    epc: bytes
    crc: bytes
    def pp(self):
        print("meta=",' '.join('{:02x}'.format(x) for x in self.metadata).upper())
        print(" EPC=",' '.join('{:02x}'.format(x) for x in self.epc).upper())


@dataclass
class cmdClass:
    desc: str
    hexSent: str
    hexReceived: str
    errorMessage: str

    def getSendBytes(self) -> bytes:
        return bytes.fromhex(self.hexSent)

    def getRecBytes(self) -> bytes:
        return bytes.fromhex(self.hexReceived)
    
    def getTags(self):
        tags = []
        b = self.getRecBytes()
        cmdb = b[0:8] 
        offset=8
        # print(f"cmd={cmdb}")
        if b[2] == 0x29:
        #    ipdb.set_trace()
           tag_count = cmdb[7]
           while offset+32 < len(b):
               metadata = b[offset+0:offset+20]
               epc = b[offset+20:offset+32]
               crc = b[offset+32:offset+34]
               tags.append(tagClass(metadata,epc,crc))
               offset += 34

        return tags
    
    def validate(self) -> bool:
        b = self.getRecBytes()
        if b[0] != 0xFF:
            self.errorMessage = "First byte was not FF"
            return False
        msg_len = int(b[1]) + 7
        if len(b) != msg_len:
            actual = len(b) 
            self.errorMessage = f"Expected Received msg length to be {msg_len}, was actually {actual}"
            return False
        statusCode =  int(b[3]) * 0xFF +   int(b[4]) 
        if statusCode != 0:
            self.errorMessage = f"Received error: {hex(statusCode)}"
            return False

        return True
       

script = []
hasErrors = False
# reads in a URA debug log for replay.  Note log must have LF for newlines
with open("2tags.txt", 'r', encoding='UTF-8') as file:
    line = file.readline()
    while line:
        cmd = cmdClass("","","","")
        if("Sending") in line:
            cmd.desc = line.strip()[26:]
            line = file.readline()
            while line.startswith(' '):
                cmd.hexSent += line.strip()+'  '
                line = file.readline()
                
        if("Received") in line:
            # skip one line 
            line = file.readline()
            while line.startswith(' '):
                # strip off comments
                cmd.hexReceived += line.strip().split('-')[0] +'  '
                line = file.readline()
                
        
        # prepend the desc with the hex code for the command
        cmd.desc = cmd.desc +' '+ cmd.hexSent.split('  ')[2]+ 'h ' 

        if(cmd.validate()):
            for tag in cmd.getTags():
                tag.pp()
            script.append(cmd)
        else:
            print("ERRROR FOUND")
            hasErrors = True
            pp.pprint(cmd)
            exit(0)

# pp.pprint(script)
exit(0)


# /* From the datasheet this is the C function used to
# Calculate the CRC */
#
# void CRC_calcCrc8(u16 *crcReg, u16 poly, u16 u8Data)
# {
#  u16 i;
#  u16 xorFlag;
#  u16 bit;
#  u16 dcdBitMask = 0x80;
#
#  for (i=0; i<8; i++) {
#   xorFlag = *crcReg & 0x8000;
#   *crcReg <<= 1;
#   bit = ((u8Data & dcdBitMask) == dcdBitMask);
#   *crcReg |= bit;
#
#   if (xorFlag)
#    *crcReg = *crcReg ^ poly;
#
#   dcdBitMask >>= 1;
#  }
# }

class Rfid:
    """ The basic class definition
    """

    _s = serial.Serial()
    _s.port = None
    _s.baudrate = 9600  
    _s.bytesize = 8
    _s.parity = 'N'
    _s.stopbits = 1
    _s.timeout = 3
    crc = 0

    def CRC_calcCrc8(self, data):
        """
        """
        dcdBitMask = 0x80

        for i in range(8):
            xorFlag = self.crc & 0x8000
            self.crc <<= 1
            bit = ((data & dcdBitMask) == dcdBitMask)
            self.crc |= bit

            if xorFlag:
                self.crc ^= 0x1021

            dcdBitMask >>= 1

        self.crc &= 0xffff

    def _tx(self, cmd):
        """ Send the command to the serial port one char at a time.
        """

        # Clear the RX buffer
        self._s.flushInput()

        # If the command does not start with 0xff it means
        # it contains only the command and data.
        # Else it is a naked command with header and crc already
        # added and it should be sent as is.
        if (cmd[0] != 255):
            self.crc = 0xffff
            # Need to include len() in the CRC
            cmd = (len(cmd)-1).to_bytes(1, byteorder='big') + cmd

            for i in cmd:
                self.CRC_calcCrc8(i)

            cmd = b'\xff' + cmd + self.crc.to_bytes(2, byteorder='big')

        print(cmd.hex())
        self._s.write(cmd)

    def _rx(self):
        """ Read 255 char or until timeout from the serial port.
        This function need a serious rewrite. Right now you have to wait 10sec.
        before see what has been received from the port.
        """
        ff =  self._s.read(1)
        if (ff) != 0xFF :
            print("ERROR, First byte was not FF")
            rx = self._s.read(255)
        else:
            msg_len =  int(self._s.read(1)) + 5
            rx = self._s.read(msg_len)
        print(rx.hex())

    def connect(self, device):
        """ Connect to the device.
        """

        if device is None:
            raise "A device MUST be given!"

        self._s.port = device
        self._s.open()
    
    def disconnect(self):
        """ Close the connection.
        """
        self._s.close()

txt = ''
rfid = Rfid()
parser = argparse.ArgumentParser(description='Thing Magic m5e-C CLI.')
parser.add_argument('device', nargs='?', default='/dev/ttyUSB0',
        help="ex. /dev/ttyUSB0 or /dev/ttyS0")
args = parser.parse_args()

# the serial port used.
rfid.connect(args.device)




for cmd in script:
    try:
        tx = cmd.getSendBytes()
        # print(cmd+'>'+txt)
        if (len(tx)):
            rfid._tx(tx)

        rfid._rx()
    except Exception as e:
        print('Error: ',e, txt)


print("disconnecting the device")
rfid.disconnect()
del(rfid)
del(parser)

# vim: tabstop=8 expandtab shiftwidth=4 softtabstop=4
# 04 = Boot 
# 03 = Get Version 

