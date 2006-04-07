<?php require_once "common.inc";

    # $id: dreammp.php,v 1.4 2004/04/16 12:32:42 frabcus Exp $

    # The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
    # This is free software, and you are welcome to redistribute it under
    # certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
    # For details see the file LICENSE.html in the top level of the source.

    require_once "db.inc";
    require_once "database.inc";

    
    $db = new DB();

// create the table (once) so we can use it.
/*$db->query("create table pw_dyn_aggregate_dreammp (
	dream_id_agg int not null,
	dream_id_sel int not null,
    vote_strength enum(\"strong\", \"weak\") not null,
	index(dream_id_agg),
	index(dream_id_sel),
    unique(dream_id_agg, dream_id_sel)
);");
*/

	# standard decoding functions for the url attributes
	require_once "decodeids.inc";
	require_once "tablemake.inc";
    require_once "tableoth.inc";

    require_once "dream.inc";
	require_once "tablepeop.inc";

	# this replaces a lot of the work just below
	$voter = get_dreammpid_attr_decode($db, "id");  # for pulling a dreammpid from id= rather than the more standard dmp=
    $policyname = html_scrub($voter["name"]);
	$dreamid = $voter["dreammpid"];

	// all private dreams will be aggregate
    $bAggregate = ($voter["private"] == 1);

	// should be available only to the owner
	$bAggregateEditable = true; //(($_GET["editable"] == "yes") || ($_POST["submit"] != ""));


    $title = "Policy - $policyname";

	# constants
	$dismodes = array();
	$dismodes["summary"] = array("dtype"	=> "summary",
								 "description" => "Votes",
								 "comparisons" => "yes",
								 "divisionlist" => "selected",
								 "policybox" => (!$bAggregate ? "yes" : ""),
								 "aggregate" => ($bAggregate ? "shown" : ""),
                                 "tooltip" => "Overview of the policy");

	$dismodes["extended"] = array("dtype"	=> "extended",
								 "description" => "Aggregates",
								 "divisionlist" => "selected",
								 "aggregate" => "fulltable",
                                 "tooltip" => "Overview of the policy");


	# work out which display mode we are in
	$display = $_GET["display"];
	if (!$bAggregateEditable || !$bAggregate || !$dismodes[$display])
		$display = "summary"; # default
	$dismode = $dismodes[$display];

    # make list of links to other display modes
    $second_links = dismodes_to_second_links($thispage, $dismodes, $tpsort, $display);

    pw_header();

	// this is where we save the votes
	if ($_GET["savevotes"] && $bAggregateEditable && $bAggregate)
	{ 
		print '<h2>THIS IS WHERE WE SAVE THE VOTES INTO THE POLICY</h2>.\n';
	}

    print "<div class=\"policydefinition\">";
    print "<p><b>Definition:</b> " . str_replace("\n", "<br>", html_scrub($voter["description"])). "</p>";
    if ($voter["private"] == 1)
        print "<p><b>Made by:</b> " . pretty_user_name($db, html_scrub($voter["user_name"])) . " (this is a legacy Dream MP)";
    if ($voter["private"] == 2)
        print "<strong>This policy is provisional, please help improve it</strong>";
    print "</p>";

    print "<p><a href=\"account/editpolicy.php?id=$dreamid\">Edit definition</a>";
    $discuss_url = dream_post_forum_link($db, $dreamid);
    if (!$discuss_url) {
        // First time someone logged in comes along, add policy to the forum
        global $domain_name;
        if (user_getid()) {
            dream_post_forum_action($db, $dreamid, "Policy introduced to forum.\n\n[b]Name:[/b] [url=http://$domain_name/policy.php?id=".$dreamid."]".$policyname."[/url]\n[b]Definition:[/b] ".$voter['description']);
            $discuss_url = dream_post_forum_link($db, $dreamid);
        } else {
            print ' | <a href="http://'.$domain_name.'/forum/viewforum.php?f=1">Discuss</a>';
        }
    }
    if ($discuss_url)
        print ' | <a href="'.htmlspecialchars($discuss_url).'">Discuss changes</a>';

	print "</div>\n";

    if ($dismode["aggregate"] == "fulltable")
	{
		// changed vote
		if (mysql_escape_string($_POST["submit"]))
        {
        	$newseldreamid = mysql_escape_string($_POST["seldreamid"]);
			$icomma = strpos($newseldreamid, ',');
			$seldreamid = substr($newseldreamid, 0, $icomma);
			$seldreamidvote = substr($newseldreamid, $icomma + 1);
			print "<h1>$seldreamid = $seldreamidvote</h1>\n";

			// find current vote
		    $query = "SELECT vote_strength
					  FROM pw_dyn_aggregate_dreammp
					  WHERE pw_dyn_aggregate_dreammp.dream_id_agg = $dreamid
					  	AND pw_dyn_aggregate_dreammp.dream_id_sel = $seldreamid";
			$row = $db->query_onez_row_assoc($query);
			$bpolselectedcurrent = ($row != null);
			$bpolselectednew = ($seldreamidvote == "yes");

			if ($bpolselectedcurrent != $bpolselectednew)
			{
				if (!$bpolselectednew)
	                $query = "DELETE FROM pw_dyn_aggregate_dreammp
							  WHERE pw_dyn_aggregate_dreammp.dream_id_agg = $dreamid
							  	AND pw_dyn_aggregate_dreammp.dream_id_sel = $seldreamid";
				else
	                $query = "INSERT INTO pw_dyn_aggregate_dreammp
							  	(dream_id_agg, dream_id_sel, vote_strength)
							  VALUES
							    ($dreamid, $seldreamid, 'strong')";
	            $db->query($query);
			}
			else
				print "<h2>DDDD  No Vote Changed</h2>\n";
        }

	    print "<table class=\"mps\">\n";
		$dreamtabattr = array("listtype" => 'aggregatevotes',
						      'dreamid' => $dreamid,
						      'listlength' => "allpublic",
							  'headings' => "yes",
							  'editable' => "yes");
		$c = print_policy_table($db, $dreamtabattr);
	    print "</table>\n";
	}

	// short list
    if ($dismode["aggregate"] == "shown")
	{
		print "<p>This Dream MP supports the following policies: ";
		$query = "SELECT name, pw_dyn_dreammp.dream_id AS dream_id
				  FROM pw_dyn_aggregate_dreammp
				  LEFT JOIN pw_dyn_dreammp
				  		ON pw_dyn_dreammp.dream_id = pw_dyn_aggregate_dreammp.dream_id_sel
        					AND pw_dyn_aggregate_dreammp.dream_id_agg = $dreamid
				  ORDER BY name";
		$db->query($query);
		$npols = $db->rows();
		if ($npols == 0)
			print "<p>This Dream MP supports no policies";
		else if ($npols == 1)
			print "<p>This Dream MP supports the following policy: ";
		else
			print "<p>This Dream MP supports the following policies: ";
		$count = 0;
	    while ($row = $db->fetch_row_assoc())
		{
			$count++;
			if ($count == 1)
				;
			else if ($count == $npols)
				print ", and ";
			else
				print ", ";
	        print '<a href="policy.php?id='.$row['dream_id'].'">'.$row["name"].'</a>';
		}
		print ".</p>\n";

		// should this be a button
		if ($bAggregateEditable)
			print '<p><a href="policy.php?id='.$dreamid.'&display='.$display.'&savevotes=yes">CLICK HERE TO SAVE YOUR VOTES</a></p>.\n';
	}


    if ($dismode["policybox"])
    {
	    print "<h2><a name=\"comparison\">Compare Against one MP</a></h2>";
        print "<div class=\"tabledreambox\">";
        print dream_box($dreamid, $policyname);
        print '<p>Why not <a href="#dreambox">add this to your own website?</a></p>';
        print "</div>";
    }

	if ($dismode["divisionlist"] == "selected")
	{
		print "<h2><a name=\"divisions\">Selected Divisions</a></h2>";
                if ($voter["votes_count"]) {
                 print "<p>This policy has voted in <b>".$voter["votes_count"]."</b> divisions.";
		 if ($voter["votes_count"] != $voter["edited_count"])
	              print " A total of <b>".(($voter["votes_count"]) - ($voter["edited_count"]))."</b> of these have not had their descriptions edited.";
		 }
	}
	else
	{
		print "<h2><a name=\"divisions\">Every Division</a></h2>\n";
	}

    print "<p>Have you spotted a wrong vote, or one that is missing?  Please edit and fix the votes and definition of a policy. ";
    if (user_getid()) {
        $db->query("update pw_dyn_user set active_policy_id = $dreamid where user_id = " . user_getid());
        print " This is now your active policy; to change its votes, go to any division page.";
    } else {
        print ' <a href="/account/settings.php">Log in </a> to do this.';
    }

    print "<table class=\"divisions\">\n";
	$divtabattr = array(
			"voter1type" 	=> "dreammp",
			"voter1"        => $dreamid,
			"showwhich"		=> ($dismode["divisionlist"] == "selected" ? "all1" : "everyvote"),
			"headings"		=> 'columns',
			"divhrefappend"	=> "&dmp=$dreamid", # gives link to crossover page
			"motionwikistate" => "listunedited");
	if ($dismode["aggregate"])
	{
		$divtabattr["voter2type"] = "aggregate";
		$divtabattr["voter2"] = $dreamid;
		$divtabattr["showwhich"] = "either";
	}
	division_table($db, $divtabattr);
    print "</table>\n";

	if ($dismode["comparisons"])
	{
	    print "<h2><a name=\"comparison\">Comparison to all MPs and Lords</a></h2>";

	    print "<p>Grades MPs and Lords acording to how often they voted with the policy.
	            If they always vote the same as the policy then their agreement is 100%, if they
				always vote the opposite when the policy votes, their agreement is 0%.
                If they never voted at the same time as the policy they don't appear.";

		$mptabattr = array("listtype" => 'dreamdistance',
						   'dreammpid' => $dreamid,
						   'dreamname' => $policyname, 
                           'headings' => 'yes');
		print "<table class=\"mps\">\n";
		mp_table($db, $mptabattr);
		print "</table>\n";
	}

	if ($dismode["policybox"])
	{
	    print '<h2><a name="dreambox">Add Policies to Your Website</a></h2>';
	    print '<p>Get people thinking about your issue, by adding a policy search
				box to your website.  This lets people compare their own MP to your policy,
				like this.</p>';
	    print dream_box($dreamid, $policyname);
	    print '<p>To do this copy and paste the following HTML into your website.
				Feel free to fiddle with it to fit the look of your site better.  We only
				ask that you leave the link to Public Whip in.';
	    print '<pre class="htmlsource">';
	    print htmlspecialchars(dream_box($dreamid, $policyname));
	    print '</pre>';
	}
?>

<?php pw_footer() ?>

