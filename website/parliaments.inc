<?php

# $Id: parliaments.inc,v 1.18 2006/03/06 15:56:24 publicwhip Exp $
# This data is duplicated in loader/PublicWhip/Parliaments.pm

# The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

# The id is unique forever - so if there were two parliaments in one
# year, the first would be id'd after the year, and we'd have to
# give the scond an id with the month in as well.  The name can change
# (as it is not in URLs), so can be any clarifying pretty description.
$parliaments = array(
    '2008' => array('from' => '2008-02-12', 'to' => '9999-12-31', 'name' => '2008 (current)'),
    '2004' => array('from' => '2004-11-16', 'to' => '2007-10-17', 'name' => '2004-2007'),
);

# These are parliamentary sessions (i.e. years) - nothing to do with
# PHP or web sessions or anything like that.
$parlsessions = array(
	'2002' => array('from' => '2002-11-13', 'to' => '2003-11-20', 'name' => '2002/2003'),
);
$parlsession = db_scrub($_GET["session"]);

function parliament_count()
{
    global $parliaments;
    return count($parliaments);
}

function date_to_parliament($date)
{
    global $parliaments;

    foreach ($parliaments as $id => $parliament)
    {
        if ($parliament['from'] <= $date && $date <= $parliament['to'])
            return $id;
    }
    return "unknown";
}

function parliament_name($id)
{
    global $parliaments;
    return $parliaments[$id]['name'];
}

function parliament_date_from($id)
{
    global $parliaments;
    return $parliaments[$id]['from'];
}

function parliament_date_to($id)
{
    global $parliaments;
    return $parliaments[$id]['to'];
}

# possibly dead function
function year_range($from, $to)
{
    $y1 = substr($from, 0, 4);
    $y2 = substr($to, 0, 4);
    $ret = $y1;
#    if ($y1 <> $y2)
    {
        $ret .= "-";
        if ($y2 <> "9999")
            $ret .= $y2;
        else
            $ret .= date("Y");
    }
    return $ret;
}

function parlsession_name($id)
{
    global $parlsessions;
    return $parlsessions[$id]['name'];
}

function parlsession_date_from($id)
{
    global $parlsessions;
    return $parlsessions[$id]['from'];
}

function parlsession_date_to($id)
{
    global $parlsessions;
    return $parlsessions[$id]['to'];
}

function whipless_party($party) {
    return $party == "XB" || 
            $party == "Other" || 
            substr($party, 0, 3) == "Ind" || 
            $party == "CWM" || 
            $party == "DCWM";
}

?>
