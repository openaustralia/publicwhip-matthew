#! /usr/bin/python2.3

# Run the script with --help to see command line options

from optparse import OptionParser
from createhansardindex import UpdateHansardIndex
from pullgluepages import PullGluePages
from runfilters import RunFiltersDir, RunDebateFilters, RunWransFilters

# Parse the command line parameters

parser = OptionParser()

parser.add_option("--network",
                  action="store_true", dest="network", default=False,
                  help="update Hansard page index, and download new raw pages")
parser.add_option("--wrans",
                  action="store_true", dest="wrans", default=False,
                  help="process Written Answers into XML files")
parser.add_option("--debates",
                  action="store_true", dest="debates", default=False,
                  help="process Debates into XML files")

parser.add_option("-f", "--from", dest="datefrom", metavar="date", default="1000-01-01",
                  help="date to process back to, default is start of time")
parser.add_option("-t", "--to", dest="dateto", metavar="date", default="9999-12-31",
                  help="date to process up to, default is present day")
parser.add_option("-d", "--date", dest="date", metavar="date", default=None,
                  help="date to process (overrides --from and --to)")

(options, args) = parser.parse_args()
if (options.date):
        options.datefrom = options.date
        options.dateto = options.date

# Do the work

if options.network:
        UpdateHansardIndex()
        PullGluePages(options.datefrom, options.dateto)
if options.wrans:
        RunFiltersDir(RunWransFilters, 'wrans', options.datefrom, options.dateto)
if options.debates:
        RunFiltersDir(RunDebateFilters, 'debates', options.datefrom, options.dateto)


