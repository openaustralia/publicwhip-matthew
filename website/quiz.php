<?php require_once "common.inc";

# $Id: quiz.php,v 1.5 2006/04/13 23:20:48 publicwhip Exp $

# The Public Whip, Copyright (C) 2006 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

require_once "db.inc";
require_once "decodeids.inc";
$db = new DB();
$db2 = new DB();

// Which MP are we comparing against?
if (get_http_var('submit')) {
    $voterattr = get_mpid_attr_decode($db, $db2, "");
    #print "<pre>";print_r($voterattr['mpprop']);print"</pre>";
}

// Get list of all public policies, which have at least one vote
$policies = array();
$qselect = "select pw_dyn_dreammp.*";
$qfrom = " from pw_dyn_dreammp";
$qjoin = "";
$qwhere = " where private = 0
    and (select count(*) from pw_dyn_dreamvote where pw_dyn_dreamvote.dream_id
         = pw_dyn_dreammp.dream_id) > 0";
$qorder = " order by name";

// Look up views of MP
if ($voterattr) {
    $qselect .= ", distance_a";
    $qjoin .= " LEFT JOIN pw_cache_dreamreal_distance 
                ON pw_cache_dreamreal_distance.dream_id = pw_dyn_dreammp.dream_id
                AND pw_cache_dreamreal_distance.person = ".$voterattr['mpprop']['person'];
}

// Look up data about all the policies
$row = $db->query($qselect.$qfrom.$qjoin.$qwhere.$qorder);
while ($row = $db->fetch_row_assoc()) {
    $policies[] = $row;
}    

// Grab shorter URL if it is one
$qstring = $_SERVER["QUERY_STRING"];
$shorter_url = false;
if (preg_match ("/^([^;]*);((?:\d+;)+(?:\d+))$/", $qstring, $matches)) {
    $_GET = array();
    $_GET['submit'] = "1";
    $_GET['mppc'] = $matches[1];
    $shortviews = split(";",$matches[2]);
    foreach ($shortviews as $shortview) {
        $_GET["p$shortview"] = "on";
    }
    $shorter_url = true;
}

// Validate if a submit
// TODO
$errors = array();

// Read in views
$views = array();
foreach ($_GET as $key => $value) {
    if (preg_match ("/^p([0-9]+)$/", $key, $matches)) {
        $policy = $matches[1];
        $views[$policy] = 1;
    }
}

// Redirect to shorter URL
if ($_GET['submit'] and !$errors and !$shorter_url) {
    $qpc = strtoupper(trim($_GET['mppc']));
    $qpc = str_replace(" ", "", $qpc);
    $quick = "?$qpc;";
    $quick .= join(";", array_keys($views));
    header("Location: /quiz.php$quick\n");
    exit;
}

// Calculate distance from MP to user's view
if ($voterattr) {
    $distance_from_mp = 0.0;
    $c = 0;
    foreach ($policies as $policy) {
        if ($views[$policy['dream_id']] && $policy['distance_a'] != null) {
            // User ticked "agree with policy"
            #print "got!<pre>"; print_r($policy); print"</pre><p>";
            $distance_from_mp += $policy['distance_a'];
            $c++;
        }
    }
    if ($c) {
        $distance_from_mp /= $c;
    } else {
        $distance_from_mp = null;
    }
    $agreement_with_mp = (1.0 - (float)($distance_from_mp)) * 100.0;
}

// Header
if ($voterattr)
    $title = "Your views compared to ".$voterattr["mpprop"]["name"].
        " " . $voterattr["mpprop"]["housenamesuffix"];
else
    $title = "Tick all policies that you agree with";
pw_header();

function quiz_form() {
    global $policies, $views, $voterattr, $distance_from_mp, $agreement_with_mp;

    if ($voterattr) {
        print "<p>Agreement with MP: ";
        print number_format($agreement_with_mp, 0)."%";
    }

    print '<form name="howtovote" method="get" action="quiz.php">';
    print '<table>';
    print "<tr valign=\"top\"><td>";
    $c = 0;
    foreach ($policies as $policy) {
        print '<input type="checkbox" name="p'.$policy['dream_id'].'" id="p'.$policy['dream_id'].'"';
        if (array_key_exists($policy['dream_id'], $views))
            print " checked";
        print '>';
        print '<label title="'.htmlspecialchars($policy['description']).'" for="p'.$policy['dream_id'].'">'.$policy["name"].'</label>';
        if ($policy['distance_a'] != null) {
            print " (".number_format((1.0-$policy['distance_a']) * 100, 0). "%)";
        }
        print " (<a href=\"policy.php?id=" . $policy['dream_id'] . "\">details";
        print "</a>)";
        print "<br>";
    #    print $_GET['p'.$issue['dream_id']])
        $c++;
        if ($c == intval(count($policies) / 2 + 1)) {
            print "</td><td>";
        }
    }
    print "</td></tr>";
    print '</table>';
    print '<input id="submit" name="submit" type="hidden"  value="1">';
	print 'And tell us your <strong>postcode</strong>... <input type="text" size="10" name="mppc" value="'.htmlspecialchars($_GET['mppc']).'" id="mppc">';
    print ' <input type="submit" name="button" value="go!" id="button">';
    print '<br><small>(so we can look up who your MP is)</small>';
    print '</form>';
}

quiz_form();
pw_footer();


