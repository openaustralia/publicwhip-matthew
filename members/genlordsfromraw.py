import re
import os
import sys
import string 

import mx.DateTime

# go through the raw lords data and generate our files of lords members

#<lord
#    id="uk.org.publicwhip/lord/1158"
#    house="lords"
#    title="Lord" lordname="Ackner" lordofname="Weedon"
#    peeragetype="Law Lord" affiliation="Cross bench"
#    fromdate="2001-06-07"
#    source="Lords2004-02-09WithFromDate.html"
#/>


# more tedious stuff to do: "earl of" and "sitting as" cases

titleconv = {  'L.':'Lord',
			   'B.':'Baroness',
			   'Abp.':'Arch-bishop',
			   'Bp.':'Bishop',
			   'V.':'Viscount',
			   'E.':'Earl',
			   'D.':'Duke',
			   'M.':'Marquess',
			   'C.':'Countess',
			   'Ly.':'Lady',
			}


class lordrecord:
	def __init__(self):
		self.title = ''
		self.lordname = ''
		self.lordofname = ''
		self.frontnames = ''

		self.peeragetype = ''
		self.affiliation = ''
		self.date = ''
		self.source = ''

		self.comments = ''

	def daltname(self):
		if self.lordname:
			return self.lordname
		assert self.lordofname
		return self.lordofname

	def OutRecord(self, fout):
		fout.write('<lord\n')
		fout.write('\tid="uk.org.publicwhip/lord/%d"\n\thouse="lords"\n' % self.lid)
		fout.write('\ttitle="%s" lordname="%s" lordofname="%s"\n' % (self.title, self.lordname, self.lordofname))
		fout.write('\tfrontnames="%s"\n' % self.frontnames)
		fout.write('\tpeeragetype="%s" affiliation="%s"\n' % (self.peeragetype, self.affiliation))
		fout.write('\tfromdate="%s"\n' % self.date)
		fout.write('\tsource="%s"\n' % self.source)
		fout.write('\tcomment="%s"\n' % self.comments)
		fout.write('/>\n')

	def __repr__(self):
		return '<%s [%s] [of %s]>' % (self.title, self.lordname, self.lordofname)


def lrsort(nr1, nr2):
	return nr1.daltname() < nr2.daltname()

#	<TR VALIGN=TOP>
#		<TD WIDTH=222 HEIGHT=16 BGCOLOR="#ffffbf">
#			<P><FONT COLOR="#000000"><FONT FACE="Arial"><FONT SIZE=2>Aberdare,
#			L.</FONT></FONT></FONT></P>
#		</TD>
#		<TD WIDTH=157 BGCOLOR="#ffffbf">
#			<P><FONT COLOR="#000000"> <FONT FACE="Arial"><FONT SIZE=2>Deputy
#			Hereditary</FONT></FONT></FONT></P>
#		</TD>
#		<TD WIDTH=119 BGCOLOR="#ffffbf">
#			<P><FONT COLOR="#000000"> <FONT FACE="Arial"><FONT SIZE=2>Other</FONT></FONT></FONT></P>
#		</TD>
#		<TD WIDTH=175 BGCOLOR="#ffffbf">
#			<P><FONT COLOR="#000000"> <FONT FACE="Arial"><FONT SIZE=2>4/10/1957</FONT></FONT></FONT></P>
#		</TD>
#	</TR>

class lordsrecords:
	def __init__(self):
		self.lordrec = [ ]

	# find a record with matching stuff
	def FindRec(self, lname, ltitle, lofname):

		# these work on blank fields

        # match by name
		lr1a = [ ]
		for r in self.lordrec:
			if lname == r.lordname:
				lr1a.append(r)

		# match by ofname
		lr1 = [ ]
		for r in lr1a:
			if lofname == r.lordofname:
				lr1.append(r)

		# no match cases
		if not lr1:
			return None

		# match by title
		retit = re.compile(ltitle, re.I)
		lr2 = [ ]
		for r in lr1:
			if retit.match(r.title):
				lr2.append(r)

		if not lr2:
			return None

		if len(lr2) != 1:
			print lr2
		assert len(lr2) == 1

		return lr2[0]



	def AddRecord(self, nr):
		r = self.FindRec(nr.lordname, nr.title, nr.lordofname)
		if not r:
			# check consistency
			self.lordrec.append(nr)

	def AddExtnameRecord(self, nr):
		r = self.FindRec(nr.lordname, nr.title, nr.lordofname)
		if r:
			r.frontnames = nr.frontnames
			r.comments = nr.comments
		else:
			nr.comments = nr.comments + " -- in extname, missing from mainlists"
			self.lordrec.append(nr)


def LoadTableWithFromDate(fpath, fname):
	fin = open(os.path.join(fpath, fname), "r")
	text = fin.read()
	fin.close()

	res = [ ]

	# extract the rows
	rows = re.findall('<tr[^>]*>\s*([\s\S]*?)\s*</tr>(?i)', text)
	for row in rows:

		# extract the columns of a row
		row = re.sub('(&nbsp;|\s)+', ' ', row)
		cols = re.findall('<td[^>]*>(?:<p[^>]*>|<font[^>]*>|<b>|\s)*([\s\S]*?)(?:</p>|</font>|</b>|\s)*</td>(?i)', row)

		# get rid of known non-lord lines
		if not cols[0] or re.search("members of the house|<br>|short\s*title(?i)", cols[0]) or not cols[1]:
			continue

		lordrec = lordrecord()

		# decode the easy parts
		lordrec.peeragetype = cols[1]
		lordrec.affiliation = cols[2]

		# the mx.datetime can't handle this
		dt = re.match('(\d{1,2})/(\d{1,2})/(\d{4})', cols[3])
		assert dt
		dyr = string.atoi(dt.group(3))
		dmo = string.atoi(dt.group(2))
		dda = string.atoi(dt.group(1))
		assert (dyr <= 2004) and (dyr > 1900) and (dmo >= 1) and (dmo <= 12) and (dda >= 1) and (dda <= 31)
		lordrec.date = '%04d-%02d-%02d' % (dyr, dmo, dda)

		lordrec.source = fname

		# decode the fullname
		lordfname = cols[0]
		lfn = re.match('(.*?)(?: of (.*?))?, (.*?\.)$', lordfname)
		if not lfn:
			print lordfname
		lordrec.title = titleconv[lfn.group(3)]
		lordrec.lordname = lfn.group(1)
		if lfn.group(2):
			lordrec.lordofname = lfn.group(2)

		# map the bishops into of-names
		if re.search('bishop(?i)', lordrec.title):
			assert not lordrec.lordofname
			lordrec.lordofname = lordrec.lordname
			lordrec.lordname = ''

		res.append(lordrec)

	return res

# run through and find extended names information
# this creates incomplete records which are later to be merged with the main list
def LoadExtendedNames(fpath, fname):
	fin = open(os.path.join(fpath, fname), "r")
	text = fin.read()
	fin.close()

	res = [ ]

	# extract the rows (very cheeky splitting of the <br> tag
	rows = re.findall('[rp]>\s*([^<]*)<b(?i)', text)
	for row in rows:
		if not row:
			continue
		row = re.sub('\s+', ' ', row)

		#ACTON, RICHARD GERALD, Lord (sits as Lord Acton of Bridgnorth)
		fnm = re.match('(.*?)(?: OF (.*?))?,\s*(.*?),\s*(.*?)( of)?\s*(?:\((.*?)\))?$', row)

		lordrec = lordrecord()

		lordrec.source = fname

		lordrec.title = fnm.group(4)
		lordrec.lordname = string.capwords(re.sub('&#8217;', "'", fnm.group(1)))
		if fnm.group(2):
			lordrec.lordofname = string.capwords(fnm.group(2))
		lordrec.frontnames = string.capwords(fnm.group(3))
		if fnm.group(5):
			assert not lordrec.lordofname
			lordrec.lordofname = lordrec.lordname
			lordrec.lordname = ''

		if fnm.group(6):
			lordrec.comments  = fnm.group(6)

		res.append(lordrec)

	return res



##################
# run through the inputs of information.
##################

rr1 = LoadTableWithFromDate('../rawdata/lords', 'Lords2004-02-09WithFromDate.html')
rr2 = LoadTableWithFromDate('../rawdata/lords', 'LordsSince1997.html')

# combine the inputs (could then sort and check duplicates)
rr = lordsrecords()
rr.lordrec.extend(rr1)

# merge in records from second list
for nr in rr2:
	rr.AddRecord(nr)

# merge in and over-write fields from extendednames list
rr3 = LoadExtendedNames('../rawdata/lords', 'Lords2003-11-26.html')
for nr in rr3:
	rr.AddExtnameRecord(nr)

# get the list sorted
rr.lordrec.sort(lrsort)

# write out the file
lordsxml = open('all-lords.xml', "w")
lordsxml.write("""<?xml version="1.0" encoding="ISO-8859-1"?>
<publicwhip>

<lord
	id="uk.org.publicwhip/lord/0"
	house="lords"
	title="Queen" lordname="Elizabeth" lordofname="Windsor"
	frontnames=""
	peeragetype="Monarch" affiliation="Crown"
	fromdate="1954-01-30"
	source=""
	comment=""
/>

""")


lid = 1
for r in rr.lordrec:
	r.lid = lid
	r.OutRecord(lordsxml)
	lid += 1

lordsxml.write("""

<lordalias fullname="The Lord Bishop of Salisbury" alternate="Lord Bishop of Salisbury" />
<lordalias fullname="The Lord Bishop of London" alternate="Lord Bishop of London" />
<lordalias fullname="St. Edmundsbury and Ipswich" alternate="Edmundsbury and Ipswich" />
""")

lordsxml.write("\n</publicwhip>\n")
lordsxml.close()


