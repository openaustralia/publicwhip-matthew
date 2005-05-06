<?php require_once "common.inc";
# $Id: faq.php,v 1.42 2005/05/06 02:23:42 frabcus Exp $

# The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.
$paddingforanchors = true; $title = "Frequently Asked Questions"; include "header.inc" 
?>

<a href="http://www.newstatesman.com/newmedia">
<img align="right" src="images/nmawinnerbutton.gif" border="0"></a>

<p>
<ul>
<li><a href="#whatis">What is the Public Whip?</a> <br/>
<li><a href="#jargon">First, can you explain "division" and other political jargon?</a> <br/>
<li><a href="#how">How does the Public Whip work?</a> <br/>
<li><a href="#timeperiod">What time period does it cover?</a> <br/>
<li><a href="#wrans">Where have the Written Answers gone?</a><br/>

<br>
<li><a href="#clarify">What do the "rebellion" and "attendance" figures mean exactly?</a> <br/>
<li><a href="#freevotes">Why do you incorrectly say people are rebels in free votes?</a> <br/>
<li><a href="#abstentions">How do you estimate abstentions?</a> <br/>

<br>
<li><a href="#legal">Legal question, what can I use this information for?</a> <br/>
<li><a href="#playwith">Can I play with the software?</a> <br/>
<li><a href="#whyfree">Why are you giving everything away for free?</a> <br/>
<li><a href="#rss">Are there any RSS syndication feeds?</a> <br/>
<li><a href="#spreadsheet">Where is the data in spreadsheet file format or in XML?</a> <br/>
<li><a href="#patents">What is the fuss about software patents?</a> <br/>
<li><a href="#election">What did you do for the 2005 election?</a> <br/>

<br>
<li><a href="#organisation">What organisation is behind the Public Whip?</a> <br/>
<li><a href="#keepup">How can I keep up with what you are doing?</a> <br/>
<li><a href="#help">Can I help with the project?</a> <br/>
<li><a href="#email">Where can I email?</a> <br/>
</ul>
</p>

<h2><a name="whatis">What is the Public Whip?</a></h2>
<p>The Public Whip is a project to watch Members of the United Kingdom
Parliament, so that the public (people like us) can better influence their
voting patterns.

<h2><a name="jargon">First, can you explain "division" and other political jargon?</a></h2>
<p>The House of Commons <i>divides</i> many times each week into those who
vote "aye" ("yes", for the motion) and those who vote "no" (against the
motion).  Each political party has <i>whips</i> who try to make their
MPs (Members of Parliament) vote for the party line.  Sometimes an MP
<i>rebels</i> by voting against the party whip.  A <i>teller</i> is
an MP involved in the counting of the vote.  For more information on all these
terms, see the 
<a href="http://www.parliament.uk/parliamentary_publications_and_archives/factsheets/p09.cfm">
Parliament factsheet on divisions</a>.

<h2><a name="how">How does the Public Whip work?</a></h2>
<p>All the House of Commons debate transcripts (<a href="http://www.parliament.the-stationery-office.co.uk/pa/cm/cmhansrd.htm">Hansard</a>) back to
1988 are published electronically on the World Wide Web.  We've written
a program to read it for you and separate out all the records of
voting.  This information has been added into an online database which you can
access.

<h2><a name="timeperiod">What time period does it cover?</a></h2>
<p>Voting data extends back across two parliaments to the May 1997 General
Election, although there are a few divisions missing in the 1997
parliament.  New divisions usually appear in Public Whip the next morning, but
sometimes take a day or two longer.  We give no warranty for the data; there
may be factual inaccuracies.  <a href="mailto:team@publicwhip.org.uk">Let us
know</a> if you find any.

<?php
    include "db.inc";
    include "parliaments.inc";
    $db = new DB(); 

    $div_count = $db->query_one_value("select count(*) from pw_division");
    $mp_count = $db->query_one_value("select count(*) from pw_mp");
    $vote_count = $db->query_one_value("select count(*) from pw_vote");
    $vote_per_div = round($vote_count / $div_count, 2);
    $db->query("select count(*) from pw_mp group by party"); $parties = $db->rows();
    $rebellious_votes = $db->query_one_value("select sum(rebellions) from pw_cache_mpinfo");
    $rebelocity = round(100 * $rebellious_votes / $vote_count, 2);
    $attendance = round(100 * $vote_count / $div_count / ($mp_count / parliament_count()), 2);
?>

<p>Some numeric statistics: The database contains <?=$mp_count?> 
MP records from <?=$parties?> parties. There are <?=$div_count?> divisions
which have been counted.  A mean of <?=$vote_per_div?> MPs voted
in each division.  In total <?=$vote_count?> votes were cast, of which
<?=$rebellious_votes?> were against the majority vote for their party.
That's an overall <?=$attendance?>% attendance rate and <?=$rebelocity?>%
rebellion rate.

<h2><a name="wrans">Where have the Written Answers gone?</a></h2>
<p>A <i>Written Answer</i> is a Government reply to a question from an MP,
usually containing detailed factual information.  These used to be available
on The Public Whip site, but are now on our sister site 
<a href="http://www.theyworkforyou.com">TheyWorkForYou.com</a>.  Your
old links to Written Answers on The Public Whip are now redirected to
the same Written Answer on TheyWorkForYou.com.  Let us know if you have
any trouble with this.


<h2><a name="clarify">What do the "rebellion" and "attendance" figures mean exactly?</a></h2>
<p>The apparent meaning of the data can be misleading, so try not to
jump to conclusions about your MP.

<p>"Attendance" is for voting or telling in divisions. An MP may have a
low attendance because they have abstained a lot, have ministerial or
other duties, or they are the speaker.  Perhaps they consider each
division carefully, and only vote when they know about the subject.  A
full list of reasons for low attendance can be found in the Divisions
section on page 11 of <a
href="http://www.parliament.uk/commons/lib/research/rp2003/rp03-032.pdf">a
House of Commons library research paper</a>.  Note also that the Public
Whip does not currently record if a member spoke in the debate but did
not vote.

<p>"Rebellion" on this website means a vote against the majority vote by
members of the MP's party.  Unfortunately this will indicate that many members
have rebelled in a free vote.  Until precise data on when and how strongly each
party has whipped is made available, there is no true way of identifying a
"rebellion".  We know of no heuristics which can reliably detect free votes,
perhaps you can think of some and let us know.  See also the next question.

<h2><a name="freevotes">Why do you incorrectly say people are rebels in free votes?</a></h2>

<p>The short answer to this question is <a href="http://www.theyworkforyou.com/debate/?id=2005-02-07.1200.2">succinctly given by the Speaker</a>.  Here is
the long answer.

<p>There is no official, recorded data about the party whip.  At the moment
we guess based on the majority vote by MPs for each party.  In order to
correctly identify rebels, we need to know each party's whip in each division.
There are two ways this could be officially recorded.

<ol>
<li>Hansard clerks could record the whip.  They could either be officially told
the whip by each party's whips' office, or they could deduce it from the
presence of offical whips.  The whip would then be written in Hansard next to
the division listing.
<li>Each whips' office could publish their official whip on their website after
each vote.  If you are a member of a political party, and want to fix the Public
Whip site, lobby them to do this, then let us know.
</ol>

<p>Parties can't have it both ways&mdash;complain that we don't know what the whip is,
and at the same time not tell us.  There is no contradiction in admitting the whip
exists and recording it officially&mdash;after all some whips <a href="http://www.cabinet-office.gov.uk/civilservice/min-mp-pay/min_sal_tables.htm">are paid a salary by the taxpayer</a> so
there is a precedent for admitting they exist.

<h2><a name="abstentions">How do you estimate abstentions?</a></h2>

<p>It isn't possible for an MP to abstain in the UK parliament.  They can
however not vote at all.  We try to detect massive low turnouts on the division
page by estimating abstentions for each party.

<p>They are calculated from the expected turnout, which is statistical based on
the average proportionate turnout for that party in all divisions. A negative
abstention indicates that more members of that party than expected voted; this
is always relative, so it could be that another party has failed to turn out
<i>en masse</i>.</p>
 
<p>Sometimes MPs also indicate abstention by <a href="boths.php">voting both
aye and no</a>.  

<h2><a name="legal">Legal question, what can I use this information for?</a></h2>

<p>Anything.  The contents of this website are copyrighted by us, and
except for the software you can use them how you like.  This data is
distributed in the hope that it will be useful, but without any
warranty; without even the implied warranty of
merchantability or fitness for a particular purpose.

<p>Amongst other things, this means that if you use it, you should
double check the information. It may be nonsense.  If you can't be
bothered, at least do some cursory cross checking.  Whichever way, use
is at your own risk.  Of course we'd rather you helped us fix the
software and correct any errors, so <a
href="mailto:team@publicwhip.org.uk">send us an email</a> if you find
inaccuracies.

<p>If you reproduce this information, or derive any interesting 
results from it, we ask you to refer your readers to
www.publicwhip.org.uk.  This way they can use and contribute themselves.

<h2><a name="playwith">Can I play with the software?</a></h2>

<p> Sure.  All the software we've written is free (libre and gratuit), protected by the 
<a href="GPL.php">GNU General Public License</a>.  It's not complicated,
anyone can have a go running them.  But there's only a point in doing
this if you are going to change it as otherwise you will see the same
results.  For more details go to the special <a
href="project/code.php">coding section</a> of this website.

<h2><a name="whyfree">Why are you giving everything away for free?</a></h2>

<p>We're not; we're letting you take copies.  Whatever you do, we still 
have our computers, all the programs, and our domain name.  The more 
people who are playing with this sort of thing, the more cool ideas can 
come out of it. 

<p>We could wrap it up as a service and sell it to political lobbying
organizations for cash.  This would, however, be pointless since it
would take away the notion of the public having access to it.  All that
would happen is that the people who are already organized influentially
would retain all the power but would have slightly better software
(which they probably have already). 

<h2><a name="rss">Are there any RSS syndication feeds?</a></h2>

<p> First an explanation.  RSS is a way to let you easily read news from lots
of different sources.  You need a special program called a newsreader to do
this.  On the BBC website, there's a <a href="http://news.bbc.co.uk/2/hi/help/3223484.stm">full
desription</a> of how to do it.  We provide the following RSS feeds:

<p> 
<a href="/feeds/interestingdivisions.xml">interestingdivisions.xml</a> &mdash; Find out every time there are more than 10 "rebellions" in a division.
<br><a href="/feeds/alldivisions.xml">alldivisions.xml</a> &mdash; Keep on top of every division in Parliament, after it happens.

<h2><a name="spreadsheet">Where is the data in spreadsheet file format or in XML?</a></h2>

<p> Take a look at our <a href="project/data.php">Raw Data</a> page.

<h2><a name="patents">What is the fuss about software patents?</a></h2>
<p>A new European directive on software patents threatens the existence
of websites like The Public Whip.   We'd like your help to stop it.  <a
href="patents/index.html">For more information see here</a>.

<h2><a name="election">What did you do for the 2005 election?</a></h2>
<p>During the 2005 General Election campaign we ran a <a href="election.php">how
they voted quiz</a>.  You can still take it and link to the results.

<h2><a name="organisation">What organisation is behind the Public Whip?</a></h2>
<p>None.  It was started by just two guys <a href="http://www.flourish.org">Francis</a> and <a
href="http://www.goatchurch.org.uk">Julian</a> who had an idea and made it
happen.  <a href="http://www.knownoffender.net/">Giles</a> designed the
original look of the website.  We're hosted by the ever helpful and encouraging
<a href="http://www.mythic-beasts.com/">Mythic Beasts</a>.  These days lots
of other people help out with bits of code, writing and design.

<h2><a name="help">Can I help with the project?</a></h2>
<p>Sure!  Email <a href="mailto:team@publicwhip.org.uk">team@publicwhip.org.uk</a> to say 
you would like to help.  We always need help writing newsletters, improving
site usability, and with publicity and media.  As well as programmers, of
course!  Read our <a href="project/">project page</a> and see the Public Whip section of <a
href="http://www.mysociety.org/cgi-bin/moin.cgi/VolunteerTasks">VolunteerTasks</a>
on the mySociety wiki for some specific things we need doing.  
</p>

<h2><a name="keepup">How can I keep up with what you are doing?</a></h2>
<p><a href="account/register.php">Subscribe to our newsletter!</a>  It's
at most once a month, and has interesting news and articles
relating to the project. You can 
<a href="http://www.publicwhip.org.uk/forum">chat with other users</a>
on our forum.

<h2><a name="email">Where can I email?</a></h2>
<p>If you have any problems, comments, queries, suggestions or offers of help about this website
then email <a
href="mailto:team@publicwhip.org.uk">team@publicwhip.org.uk</a>.  If you would like
to contact your MP about something that concerns you, then use <a href="http://www.writetothem.com">WriteToThem</a>.

<?php include "footer.inc" ?>

