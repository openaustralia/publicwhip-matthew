<?php require_once "common.inc";
    # $Id: mp.php,v 1.50 2005/02/18 19:43:41 frabcus Exp $

    # The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
    # This is free software, and you are welcome to redistribute it under
    # certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
    # For details see the file LICENSE.html in the top level of the source.

    include "cache-begin.inc";

    include "db.inc";
    $db = new DB();

	# standard decoding functions for the url attributes
	include "decodeids.inc";

	# [showall=yes]  [allfriends=yes]  [expand=yes]  [mpgen=byconstituency]
    $show_all = ($_GET["showall"] == "yes");
    $all_friends = ($_GET["allfriends"] == "yes");
    $expand = ($_GET["expand"] == "yes");
	$byconstituency = ($_GET["mpgen"] == "byconstituency");

	$mpid = decode_mpid($db, 0);
	$mpattr = get_mpid_attr($db, $mpid, $byconstituency);
    $this_anchor = "mp.php?".$mpattr["mpanchor"];


	# generate the header of this webpage
    $title = html_scrub("Voting Record - ".$mpattr['mpname']." MP, ".$mpattr['constituency']);
    include "header.inc";


	# internal page links
	print '<p>';
	print '<a href="#divisions">Interesting Divisions</a>';
	print ' | ';
	print '<a href="#friends">Possible Friends</a>';
	print ' | ';
	print '<a href="#dreammotions">Dream MP Comparisons</a>';

?>

<?
	# generate ministerial events (maybe events for general elections?)
 	$query = "SELECT dept, position, from_date, to_date
        	  FROM pw_moffice
			  WHERE pw_moffice.person = '".$mpattr["personid"]."'
        	  ORDER BY from_date DESC";
    $db->query($query);

    $events = array();
    $currently_minister = "";

	# it goes in reverse order
    while ($row = $db->fetch_row_assoc())
    {
        if ($row["to_date"] == "9999-12-31")
            $currently_minister = $row["position"].  ", " .  $row["dept"];
		else
            array_push($events, array($row["to_date"],   "Stopped being " .  $row["position"].  ", " . $row["dept"]));
        array_push($events, 	array($row["from_date"], "Became " .  $row["position"]. ", 		   " . $row["dept"]));
    }
	if ($byconstituency)  # clear if not going to be the same guy all the way through
		$events = array();

    print "<h2><a name=\"general\">General Information</a></h2>";

    if ($currently_minister)
        print "<p><b>".$mpattr['mpname']."</b> is currently <b>$currently_minister</b>.<br>
               MP for <b>".$mpattr['constituency']."</b>";
    else
        print "<p><b>".$mpattr['mpname']."</b> has been MP for <b>".$mpattr['constituency']."</b>";
	print " during the following periods of time during the last two parliaments:<br>";
	print "(Check out <a href=\"faq.php#clarify\">our explanation</a> of 'attendance'
            and 'rebellions', as they may not have the meanings you expect.)</p>";
?>

<?
    $prettyrow = 0;
    print "<table><tr class=\"headings\">";
    print "<td>Party</td>
            <td>From</td><td>To</td>
            <td>Rebellions (estimate)</td><td>Attendance (divisions)</td>
            <td>Teller</td></tr>";
    foreach ($mpattr['mpids'] as $mpid)
    {
		$query = "SELECT party, pw_mp.mp_id as mpid,
	        			rebellions, votes_attended, votes_possible,
	        			entered_house, left_house,
	        			entered_reason, left_reason,
	        			tells, person
				  FROM pw_mp, pw_cache_mpinfo
				  WHERE pw_mp.mp_id = pw_cache_mpinfo.mp_id and pw_mp.mp_id = $mpid";
	    $row = $db->query_one_row_assoc($query);

        $prettyrow = pretty_row_start($prettyrow);
        print "<td>".pretty_party($row['party'])."</td>\n";
		print "<td>".$row['entered_house']."</td>\n";
        #if ($row['left_house'] == "9999-12-31") { $row[12] = "still in office"; }
		print "<td>".$row['left_house']."</td>\n";
        print "<td class=\"percent\">".percentiserat($row['rebellions'], ' votes out of ', $row['votes_attended'])."</td>\n";
		print "<td class=\"percent\">".percentiserat($row['votes_attended'], ' votes out of ', $row['votes_possible'])."</td>\n";
        print "<td>".$row['tells']." times</td>\n";
        print "</tr>\n";
    }
    print "</table>";

    print "<p>";
    print "<a href=\"http://www.theyworkforyou.com/mp/?m=" .  $mpid. "\">";
    print "Performance data, recent speeches, and biographical links</a> ";
    print "at TheyWorkForYou.com.";
    print "<br> <a href=\"http://www.writetothem.com\">Contact your MP</a> with
    WriteToThem for free.  Find the <a
    href=\"http://www.parliament.uk/directories/hciolists/alms.cfm\">email
    address</a> of some MPs.";

?>


<?php
    if (!$show_all)
    {
        print "<h2><a name=\"divisions\">Interesting Divisions</a></h2>
        <p>Votes in parliament for which this MP's vote differed from the
        majority vote of their party (Rebel), or in which this MP was
        a teller (Teller) or both (Rebel Teller).  ";
        print "You can also <a href=\"$this_anchor&showall=yes#divisions\">see all divisions this MP voted in</a>.";
    }
    else
    {
        print "<h2><a name=\"divisions\">Divisions Attended</a></h2>
        <p>Divisions in which this MP voted.  The first column
        indicates if they voted against the majority vote of
        their party (Rebel), were a teller for that side (Teller)
        or both (Rebel Teller). ";
    }
    print " Also shows when the MP became or stopped being a paid minister. ";

    function print_event($event)
    {
        global $prettyrow;
        $prettyrow = pretty_row_start($prettyrow);
        print "<td>&nbsp;</td><td>&nbsp;</td>";
        print "<td>" . $event[0] . "</td>";
        print "<td colspan=7>" . $event[1] .  "</td></tr>\n";
    }


    $events_ix = 0;
	$qwrestrictions = "";
	if (!$showall)
		$qwrestrictions = "AND ((vote <> whip_guess and whip_guess <> 'unknown' and vote <> 'both')
							OR vote = 'tellaye' OR vote = 'tellno')";
    print "<table class=\"votes\">\n";
    foreach ($mpattr['mpids'] as $mpid)
    {
		$party = $mpattr['party'];   # this should change with the mpid


        # main set that grabs all the divisions and makes a coarse subsampling
        $query = "SELECT pw_division.division_id, division_number, division_date,
            			 division_name, source_url, vote, whip_guess,
						 rebellions
				  FROM pw_division, pw_vote, pw_cache_whip, pw_cache_divinfo
				  WHERE pw_vote.mp_id = $mpid
				  	AND pw_division.division_id = pw_vote.division_id and
            			pw_cache_whip.division_id = pw_division.division_id and
            			pw_cache_divinfo.division_id = pw_division.division_id and
            			pw_cache_whip.party = \"".$party."\"
						$qwrestrictions
					ORDER BY division_date DESC, division_number DESC";
        $db->query($query);

        print "<tr class=\"headings\">";
		print "<td>Role</td>";
        print "<td>No.</td><td>Date</td><td>Subject</td>";
        print "<td>Vote</td><td>$party Vote</td>";
        print "<td>Debate</td></tr>\n";
        $prettyrow = 0;
        while ($row = $db->fetch_row())
        {
            while ($events_ix < count($events) && $row[2] <= $events[$events_ix][0])
			{
                print_event($events[$events_ix]);
                $events_ix ++;
            }

            $class = "";
            $votedesc = "";
            if ($row[6] != "unknown")
            {
                $detelled = $row[5];
                if ($detelled == "tellaye") $detelled = "aye";
                if ($detelled == "tellno") $detelled = "no";

                if ($detelled != $row[6])
                {
                    $class .= "rebel";
                    $votedesc = "Rebel";
                }
                if ($row[5] == "tellaye" || $row[5] == "tellno")
                {
                    $class .= "teller";
                    if ($votedesc == "Rebel")
                        $votedesc = "Rebel Teller";
                    else
                        $votedesc = "Teller";
                }
            }
            if ($votedesc == "")
                $votedesc = "Loyal";

            $prettyrow = pretty_row_start($prettyrow);
            print "<td class=\"$class\">$votedesc</td>";
            print "<td>$row[1]</td> <td>$row[2]</td> <td><a
                href=\"division.php?date=" . urlencode($row[2]) . "&number="
                . urlencode($row[1]) . "\">$row[3]</a></td>
                <td>$row[5]</td><td>$row[6]</td>
                <td><a href=\"$row[4]\">Hansard</a></td>";
            print "</tr>\n";
        }
        if ($db->rows() == 0)
        {
            $prettyrow = pretty_row_start($prettyrow, "");
            if ($show_all)
                print "<td colspan=7>no votes</td></tr>\n";
            else
                print "<td colspan=7>no rebellions, never teller</td></tr>\n";
        }
    }
    while ($events_ix < count($events))
    {
        print_event($events[$events_ix]);
        $events_ix ++;
    }
    print "</table>\n";

    if (!$show_all)
        print "<p><a href=\"$this_anchor&showall=yes#divisions\">Show all divisions this MP voted in</a>";
    else
        print "<p><a href=\"$this_anchor#divisions\">Show only divisions MP rebelled in</a>";

    print "<h2><a name=\"friends\">Possible Friends</a></h2>";
    print "<p>Shows which MPs voted most similarly to this one. The
    distance is measured from 0 (always voted the same) to 1 (always
    voted differently).  Only divisions that both MPs voted in are
    counted.  This may reveal relationships between MPs that were
    previously unsuspected.  Or it may be nonsense.";

    foreach ($mpattr['mpids'] as $mpid)
    {
        #print "<h3>" . pretty_parliament_and_party($from_dates[$i], $parties[$i], $enter_reason[$i], $left_reason[$i]). "</h3>";
        print "<h3>" . "pretty_parliament_and_party". "</h3>";
        print "<table class=\"mps\">\n";
        $query = "select first_name, last_name, title, constituency,
            party, pw_mp.mp_id,
            round(100*rebellions/votes_attended,1),
            round(100*votes_attended/votes_possible,1),
            distance, entered_reason, left_reason from pw_mp,
            pw_cache_mpinfo, pw_cache_mpdist where
            pw_mp.mp_id = pw_cache_mpinfo.mp_id and
            (pw_mp.mp_id = pw_cache_mpdist.mp_id_1
            and pw_cache_mpdist.mp_id_2 = $mpid
            and pw_cache_mpdist.mp_id_1 <> $mpid)
            ";

        print "<tr class=\"headings\"><td>Name</td><td>Constituency</td><td>Party</td><td>Distance</td><td>Rebellions</td><td>Attendance</td></tr>";
        $prettyrow = 0;

        $db->query($query . " and distance = 0");
        $same_voters = $db->rows();

        $limit = "";
        if (!$all_friends)
            $limit .= " limit 0,5";
        $db->query($query . " order by distance $limit");

        while ($row = $db->fetch_row())
        {
            $prettyrow = pretty_row_start($prettyrow);
            $anchor = "\"mp.php?firstname=" . urlencode($row[0]) .
                "&lastname=" . urlencode($row[1]) . "&constituency=" .
                urlencode($row[3]) . "\"";
            if ($row[8] == "0")
                $row[8] = "0 (always vote same)";
            if ($row[8] == "1")
                $row[8] = "1 (always vote different)";

            print "<td><a href=$anchor>$row[2] $row[0] $row[1]</a></td>
                <td>$row[3]</td>
                <td>" . pretty_party($row[4], $row[9], $row[10]) . "</td>
                <td>$row[8]</td>";

            $row[6] = percentise($row[6]);
            $row[7] = percentise($row[7]);
            print "<td class=\"percent\">$row[6]</td>";
            print "<td class=\"percent\">$row[7]</td>";
            print "</tr>\n";
        }
        if ($db->rows() == 0)
        {
            $prettyrow = pretty_row_start($prettyrow, "");
            print "<td colspan=6>no votes to compare</td></tr>\n";
        }
        print "</table>\n";
        if (!$all_friends)
        {
            print "<p><a href=\"$this_anchor&allfriends=yes#friends\">Show all MPs in order of friendliness to this one</a>";
            if ($same_voters > 4)
                print " ($same_voters MPs voted exactly the same as this one)";
        }
        else
        {
            print "<p><a href=\"$this_anchor#friends\">Show only a few possible friends</a>";
        }
    }
?>

<?php
    function print_selected_dream($db, $id, $dreammpid)
    {
		// should count overlapping votes between dream and mp
	    $query = "SELECT name, description, rollie_id, user_name, count(pw_dyn_rollievote.vote) as count
		          FROM pw_dyn_rolliemp, pw_dyn_rollievote, pw_dyn_user
				  WHERE pw_dyn_rollievote.rolliemp_id = $dreammpid and pw_dyn_rolliemp.user_id = pw_dyn_user.user_id and pw_dyn_rollievote.rolliemp_id = rollie_id
				  GROUP BY rollie_id
				  ORDER BY count desc";
	    $db->query($query);
	    $row = $db->fetch_row();
		$link = "jmp.php?id=".urlencode($id)."&dreammpid=".urlencode($dreammpid);
        print "<td>$row[4]</td>\n";
        print "<td>".html_scrub($row[3])."</td>\n";
        print "<td><a href=\"$link\">Compare ".$mpattr['mpname']." to '".html_scrub($row[0])."'</a></td>\n";
        print "<td>" . trim_characters(str_replace("\n", "<br>", html_scrub($row[1])), 0, 50);
        print "</tr>\n";
    }

	print "<h2><a name=\"dreammotions\">Dream MP Comparisons</a></h2>";
    print "<p>Votes on motions chosen by a Dream MP.  A selected list which can
        be used to find what an MP stands for. Email us if you think your Dream
        MP is appropriate to include here.";
    print "<table class=\"mps\">\n";
    print "<tr class=\"headings\">
        <td>Votes</td>
        <td>Made by</td>
        <td>Dream MP</td>
        <td>Description</td>
        </tr>";

    $prettyrow = 0;
	$prettyrow = pretty_row_start($prettyrow);
    print_selected_dream($db, $mpid, 219);
    print_selected_dream($db, $mpid, 258);
    print "</table>\n";
?>


<?php include "footer.inc" ?>
<?php include "cache-end.inc"; ?>

