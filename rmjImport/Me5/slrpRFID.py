from sllurp import llrp
from twisted.internet import reactor
from twisted.internet.serialport import SerialPort
import logging

import sys

from twisted.internet.serialport import SerialPort
from twisted.internet.protocol import Protocol, Factory
from twisted.python import log


class SerialProtocol(Protocol):

    def __init__(self, factory):
        self.factory = factory

    def dataReceived(self, data):
        log.msg(data.strip())
        self.factory.notify(data)


class TCPProtocol(Protocol):

    def connectionMade(self):
        self.factory.client = self

    def connectionLost(self, reason):
        self.factory.client = None


class SerialCommFactory(Factory):
    protocol = TCPProtocol

    def __init__(self):
        self.client = None

    def notify(self, data):
        if self.client is None:
            log.msg('client is None')
        else:
            self.client.transport.write(data)

logging.getLogger().setLevel(logging.INFO)

# reactor.connectTCP('myreader', llrp.LLRP_PORT, factory)

def cb (tagReport):
    tags = tagReport.msgdict['RO_ACCESS_REPORT']['TagReportData']
    print('tags:', tags)

if __name__ == '__main__':
    
    factory = llrp.LLRPClientFactory()
    factory.addTagReportCallback(cb)

    log.startLogging(sys.stdout)
    # factory = SerialCommFactory()
    # reactor.listenTCP(8000, factory)
    SerialPort(SerialProtocol(factory), '/dev/ttyUSB0', reactor, baudrate='115200')
    reactor.run()

