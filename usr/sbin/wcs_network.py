#!/usr/bin/python

import pdb
import logging
import wcs_connect
import wcs_update

log_filename = '/home/pi/log/wcs_network.log'

def do_main():
  logging.basicConfig( filename=log_filename, level=logging.DEBUG )
  logging.info( 'Program starting in do_main()' )

  connector = wcs_connect.WCSInternetConnector()
  if connector.connect() == True:
    updater = wcs_update.WCSUpdater()
    if updater.update() == False:
      logging.error( 'Updating failed.  See %s', log_filename )
    else:
      logging.info( 'Updating succeeded.' )

#
# Program starts here.
#

# pdb.set_trace()
do_main()

