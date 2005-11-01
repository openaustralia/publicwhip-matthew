<?php require_once "common.inc";
# $Id: search.php,v 1.40 2005/11/01 00:56:21 frabcus Exp $

# The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

    require_once "db.inc";
    $prettyquery = html_scrub(trim($_GET["query"]));
    $query = strtolower(db_scrub(trim($_GET["query"])));
    $title = "Search for '$prettyquery'"; 
    if ($prettyquery == "") {
        $onload = "givefocus('query')";
        $title = "Search";
    }
    require_once "render.inc";
    require_once "parliaments.inc";
    require_once "constituencies.inc";
    require_once "links.inc";
    require_once "postcode.inc";

    $db = new DB(); 

    $postcode = is_postcode($query);
    $header = false;

    if ($query <> "")
    {
        $found = false;

        if (!$postcode)
        {
            $header = true;
            include "header.inc";
            
            # Perform query on divisions
            $db->query("$divisions_query_start and (lower(division_name) like '%$query%'
                or lower(motion) like '%$query%')
                order by division_date desc, division_number desc");

            if ($db->rows() > 0)
            {
                $found = true;
                print "<p>Found these " . $db->rows() . " divisions matching '$prettyquery':";
                print "<table class=\"votes\">\n";
                print "<tr
                class=\"headings\"><td>No.</td><td>Date</td><td>Subject</td><td>Rebellions</td><td>Turnout</td></tr>";
                render_divisions_table($db);
                print "</table>\n";
            }
        }

        # Perform query on MPs
        $score_clause = "(";
        $score_clause .= "(lower(concat(first_name, ' ', last_name)) = '$query') * 10";


        $querybits = explode(" ", $query);

        foreach ($querybits as $querybit)
        {
            $querybits = trim($querybits);
            if ($querybits != "")
            {
                $score_clause .= "+ (lower(constituency) = '$querybit') * 10 + 
                (soundex(concat(first_name, ' ', last_name)) = soundex('$querybit')) * 8 + 
                (soundex(constituency) = soundex('$querybit')) * 8 + 
                (soundex(last_name) = soundex('$querybit')) * 6 + 
                (lower(constituency) like '%$querybit%') * 4 + 
                (lower(last_name) like '%$querybit%') * 4 + 
                (soundex(first_name) = soundex('$querybit')) * 2 + 
                (lower(first_name) like '%$querybit%') + 
                (soundex(constituency) like concat('%',soundex('$querybit'),'%'))";
            }
        }
        $score_clause .= ")";

        if ($postcode)
        {
#            print "Postcode searching is not available.  Please search for your MP name. If you don't know their name, you can try searching for your town or district name, or use <a href=\"http://www.faxyourmp.com\">FaxYourMP</a> to find your MP name from postcode.";
            $score_clause = "(1=0)";
            $pccons = postcode_to_constituency($db, $query);
            if (isset($pccons))
            {
                # Overwrite over matches if we have postcode
                $score_clause = "(constituency = '" . db_scrub($pccons) . "')";
            }
        }

        $db->query("$mps_query_start and ($score_clause > 0) 
                    order by $score_clause desc, constituency, entered_house desc, last_name, first_name");

        # Even with postcode, we check the query first, as the search page gives better error message
        if ($postcode and $db->rows() > 0)
        {
            header("Location: mp.php?constituency=" . urlencode($pccons));
            exit;
        }

        if (!$header)
            include "header.inc";

        if ($db->rows() > 0)
        {
            $found = true;
            print "<p>Found these MPs whose names sound like ";
            if ($postcode)
                print "postcode ";
            print "'$prettyquery':";
            print "<table class=\"mps\"><tr
                class=\"headings\"><td>Date</td><td>Name</td><td>Constituency</td><td>Party</td></tr>\n";
            $prettyrow = 0;
            while ($row = $db->fetch_row_assoc())
            {
                $prettyrow = pretty_row_start($prettyrow);
                $anchor = "\"mp.php?". link_to_mp($row) . "\"";

                print "<td>" . year_range($row['entered_house'], $row['left_house']) . "</td>";
                print "<td><a href=$anchor>".$row['title']." ".$row['first_name']." ".$row['last_name']."</a></td></td>";
                if ($row['house'] == 'commons')
                    print "<td>".$row['constituency']."</td>";
                else 
                    print "<td>n/a</td>";
                print "<td>" . pretty_party($row['party'], $row['entered_reason'], $row['left_reason']) .  "</td>";
                print "</tr>\n";
            }
            print "</table>\n";
        } 

        # Nothing?
        if (!$found)
        {
?>
<p>Nothing found matching <? if ($postcode) print "postcode "; ?> '<?=$prettyquery?>'.
<p>Try browsing the list of <a href="mps.php">all MPs</a>
or <a href="divisions.php">all divisions</a>.
<?php
        }

    }
    else
    {
        include "header.inc";
    }

    $random_mp = searchtip_random_mp($db);
    $random_constituency = searchtip_random_constituency($db);
    $random_topic = searchtip_random_topic($db);
?>

<p class="search">Enter your postcode, MP name, constituency or debate topic:</p>
<form class="search" action="search.php" name=pw>
<input maxLength=256 size=25 name=query id=query value=""> <input type="submit" value="Search" name="button">
</form>

<p class="search"><i>Example: "OX1 3DR", "<?=$random_mp?>"<?=$random_constituency?> or "<?=$random_topic?>"</i>

<p class="search"><span class="ptitle">MPs:</span> You can enter a postcode
to get a list of all MPs for that constituency, or else enter their name or
part of their name.  If you don't know exactly how to spell the name,
write it as best you can like it sounds.

<p class="search"><span class="ptitle">Divisions:</span> 
To find divisions you are interested in, enter the
name of a subject, such as "Pensions" or "Hunting".  The Public Whip
will search the titles of the divisions and the text of the motion being
debated.  If you enter multiple words, it will only find entries where they 
appear next to each other as you enter them.  You can enter part of a word.

<?php include "footer.inc" ?>

