#! /usr/bin/python2.3

import sys
import re
import os
import string
import cStringIO

from filterwranscolnum import FilterWransColnum
from filterwransspeakers import FilterWransSpeakers
from filterwranssections import FilterWransSections


from filterdebatecoltime import FilterDebateColTime
from filterdebatespeakers import FilterDebateSpeakers
from filterdebatesections import FilterDebateSections


toppath = os.path.expanduser('~/pwdata')

# master function which carries the glued pages into the xml filtered pages

# incoming directory of glued pages directories
pwcmdirs = os.path.join(toppath, "pwcmpages")
# outgoing directory of scaped pages directories
pwxmldirs = os.path.join(toppath, "pwscrapedxml")

tempfile = os.path.join(toppath, "filtertemp")

# create the output directory
if not os.path.isdir(pwxmldirs):
	os.mkdir(pwxmldirs)

# this
def RunFiltersDir(filterfunction, dname, datefrom, dateto):
	# the in and out directories for the type
	pwcmdirin = os.path.join(pwcmdirs, dname)
	pwxmldirout = os.path.join(pwxmldirs, dname)

	# create output directory
	if not os.path.isdir(pwxmldirout):
		os.mkdir(pwxmldirout)

	# loop through file in input directory in reverse date order
	fdirin = os.listdir(pwcmdirin)
	fdirin.sort()
	fdirin.reverse()
	for fin in fdirin:
		jfin = os.path.join(pwcmdirin, fin)

		# extract the date from the file name
		sdate = re.search('\d{4}-\d{2}-\d{2}', fin).group(0)

                # skip dates outside the range specified on the command line
                if sdate < datefrom or sdate > dateto:
                        continue

		# create the output file name
		jfout = os.path.join(pwxmldirout, re.sub('\.html$', '.xml', fin))

		# skip already processed files
		if os.path.isfile(jfout):
			continue

		# read the text of the file
		print fin
		ofin = open(jfin)
		text = ofin.read()
		ofin.close()

		# call the filter function and copy the temp file into the correct place.
		# this avoids partially processed files getting into the output when you hit escape.
		fout = open(tempfile, "w")
		filterfunction(fout, text, sdate)
		fout.close()
		os.rename(tempfile, jfout)

# These text filtering functions filter twice through stringfiles,
# before directly filtering to the real file.
def RunWransFilters(fout, text, sdate):
	si = cStringIO.StringIO()
	FilterWransColnum(si, text, sdate)
	text = si.getvalue()
	si.close()

	si = cStringIO.StringIO()
	FilterWransSpeakers(si, text, sdate)
	text = si.getvalue()
	si.close()

	FilterWransSections(fout, text, sdate)


def RunDebateFilters(fout, text, sdate):
	si = cStringIO.StringIO()
	FilterDebateColTime(si, text, sdate)
	text = si.getvalue()
	si.close()

	si = cStringIO.StringIO()
	FilterDebateSpeakers(si, text, sdate)
	text = si.getvalue()
	si.close()

	FilterDebateSections(fout, text, sdate)



