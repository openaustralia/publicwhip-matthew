#! /usr/bin/python2.3

import sys
import re
import string
import cStringIO

import mx.DateTime

from parlphrases import parlPhrases
from miscfuncs import FixHTMLEntities
from miscfuncs import FixHTMLEntitiesL
from miscfuncs import SplitParaIndents

from filterwransemblinks import rreglink
from filterwransemblinks import rregemail

from filterwransemblinks import rehtlink
from filterwransemblinks import ConstructHTTPlink


redatephraseval = re.compile('(?:(?:%s) )?(\d+ (?:%s)( \d+)?)' % (parlPhrases.daysofweek, parlPhrases.monthsofyear))
reoffrepw = re.compile('<i>official(?:</i> <i>| )report,?</i>,? c(?:olumns?)?\.? (\d+(?:&#150;\d+)?[WS]*)(?i)')
restandingo = re.compile("Standing Order No.\s*(\d+(?:\s*\(\d+\))?(?:\s*\([a-z]\))?)\s*\(([^\(\)]*(?:\([^\(\)]*\))?)\)")
restandingomarg = re.compile("Standing Order No")
reqnum = re.compile("\s*\[(\d+)\]\s*$")

class PhraseTokenize:

	def RawTokensN(self, qs, stex):
		self.toklist.append( ('', '', FixHTMLEntities(stex, stampurl=(qs and qs.sstampurl))) )
		return



	# find and clean-up http links
	def HTTPlinkToken3(self, qs, stex):
		nextfunc = self.RawTokensN

		qhttp = rehtlink.search(stex)
		if not qhttp:
			return nextfunc(qs, stex)

		qstrlink = ConstructHTTPlink(qhttp.group(1), qhttp.group(2), qhttp.group(3))

		nextfunc(qs, stex[:qhttp.span(0)[0]])
		self.toklist.append( ('a', ' href="%s"' % qstrlink, FixHTMLEntities(qhttp.group(0)) ) )
		self.HTTPlinkToken3(qs, stex[qhttp.span(0)[1]:])


	# official report phrases
	# on 4 June 2003, <i>Official Report,</i> column 22WS
	# columns 687&#150;89W
	def OffrepTokens2(self, qs, stex):
		nextfunc = self.HTTPlinkToken3

		qoffrep = reoffrepw.search(stex)
		if not qoffrep:
			return nextfunc(qs, stex)

		# extract the proper column without the dash
		qcpart = re.match('(\d+)(?:&#150;(\d+))?([WS]*)(?i)$', qoffrep.group(1))
		if qcpart.group(2):
			qcpartlead = qcpart.group(1)[len(qcpart.group(1)) - len(qcpart.group(2)):]
			if string.atoi(qcpartlead) >= string.atoi(qcpart.group(2)):
				print qoffrep.group(0)
				raise Exception, ' non-following column leadoff '

		# this gives a link to the date.colnumW type show.
		qcolcode = qcpart.group(1) + string.upper(qcpart.group(3))
		offrepid = 'wrans/%s.%s' % (self.lastdate, qcolcode)

		nextfunc(qs, stex[:qoffrep.span(0)[0]])
		self.toklist.append( ('phrase', ' class="offrep" id="%s"' % offrepid, '<i>Official Report</i> Column %s' % qoffrep.group(1)) )
		self.OffrepTokens2(qs, stex[qoffrep.span(0)[1]:])


	def DateTokens1(self, qs, stex):
		nextfunc = self.OffrepTokens2
		qdateph = redatephraseval.search(stex)
		if not qdateph:
			return nextfunc(qs, stex)

		# find the date string and should append on the year if not there
		ldate = qdateph.group(1)
		if not qdateph.group(2):
			pass
		try:
			ldate = mx.DateTime.DateTimeFrom(ldate).date
		except:
			return self.RawTokensN(qs, stex)

		# output the three pieces
		nextfunc(qs, stex[:qdateph.span(0)[0]])

		# don't tokenize dates, but record them for the offrep tokens
		#self.toklist.append( ('phrase', ' class="date" id="%s"' % ldate, qdateph.group(0)) )
		self.toklist.append( ('', '', qdateph.group(0)) )

		self.lastdate = ldate
		self.DateTokens1(qs, stex[qdateph.span(0)[1]:])


	def StandingOrder1(self, qs, stex):
		nextfunc = self.DateTokens1
		qstandingoph = restandingo.search(stex)
		if not qstandingoph:
			if restandingomarg.search(stex):
				print "Marginal standing order "
				print stex
			return nextfunc(qs, stex)

		# this works well, except when two standing orders are quoted (number x and y)
		# should work out a standard hyperlink for them.
		#qstandingoph
		stando = qstandingoph.group(1)
		print "standing order: %s : %s " % (qstandingoph.group(1), qstandingoph.group(2))

		if restandingomarg.search(stex[:qstandingoph.span(0)[0]]):
			print "Marginal standing order "
			print stex

		# output the three pieces
		nextfunc(qs, stex[:qstandingoph.span(0)[0]])
		self.toklist.append( ('phrase', ' class="standing-order" code="%s"' % stando, qstandingoph.group(0)) )
		self.StandingOrder1(qs, stex[qstandingoph.span(0)[1]:])


	def __init__(self, qs, stex):
		self.lastdate = ''
		self.toklist = [ ]

 		self.rmqnum = reqnum.search(stex)
		if self.rmqnum:
			stex = stex[:self.rmqnum.span(0)[0]]

		# separate out any qnums at end of paragraph
		self.StandingOrder1(qs, stex)


	def GetPara(self, ptype, bBegToMove=False, bKillqnum=False):

		if (not bKillqnum) and self.rmqnum:
			self.rqnum = ' qnum="%s"' % self.rmqnum.group(1)
			print self.rqnum
		else:
			self.rqnum = ""


		if bBegToMove:
			res = [ '<p class="%s" pwmotiontext="yes">' % ptype ]
		elif ptype:
			res = [ '<p class="%s">' % ptype ]
		else:
			res = [ '<p%s>' % self.rqnum ]

		for tok in self.toklist:
			if tok[0]:
				res.append('<%s%s>' % (tok[0], tok[1]))
				res.append(tok[2])
				res.append('</%s>' % tok[0])
			else:
				res.append(tok[2])

		res.append('</p>')
		return string.join(res, '')



rewdslDUFF =  [ rreglink, rregemail,
		'\$?\d+(?:,\d+)*\.?\d*',
		'(?:[\w\'\-/%%]|&#\d+;)+',
		'\$\d+\.?\d*',
		'&quot;',
		'&amp;',
		'</?i>',
		'</?sup>',
		'\[\w+\]',
		'[();&".,:]',
		'\*+',
		'='
	  ]

#rewdsDUFF = re.compile('(%s)' % string.join(rewdsl, '|'))
#print rewds.findall('amounted to $2.479 billion.')
#sys.exit()

# this is highly experimental methods here, of tokenizing the words
def DUFFFFilterSentence(text):
	return
	swords = rewds.split(text)
	for sw in swords:
		if not re.match('\s*$', sw):
			if not rewds.match(sw):
				print text
				print sw
				sys.exit()

