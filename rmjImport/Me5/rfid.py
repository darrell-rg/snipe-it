#!/usr/bin/env python3
from __future__ import print_function
import time
from datetime import datetime
import mercury
#  antenna=1, protocol='GEN2'
print("you must send the boot command (04) before this script will work!")
# working mercuryapi is 1.29.4.34

reader = mercury.Reader("tmr:///dev/ttyUSB0", baudrate=9600)

def stats_received(stats):
    print({"temp" : stats.temperature})
    print({"antenna" : stats.antenna})
    print({"protocol" : stats.protocol})
    print({"frequency" : stats.frequency})

reader.enable_stats(stats_received)

print('Model:',reader.get_model())
print('Software Version:',reader.get_software_version())
print('Regions:',reader.get_supported_regions())
print('Setting Region to:','NA')
reader.set_region("NA")

print('antennas:',reader.get_antennas())
print('ports:',reader.get_connected_ports())
print('get_temperature:',reader.get_temperature())
print('power range:',reader.get_power_range())


# these give RuntimeError: Message command length is incorrec
# print('gpio inputs:',reader.get_gpio_inputs())
# print('read powers:',reader.get_read_powers())
# print('write powers:',reader.get_write_powers())
print(reader.get_read_powers())

reader.set_read_plan([1], "GEN2",  read_power=600)  #bank=["epc"],

print(reader.read())

# reader.set_read_plan([1], "GEN2", read_power=1900)
# print(reader.read())

# reader.start_reading(lambda tag: print(tag.epc, tag.antenna, tag.read_count, tag.rssi, datetime.fromtimestamp(tag.timestamp)))
time.sleep(1)
# reader.stop_reading()
