<?php
# $Id: constituencies.inc,v 1.14 2008/10/13 16:44:36 publicwhip Exp $

# The Public Whip, Copyright (C) 2005 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

function normalise_constituency_name($db, $name, $parl = null) {
    // Use &amp; everywhere, as in DB
    $name = str_replace("&amp;", "&", $name);
    $name = str_replace(" and ", " & ", $name); 
    $name = str_replace("&", "&amp;", $name);
    $name = str_replace("&amp;#39;", "&#39;", $name);
    $name = str_replace("  ", " ", $name); 
    $name = trim($name);
    $name = db_scrub($name);

    $qsel = "SELECT name, cons_id FROM pw_constituency"; 
    $qwhere = " WHERE main_name".($parl ? " AND from_date < '$parl' and '$parl' < to_date" : "");
    $qwhere2 = " AND name LIKE '$name'"; 
    $qorder = " ORDER BY to_date DESC LIMIT 1"; 
    $sth1 = $db->query_onez_row_assoc($qsel.$qwhere.$qwhere2.$qorder);

    if ($sth1)
        return $sth1["name"];
    $qwhere2 = " AND name LIKE '%$name%'";    
    $sth1 = $db->query_onez_row_assoc($qsel.$qwhere.$qwhere2.$qorder);
    if ($sth1)
        return $sth1["name"];
    
    return false;

    #$query = "SELECT name FROM pw_constituency  main_name and cons_id = '".$sth1['cons_id']."'";
    #$sth2 = $db->query_onez_row_assoc($query);
    #if ($sth2)
    #    return $sth2["name"];
}

?>
