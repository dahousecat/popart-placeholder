#!/usr/bin/env python

import sys, ConfigParser

config = ConfigParser.ConfigParser()
config.readfp(sys.stdin)

for sec in config.sections():

    # If is has not been declared then declare it
    print "declare -p %s >/dev/null 2>&1 || declare -A %s" % (sec.upper(), sec.upper())

    # Simple version that does not check if variable is already declared
    # print "declare -A %s" % (sec.upper())

    for key, val in config.items(sec):
        print '%s[%s]="%s"' % (sec.upper(), key, val)
