#!/usr/bin/python

import pdb

import commands
import os
import re
import sys
import time

config_file = '/mnt/wcs_flash/wcs_wpa.txt'
id_file = '/mnt/wcs_flash/wcs_id.txt'

interfaces_ap_file = '/etc/network/interfaces.hostapd'

def in_ap_network( ip ):
  # Return True if the ip address is in the AP network.
  if os.path.exists( interfaces_ap_file ):
    ip_net = ip.split( '.' )
    try:
      with open( interfaces_ap_file, 'r' ) as cfg:
        read_data = cfg.read().split( '\n' )
      cfg.closed
    except IOError as (errno, strerror):
      print "Config file exists but cannot open: " + interfaces_ap_file
      print "I/O error({0}): {1}".format( errno, strerror )
      return -1
    for line in read_data:
      i1 = line.find( 'address' )
      if i1 != -1:
        network_ip_addr = line[i1 + len( 'address' ):].split()[0] 
        net = network_ip_addr.split( '.' )
        if ip_net[0] == net[0] and ip_net[1] == net[1] and ip_net[2] == net[2]:
          return True
  return False

def backup( path, orig ):
  backup_filename = path + orig + '.' +  time.strftime( '%Y%m%d-%H%M%S' )
  os.system( 'cp ' + path + orig + ' ' + backup_filename )

def register_ip( ip_addr ):
  import requests
  unit_id = '999'
  if os.path.exists( id_file ):
    try:
      with open( id_file, 'r' ) as cfg:
        read_data = cfg.read().split( '\n' )
      cfg.closed
    except IOError as (errno, strerror):
      print "Id file exists but cannot open: " + id_file
      print "I/O error({0}): {1}".format( errno, strerror )
      print "Cannot read id file.  Assigning default unit_id of 999."
    for line in read_data:
      i1 = line.find( 'id' )
      if i1 != -1:
        unit_id = line[i1 + len( 'id' ):].split()[0] 
  try:
    data = dict( id=unit_id, ip=ip_addr )
    r = requests.get( "http://bellcoho.com/community/Steve/wcs_register.php", params=data )
    print r.url
    print r.text
  except:
    print "Unexpected error: ", sys.exc_info()[0]

def is_eth0() :
  retval = False
  search1 = 'eth0'
  search2 = 'inet addr:'
  s = commands.getoutput( '/sbin/ifconfig' ).split( '\n' )
  for line in s:
    if search1 in line:
      nextline = s[s.index(line)+1].lstrip()
      if search2 in nextline:
        i1 = nextline.find( search2 )
        ip_addr = nextline[i1 + len( search2 ):].split()[0]
        print 'Ethernet connected'
        retval = True
        register_ip( ip_addr )
        # Turn off dnsmasq and hostapd services, as they are not
        # needed and will consume cpu.  Only needed when the unit
        # is an AP.
        os.system( 'service dnsmasq stop' )
        os.system( 'service hostapd stop' ) 
        break
  return retval

def is_wlan0() :
  search1 = 'wlan0'
  s = commands.getoutput( '/sbin/ifconfig' ).split( '\n' )
  for line in s:
    if search1 in line:
      return True
  return False

def is_connected( do_ifdown = True ):
  retval = False
  search1 = 'wlan0'
  search1a = 'mon.wlan0'
  search2 = 'inet addr:'
  if do_ifdown :
    # do 'ifdown wlan0; ifup wlan0' to start the wireless interface.
    os.system( 'ifdown wlan0; ifup wlan0' )
  s = commands.getoutput( '/sbin/ifconfig' ).split( '\n' )
  for line in s:
    if search1 in line:
      print line
      if search1a in line:
        continue
      nextline = s[s.index(line)+1].lstrip()
      print nextline
      if search2 in nextline:
        i1 = nextline.find( search2 )
        ip_addr = nextline[i1 + len( search2 ):].split()[0]
        print ip_addr
        # If the IP address is our address as a standalone AP,
        # we aren't really connected and should fall through to
        # the code that takes care of the AP setup.
        # Look up the AP IP address in interfaces_ap_file.
        if in_ap_network( ip_addr ):
          break
        print 'Network connected'
        retval = True
        # Call a web service to report this unit's IP address.
        register_ip( ip_addr )
        # Turn off dnsmasq and hostapd services, as they are not
        # needed and will consume cpu.  Only needed when the unit
        # is an AP.
        os.system( 'service dnsmasq stop' )
        os.system( 'service hostapd stop' ) 
        # Don't see why I would need to take the interface down and up again.
        # # do 'ifdown wlan0; ifup wlan0' to start the wireless interface.
        # os.system( 'ifdown wlan0; ifup wlan0' )
        break
  return retval

def do_main() :
  # If there is an ethernet connection, report it and stop.
  if is_eth0() :
    return

  # If there is no wlan0 connection, we can't go further.
  if not is_wlan0() :
    return

  time.sleep(10)

  # Read the configuration specified in a file on the USB stick,
  # if it exists.
  # If it contains "override true" then use the configuration specified
  # even if we could connect to the network we were previously on.
  connected = False
  ssid = ""
  psk = ""
  override = False
  if os.path.exists( config_file ):
    try:
      with open( config_file, 'r' ) as cfg:
        read_data = cfg.read().split( '\n' )
      cfg.closed
    except IOError as (errno, strerror):
      print "Config file exists but cannot open: " + config_file
      print "I/O error({0}): {1}".format( errno, strerror )
    print read_data
    # read_data may contain a value for ssid and wpa-psk
    for line in read_data:
      i1 = line.find( 'wpa-ssid' )
      if i1 != -1:
        ssid = line
      i1 = line.find( 'wpa-psk' )
      if i1 != -1:
        psk = line
      if "override" in line and "true" in line:
        override = True

  # See if we're already connected and don't need to proceed further.
  # If override is set, skip this so we'll always to to the new network
  # that has been requested.
  if override == False:
    connected = is_connected()

  if connected == False:
    # Try to connect with the configuration specified from the
    # file on the USB stick.
    if ssid != "":
      # if there is an ssid, open interfaces.wpa and replace these values in that file.
      # backup the current interfaces.wpa to one with a timestamp appended
      # to the file name.
      # if read_data has a wpa-ssid and no wpa-psk, delete any existing
      # wpa-psk from interfaces.wpa.
      backup( '/etc/network/', 'interfaces.wpa' )
      with open( '/etc/network/interfaces.wpa', 'r' ) as wpa_cfg:
        read_data = wpa_cfg.read().split( '\n' )
      wpa_cfg.closed
      print read_data
      tmp_name = '/tmp/interfaces.wpa'
      with open( tmp_name, 'w' ) as new_cfg:
        for line in read_data:
          if ( line.find( 'wpa-ssid' ) == -1 ) and ( line.find( 'wpa-psk' ) == -1 ):
            new_cfg.write( line + '\n' )
        new_cfg.write( ssid + '\n' )
        if psk != "":
          new_cfg.write( psk + '\n' )
      new_cfg.closed
      # save the new file as interfaces.wpa and copy it into the operant 
      # interfaces file.
      os.system( 'mv ' + tmp_name + ' /etc/network/interfaces.wpa' )
      os.system( 'cp /etc/network/interfaces.wpa /etc/network/interfaces' )
      os.system( 'service dnsmasq stop' )
      os.system( 'service hostapd stop' ) 
      time.sleep(10)
      # do 'ifdown wlan0; ifup wlan0' to start the wireless interface.
      os.system( 'ifdown wlan0; ifup wlan0' )
      # if everything works, get the IP address and report it to the
      # web service and set connected to True.
      # Test the new connection.
      connected = is_connected( False )
      # Give it another try if it failed, in case there was a timing issue.
      if connected == False:
        time.sleep(10)
        os.system( 'ifdown wlan0; ifup wlan0' )
        connected = is_connected( False )

  if connected == False:
    # No network available.  Configure to be a wireless access point (AP).
    # Copy /etc/network/interfaces.hostapd to /etc/network/interfaces,
    # after backing up interfaces to have a timestamp appended to its
    # file name.
    # do 'ifdown wlan0; ifup wlan0' to start the wireless interface.
    # do 'service dnsmasq restart' and 'service hostapd restart'.
    # This may not work, so the ifconfig may show an address but the
    # antenna isn't broadcasting.
    backup( '/etc/network/', 'interfaces' )
    os.system( 'cp ' + interfaces_ap_file + ' /etc/network/interfaces' )
    time.sleep(10)
    os.system( 'ifdown wlan0; ifup wlan0' )
    os.system( 'service dnsmasq restart' )
    os.system( 'service hostapd restart' ) 

#
# Program starts here.
#

# pdb.set_trace()
do_main()

