<?php 
    include "account/user.inc";
    if (!user_isloggedin())
        include "cache-begin.inc"; 
?>
<?php
# $Id: division.php,v 1.30 2004/04/07 17:33:53 frabcus Exp $
# vim:sw=4:ts=4:et:nowrap

# The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

    include "db.inc";
    include "account/database.inc";
    $db = new DB(); 

    $date = db_scrub($_GET["date"]);
    $div_no = db_scrub($_GET["number"]);

    $show_all = false;
    if ($_GET["showall"] == "yes")
        $show_all = true;
    $all_similars = false;
    if ($_GET["allsimilars"] == "yes")
        $all_similars = true;

    $row = $db->query_one_row("select pw_division.division_id, division_name,
            source_url, rebellions, turnout, notes, motion, debate_url from pw_division,
            pw_cache_divinfo where pw_division.division_id =
            pw_cache_divinfo.division_id and division_date = '$date'
            and division_number='$div_no'");
    $div_id = $row[0];
    $name = $row[1];
    $source = $row[2];
    $rebellions = $row[3];
    $turnout = $row[4];
    $notes = $row[5];
    $motion = $row[6];
    $debate_url = $row[7];
    $prettydate = date("j M Y", strtotime($date));
    $div_no = html_scrub($div_no); 
    $this_anchor = "division.php?date=" . urlencode($date) .  "&number=" . urlencode($div_no); 
    $title = "$name - $prettydate - Division No. $div_no";
    include "header.inc";

    print '<p><a href="#motion">Motion</a>';
	print ' | ';
	print '<a href="#summary">Party Summary</a>';
	print ' | ';
    if (!$show_all)
        print '<a href="#rebels">Rebel Voters</a>'; 
    else    
    {
        print '<a href="#voters">Voter List</a>'; 
    	print ' | ';
        print '<a href="#nonvoters">Non-Voter List</a>'; 
    }
    
#	print ' | ';
#	print '<a href="#similar">Similar Divisions</a>'; 
    
    # Dream MP feature
    function vote_value($value, $curr)
    {
        $ret = "value=\"" . html_scrub($value) . "\" ";
        if ($value == $curr)
        {
            $ret .= "selected ";
        }
        return $ret;
    }

    if (user_isloggedin())
    {
        $submit=mysql_escape_string($_POST["submit"]);
        
        print "<div class=\"tablerollie\">";
        print "<span class=\"ptitle\">Dream MP</span>";

        $query = "select rollie_id, name, description from 
            pw_dyn_rolliemp where user_id = '" . user_getid() . "'";

        print "<p>Vote in this division for your dream MPs.</p>";
        print '<FORM ACTION="division.php?date=' . urlencode($date) . '&number=' . urlencode($div_no) . '" METHOD="POST">';
        print '<table>';
        $db->query($query);
        $rowarray = $db->fetch_rows_assoc();
        
        foreach ($rowarray as $row)
        {
            # find dream MP vote
            $query = "select vote from pw_dyn_rollievote where 
                    division_date = '$date' and division_number = '$div_no' and
                    rolliemp_id = '" . $row['rollie_id'] . "'";
            $db->query($query);
            if ($db->rows() == 0)
                $vote = "--";
            else
            {
                $qrow = $db->fetch_row_assoc();
                $vote = $qrow['vote'];
            }
            # alter dream MP's vote if they submitted form
            if ($submit)
            {
                $changedvote = mysql_escape_string($_POST["vote" . $row['rollie_id']]);
                if ($changedvote != $vote)
                {
                    print "<tr><td colspan=2><div class=\"error\">";
                    if ($changedvote == "--")
                    {
                        $query = "delete from pw_dyn_rollievote "  .
                            " where rolliemp_id='" . $row['rollie_id'] . "' and " . 
                            "division_date = '$date' and division_number = '$div_no'";
                        $db->query($query);
                        if ($db->rows() == 0)
                            print "Error changing '" . html_scrub($row['name']) . "' to non-voter (no change)";
                        elseif ($db->rows() > 1)
                            print "Error changing '" . html_scrub($row['name']) . "' to non-voter (too many rows)";
                        audit_log("Changed '" . $row['name'] . "' to non-voter for division " . $date . " " . $div_no);
                        print html_scrub("Changed '" . $row['name'] . "' to non-voter");
                    }
                    else
                    {
                        $query = "update pw_dyn_rollievote set vote='" . $changedvote . "'" . 
                            " where rolliemp_id='" . $row['rollie_id'] . "' and " . 
                            "division_date = '$date' and division_number = '$div_no'";
                        if ($vote == "--")
                        {
                            $query = "insert into pw_dyn_rollievote (vote, rolliemp_id, division_date,
                                    division_number) values ('" . $changedvote . "', ".
                                "'" . $row['rollie_id'] . "', '$date', '$div_no')";
                        }
                        $db->query($query);
                        if ($db->rows() == 0)
                            print "Error changing '" . html_scrub($row['name']) . "' to " . $changedvote . " (no change)";
                        elseif ($db->rows() > 1)
                            print "Error changing '" . html_scrub($row['name']) . "' to " . $changedvote . " (too many rows)";
                        audit_log("Changed '" . $row['name'] . "' to " . $changedvote . " for division " . $date . " " . $div_no);
                        print html_scrub("Changed '" . $row['name'] . "' to " . $changedvote);
                    }
                    print "</div></td></tr>";
                    $vote = $changedvote;
                }
            }
            # display dream MP vote
            print "<tr><td>\n";
            print '<a href="dreammp.php?id=' . $row['rollie_id'] . '">' . html_scrub($row['name']) . "</a>: \n";
            print "</td><td>\n";
            print ' <select name="vote' . $row['rollie_id'] . '" size="1">
                                <option ' . vote_value("aye", $vote) . '>Aye</option>
                                <option ' . vote_value("no", $vote) . '>No</option>
                                <option ' . vote_value("both", $vote) . '>Abstain</option>
                                <option ' . vote_value("--", $vote) . '>Non-voter</option>
                            </select></p>';
            print '</td></tr>';
        }
        print "<tr><td>\n";
        print ' <a href="account/adddream.php">[Add new dream MP]</a>';
        print "</td><td>\n";
        if (count ($rowarray) > 0)
            print '<INPUT TYPE="SUBMIT" NAME="submit" VALUE="Update Votes">';
        print '</td></tr>';
        print '</table>';
        print '</FORM>';
        print "</div>";
    }
    else
    {
/*       
        print "<p>Have a strong view on this division?  <a href=\"account/adddream.php\">Create your
            own dream MP</a>, and have them vote how you would like them to have voted.  Essential
            for any campaigning organisation, parliamentary candidate, or just for fun.";
        print "<p>Already have a dream MP?  <a href=\"account/settings.php\">Login here</a>";
        */

    }
 
    # Summary
	print "<h2>Summary</h2>";

    $ayes = $db->query_one_value("select count(*) from pw_vote
        where division_id = $div_id and vote = 'aye'");
    $noes = $db->query_one_value("select count(*) from pw_vote
        where division_id = $div_id and vote = 'no'");
    $boths = $db->query_one_value("select count(*) from pw_vote
        where division_id = $div_id and vote = 'both'");
    $tellers = $db->query_one_value("select count(*) from pw_vote
        where division_id = $div_id and (vote = 'tellaye' or vote = 'tellno')");
    print "<br>On $prettydate, $turnout MPs voted in division no. $div_no in the House of Commons.  
        <br>Subject was '$name'
        <br>Votes were $ayes aye, $noes no, $boths both, $tellers tellers.  
        There were $rebellions rebellions against majority party vote.";

    if ($debate_url == "")
    {
        $debate_url = $source;
        $source = "";
    }
    print "<br><a href=\"$debate_url\">Read the full debate</a> leading up to this division";
    if ($source != "")
        print ", <a href=\"$source\">check division listing</a>";
    print " (on the Parliament website)";
    print "$notes";
    
    print "<h2><a name=\"motion\">Motion</a></h2> <p>Procedural text extracted from the debate,
    so you can try to work out what 'aye' (for the motion) and 'no' (against the motion) meant.
    This is for guidance only, irrelevant text may be shown, crucial text may
    be missing.</p>";
    print "<div class=\"motion\">$motion";
    print "</div>\n";

    # Work out proportions for party voting (todo: cache)
    $db->query("select party, total_votes from pw_cache_partyinfo");
    $alldivs = array();
    while ($row = $db->fetch_row())
    {
        $alldivs[$row[0]] = $row[1];
    }
    $alldivs_total = array_sum(array_values($alldivs));

    # Table of votes by party
    $db->query("select pw_mp.party, count(*), vote, whip_guess from pw_vote,
        pw_mp, pw_cache_whip where pw_vote.division_id = $div_id and
        pw_vote.mp_id = pw_mp.mp_id and pw_cache_whip.division_id =
        pw_vote.division_id and pw_cache_whip.party = pw_mp.party group
        by pw_mp.party, vote order by party, vote");
    print "<h2><a name=\"summary\">Party Summary</a></h2>";
    print "<p>Votes by party, red entries are votes against the majority for that party.  ";
    print "
    <div class=\"tableexplain\">
    <span class=\"ptitle\">What is Tell?</span> 
    '+1 tell' means that in addition one member of that party was a
    teller for that division lobby. Tellers are usually whips, or else
    particularly support the vote they tell for.</p>
    <p>
    <span class=\"ptitle\">What are Boths?</span> An MP can vote both
    aye and no in the same division. The <a href=\"boths.php\">boths
    page</a> explains this, and lists all cases of it happening.
    <p>
    <span class=\"ptitle\">What is Abstain?</span> Abstentions are
    calculated from the expected turnout, which is statistical based on
    the average proporionate turnout for that party in all divisions. A
    negative abstention indicates that more members of that party than
    expected voted; this is always relative, so it could be that another
    party has failed to turn out <i>en masse</i>.</p>
    </div>";

    # Precalc values
    $ayes = array();
    $noes = array();
    $boths = array();
    $tellayes = array();
    $tellnoes = array();
    $whips = array();
    $prettyrow = 0;
    while ($row = $db->fetch_row())
    {
        $party = $row[0];
        $count = $row[1];
        $vote = $row[2];
        $whip = $row[3];

        if ($vote == "aye")
        {
            $ayes[$party] += $count;
        }
        else if ($vote == "no")
        {
            $noes[$party] += $count;
        }
        else if ($vote == "both")
        {
            $boths[$party] += $count;
        }
        else if ($vote == "tellaye")
        {
            $tellayes[$party] += $count;
        }
        else if ($vote == "tellno")
        {
            $tellnoes[$party] += $count;
        }
        else
        {
            print "Unknown vote type: " + $vote;
        }

        $whips[$party] = $whip;
    }

    # Make table
    print "<table><tr class=\"headings\"><td>Party</td><td>Ayes</td><td>Noes</td>";
    print "<td>Both</td>";
#    print "<td>Tell<br>Ayes</td><td>Tell<br>Noes</td>";
    print "<td>Turnout</td>";
    print "<td>Expected</td><td>Abstain</td></tr>";
    $allparties = array_keys($alldivs);
    usort($allparties, strcasecmp);
    $votes = array_sum(array_values($ayes)) +
        array_sum(array_values($noes)) + array_sum(array_values($boths)) +
        array_sum(array_values($tellayes)) + array_sum(array_values($tellnoes));
    if ($votes <> $turnout)
    {
        print "<p>Error $votes <> $turnout\n";
    }
    foreach ($allparties as $party)
    {
        $aye = $ayes[$party];
        $no = $noes[$party];
        $both = $boths[$party];
        $tellaye = $tellayes[$party];
        $tellno = $tellnoes[$party];
        if ($aye == "") { $aye = 0; }
        if ($no == "") { $no = 0; }
        if ($both == "") { $both = 0; }
        $whip = $whips[$party];
        $total = $aye + $no + $both + $tellaye + $tellno;
        $classaye = "normal";
        $classno = "normal";
        if ($whip == "aye") { if ($no + $tellno > 0) { $classno = "rebel";} ;} else { $classno = "whip"; }
        if ($whip == "no") { if ($aye + $tellaye> 0) { $classaye = "rebel";} ;} else { $classaye = "whip"; }

        $classboth = "normal";
        if ($both > 0) { $classboth = "important"; }

        $alldiv = $alldivs[$party];
        $expected = round($votes * ($alldiv / $alldivs_total), 0);
        $abstentions = round($expected - $total, 0);
        $classabs = "normal";
        if (abs($abstentions) >= 2) { $classabs = "important"; }
        
        if ($tellaye > 0 or $tellno > 0 or $aye > 0 or $no > 0 or $both > 0 or $abstentions >= 2)
        {
            if ($tellaye > 0)
                $aye .= " (+" . $tellaye . " tell)";
            if ($tellno > 0)
                $no .= " (+" . $tellno . " tell)";

            $prettyrow = pretty_row_start($prettyrow);        
            print "<td>" . pretty_party($party) . "</td>";
            print "<td class=\"$classaye\">$aye</td>";
            print "<td class=\"$classno\">$no</td>";
            print "<td class=\"$classboth\">$both</td>";
#            print "<td>$tellaye</td>";
 #           print "<td>$tellno</td>";
            print "<td>$total</td>";
            print "<td>$expected</td>";
            print "<td class=\"$classabs\">$abstentions</td>";
            print "</tr>";
        }
    }
    print "</table>";

    $mps = array(); 

    function vote_table($div_id, $db, $date, $show_all, $query)
    {
        # Table of MP votes
#        print $query;
        $db->query($query);
#        print " ROWS " . $db->rows() . " \n";

        global $mps;

        print "<table class=\"votes\"><tr class=\"headings\"><td>MP</td><td>Constituency</td><td>Party</td><td>Vote</td></tr>";
        $prettyrow = 0;
        while ($row = $db->fetch_row())
        {
            array_push($mps, $row[5]);
            $class = "";
            if ($row[4] == "")
                $row[4] = "nonvoter";
            $nt4 = str_replace("tell", "", $row[4]);
            $nt6 = str_replace("tell", "", $row[6]);
            if ($show_all && $nt6 != $nt4 && $nt6 <> "unknown" && $nt4 <> "both" && $nt4 <> "nonvoter")
                $class = "rebel";
            if ($nt4 == "both")
                $class = "both";
            $prettyrow = pretty_row_start($prettyrow, $class);
            print "<td><a href=\"mp.php?firstname=" . urlencode($row[0]) .
                "&lastname=" . urlencode($row[1]) . "&constituency=" .
                urlencode($row[7]) . "\">$row[2] $row[0] $row[1]</a></td>
                <td>$row[7]</td><td>" . pretty_party($row[3]) . "</td><td>$row[4]</td>";
            print "</tr>";
        }
        if ($db->rows() == 0)
        {
            $prettyrow = pretty_row_start($prettyrow, "");
            print "<td colspan=4>no rebellions</td></tr>\n";
        }
        print "</table>";
    }
    
    $query = "select first_name, last_name, title, pw_mp.party,
        vote, pw_mp.mp_id, whip_guess, constituency from pw_mp, pw_vote, pw_cache_whip 
        where pw_vote.mp_id = pw_mp.mp_id
            and pw_cache_whip.party = pw_mp.party
            and pw_vote.division_id = $div_id
            and pw_cache_whip.division_id = $div_id
            and entered_house <= '$date' and left_house >= '$date' and vote is not null ";
    if (!$show_all)
    {
        $query .= "and vote is not null and whip_guess <> 'unknown' and vote <>
            'both' and whip_guess <> replace(vote, 'tell', '')";
        print "<h2><a name=\"rebels\">Rebel Voters</a></h2>
        <p>MPs for which their vote in this division differed from
        the majority vote of their party.";
    }
    else
    {
        print "<h2><a name=\"voters\">Voter List</a></h2>
            <p>Vote of each MP. Those where they voted differently from
            the majority in their party are marked in red.";
    }
    $query .= "order by party, last_name, first_name desc";
    vote_table($div_id, $db, $date, $show_all, $query);
    if (!$show_all)
    {
        print "<p><a href=\"$this_anchor&showall=yes#voters\">Show detailed voting records - 
        all MPs who voted in this division, and all MPs who did not</a>";
    }
    else
    {
        print "<p><a href=\"$this_anchor#rebels\">Show only MPs who rebelled in this division</a>";
    }

    if ($show_all)
    {
        $mp_not_already = "mp_id<>" . join(" and mp_id<>", $mps);
        $query = "select first_name, last_name, title, pw_mp.party,
            \"\", pw_mp.mp_id, \"\", constituency from pw_mp where
                entered_house <= '$date' and left_house >= '$date' and 
                ($mp_not_already)";
        $query .= "order by party, last_name, first_name desc";
        print "<h2><a name=\"nonvoters\">Non-Voter List</a></h2>
            <p>MPs who did not vote in the division.  There are many
            reasons an MP may not vote - read this
            <a href=\"faq.php#clarify\">clear explanation</a> of
            attendance to find some reasons.  Note that MPs who voted both for
            and against are listed in the table above, not this table.  Search 
            for \"both\" to find them.";
        vote_table($div_id, $db, $date, $show_all, $query);
        print "<p><a href=\"$this_anchor#rebels\">Show only MPs who rebelled in this division</a>";
    }

/*    print "<h2><a name=\"similar\">Similar Divisions</a></h2>";
    print "<p>Shows which divisions had similar rebels to this one.
    Distance is measured from near 0 (many common rebels) to 1 (no
    common rebels).  Only MPs who voted in both divisions are counted.";

    print "<table class=\"votes\">\n";
    $query = 
        "select pw_division.division_id, division_number, division_date, division_name, 
       rebellions, turnout, distance from pw_division, pw_cache_divinfo, pw_cache_divdist where
        pw_division.division_id = pw_cache_divinfo.division_id and
        (pw_division.division_id = pw_cache_divdist.div_id_1
        and pw_cache_divdist.div_id_2 = $div_id
        and pw_cache_divdist.div_id_1 <> $div_id)
        ";

    print "<tr class=\"headings\"><td>Number</td><td>Date</td><td>Subject</td><td>Distance</td><td>Rebellions</td><td>Turnout</td></tr>";
    $prettyrow = 0;

    $db->query($query . " and distance = 0");
    $same_rebels = $db->rows();

    $limit = "";
    if (!$all_similars)
        $limit .= " limit 0,10"; 
    $db->query($query . " order by distance / (rebellions + 1) $limit");

    while ($row = $db->fetch_row())
    {
        $prettyrow = pretty_row_start($prettyrow);

        $class = "";
        if ($row[4] >= 10)
        {
            $class = "rebel";
        }
        $prettyrow = pretty_row_start($prettyrow, $class);
        print "<td>$row[1]</td> <td>$row[2]</td> <td><a
            href=\"division.php?date=" . urlencode($row[2]) . "&number="
            . urlencode($row[1]) . "\">$row[3]</a></td> 
            <td>$row[6]</td> 
            <td>$row[4]</td> 
            <td>$row[5]</td>";
        print "</tr>\n";
    }
    if ($db->rows() == 0)
    {
        $prettyrow = pretty_row_start($prettyrow, "");
        print "<td colspan=6>no common MPs to compare</td></tr>\n";
    }
    print "</table>\n";
    if (!$all_similars)
    {
        print "<p><a href=\"$this_anchor&allsimilars=yes#similar\">Show all divisions in order of similarity to this one</a>";
        if ($same_rebels > 4)
            print " ($same_rebels divisions had exactly the same rebels as this one)";
    }
    else
    {
        print "<p><a href=\"$this_anchor#similar\">Show only a few similar divisions</a>";
    }
*/
?>

<?php include "footer.inc" ?>
<?php 
if (!user_isloggedin())
    include "cache-end.inc"; 
?>
