<?php require_once "../common.inc";
# $Id: wiki.php,v 1.15 2005/10/06 12:45:07 frabcus Exp $
# vim:sw=4:ts=4:et:nowrap

# The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

include('../database.inc');
include_once('user.inc');

include "../db.inc";
include "../cache-tools.inc";
include "../wiki.inc";
$db = new DB(); 

$just_logged_in = do_login_screen();

if (user_isloggedin()) # User logged in, show settings screen
{
    $type = db_scrub($_GET["type"]);
    if ($type == 'motion')
        $params = array(db_scrub($_GET["date"]), db_scrub($_GET["number"]), db_scrub($_GET["house"]));
    else
        fatal_error("Unknown wiki type " . htmlspecialchars($type));
    $newtext = db_scrub($_POST["newtext"]);
    $submit = db_scrub($_POST["submit"]);
    $r = db_scrub($_GET["r"]);

    $division_number = $matches[2];
    $db->query("select * from pw_division where division_date = '$params[0]' 
        and division_number = '$params[1]' and house = '$params[2]'");
    $division_details = $db->fetch_row_assoc();
    $prettydate = date("j M Y", strtotime($params[0]));
    $title = "Edit Division Description - " . $division_details['division_name'] . " - $prettydate - Division No. $division_number";
    $debate_gid = str_replace("uk.org.publicwhip/debate/", "", $division_details['debate_gid']);
    
    if ($submit && (!$just_logged_in))
    {
        if ($submit == "Save") {
            $db->query_errcheck("insert into pw_dyn_wiki_motion
                (division_date, division_number, house, text_body, user_id, edit_date) values
                ('$params[0]', '$params[1]', '$params[2]', '$newtext', '" . user_getid() . "', now())");
            audit_log("Edited $type wiki text $params[0] $params[1] $params[2]");
            if ($type == 'motion') {
                notify_motion_updated($db, $params[0], $params[1], $params[2]);
            }
        }
        header("Location: ". $r);
        exit;
    }
    else 
    {
        include "../header.inc";

        $values = get_wiki_current_value($type, $params);
        
        if ($type == 'motion') {
?>
        <p>Describe the <i>result</i> of this division.  This will require you
        to check through the 
<?
        if ($debate_gid != "") {
            print "<a href=\"http://www.theyworkforyou.com/debates/?id=$debate_gid\">debate leading up to the vote</a>.";
        } else {
            print "debate leading up to the vote.";
        }
?>
        The raw, and frequently
        wrong, motion text is there by default.  Feel free to remove it when
        you've replaced it with something better. </p>

        <p>Please, keep it accurate, authorative, brief, and as jargon-free as
        possible so that new readers who don't know Parliamentary procedure can
        gain enlightenment. You are encouraged to include hyperlinks to the
        statutory legislation, command papers, reports, and committee
        proceedings that are referred to so that readers who want to follow the
        story further will know where to look.</p>

        <div class="tableexplain">
        <p><span class="ptitle">You can write comments</span>.  Keep them below the "COMMENTS AND
        NOTES" line so that they don't interfere with the provision of what we
        hope could be the most authoratitive and accessible record of what's
        going on in Parliament.</p>

        <p><span class="ptitle">Questions, thoughts?</span>
        <a href="/forum/viewforum.php?f=2">Chat
with other motion researchers on our special forum</a>.

        <p><span class="ptitle">Seperators</span>. Leave the "DIVISION TITLE", "MOTION EFFECT" and "COMMENTS AND NOTES"
        in place, so our computer can work it out.

        <p><span class="ptitle">HTML tags</span>. You can use the following:
        <ul>
        <li>&lt;p&gt; - begin paragraph
        <li>&lt;p class="italic"&gt; - begin italic paragraph
        <li>&lt;p class="indent"&gt; - begin indented paragraph
        <li>&lt;/p&gt; - end paragraph
        <li>&lt;i&gt; &lt;/i&gt; - italic
        <li>&lt;b&gt; &lt;/b&gt; - bold
        <li>&lt;a href="http://..."&gt; &lt;/a&gt; - link
        </ul>

        </div>

        <p><b>Edit division title and description:</b>
<?
        }

?>
        <P>
        <FORM ACTION="<?=$REQUEST_URI?>" METHOD="POST">
        <textarea name="newtext" rows="25" cols="45"><?=html_scrub($values['text_body'])?></textarea>
        <p>
        <INPUT TYPE="SUBMIT" NAME="submit" VALUE="Save">
        <INPUT TYPE="SUBMIT" NAME="submit" VALUE="Cancel">
        </FORM>
        </P>
<?
        if ($type == 'motion') {
?>
        <p><a href="<?=get_wiki_history_link($type, $params)?>">View change history</a>

<?
        }

    }
}
else
{
    login_screen();
}

?> 
<?php include "../footer.inc" ?>
