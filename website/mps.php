<?php require_once "common.inc";
    # $Id: mps.php,v 1.17 2005/10/05 14:42:39 frabcus Exp $

    # The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
    # This is free software, and you are welcome to redistribute it under
    # certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
    # For details see the file LICENSE.html in the top level of the source.

    include "db.inc";
    include "tablemake.inc";
    include "tablepeop.inc";

    include "render.inc";
    $db = new DB();

    $sort = db_scrub($_GET["sort"]);
    if ($sort != "rebellions")
        $title = "MPs";
    else
        $title = "Rebels";

    include "parliaments.inc";
	if ($parlsession != "")
		$title .= " - " . parlsession_name($parlsession) . " Session";
	else
		$title .= " - " . parliament_name($parliament) . " Parliament";

    $second_links = array();
    foreach ($parliaments as $pname => $pdata) {
        array_push($second_links, "<a href=\"mps.php?parliament=". $pname.
              "&sort=" . html_scrub($sort) . "\"
              class=\"".($parliament == $pname ? "on" : "off")."\"
              >".
              $pdata['name'] . " Parliament</a>");
    }
    include "header.inc";

?>
<p>The Members of Parliament are listed with the number of times they
voted against the majority vote for their party and how often they turn up
to vote.  Read a <a href="faq.php#clarify">clear
explanation</a> of these terms, as they may not have the meanings
you expect. You can change the order of the table by selecting the headings.
<?php

    print "<table class=\"mps\">\n";

    $url = "mps.php?parliament=" . urlencode($parliament) . "&";
    print "<tr class=\"headings\">";
    head_cell($url, $sort, "Name", "lastname", "Sort by surname");
    head_cell($url, $sort, "Constituency", "constituency", "Sort by constituency");
    head_cell($url, $sort, "Party", "party", "Sort by party");
    head_cell($url, $sort, "Rebellions<br>(estimate)", "rebellions", "Sort by rebels");
    head_cell($url, $sort, "Attendance<br>(divisions)", "attendance", "Sort by attendance");
    print "</tr>";


	# a function which generates any table of mps for printing,
	$mptabattr = array("listtype" 	=> "parliament",
					   "parliament" => $parliaments[$parliament],
					   "showwhich" 	=> "all",
					   "sortby"		=> $sort,
                       "house"      => "commons");
	mp_table($db, $mptabattr);
    print "</table>\n";
?>

<?php include "footer.inc" ?>
