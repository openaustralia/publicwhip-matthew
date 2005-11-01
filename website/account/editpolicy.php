<?php require_once "../common.inc";

# $id: editdream.php,v 1.1 2004/04/16 13:38:56 frabcus Exp $

# The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

include('../database.inc');
include_once('user.inc');
include "../db.inc";
include "../cache-tools.inc";
require_once "../dream.inc";
require_once "../DifferenceEngine.inc";

$db = new DB(); 
$just_logged_in = do_login_screen();

if (user_isloggedin()) # User logged in, show settings screen
{
    $title = "Modify Policy"; 

    $dreamid=intval(db_scrub($_GET["id"]));
    
    $name=db_scrub($_POST["name"]);
    $description=$_POST["description"];
    $submit=db_scrub($_POST["submit"]);

    $query = "select name, description, user_id, private from pw_dyn_dreammp where dream_id = '$dreamid'";
    $row = $db->query_one_row($query);
    if (!$name)
        $name = $row[0];
    if (!$description)
        $description = $row[1];
    $user_id = $row[2];
    $private = $row[3];

    if ($private && user_getid() != $user_id)
    {
        include "../header.inc";
        print "<p>This is not your legacy Dream MP, so you can't edit their name or defintion.";
    }
    else
    {
        $ok = false;
        if ($submit && (!$just_logged_in) && $submit == 'Save') 
        {
            if ($name == "" or $description == "")
                $feedback = "Please name the policy, and give a definition.";
            else
            {
                $db = new DB(); 
                list($prev_name, $prev_description) = $db->query_one_row("select name, description from pw_dyn_dreammp where dream_id = '$dreamid'");

                $name_diff = format_linediff($prev_name, stripslashes($name), false); # always have link
                $description_diff = format_linediff($prev_description, $description, true);

                dream_post_forum_action($db, $dreamid, "Changed name and/or definition of policy.\n\n[b]Name:[/b] ".$name_diff."\n[b]Definition:[/b] ".$description_diff);
                $ret = $db->query_errcheck("update pw_dyn_dreammp set name='$name', description='".mysql_escape_string($description)."' where dream_id='$dreamid'");
                notify_dream_mp_updated($db, intval($dreamid));

                if ($ret)
                {
                    $ok = true;
                    $feedback = "Successfully edited policy '" . html_scrub($name) . "'.  
                     To see the changes, go to <a href=\"../policy.php?id=$dreamid\">the
                     policy's page</a>.";
                    audit_log("Edited definition policy '" . $name . "'");
                }
                else
                {
                    $feedback = "Failed to edit policy. " . mysql_error();
                }
            }
        } elseif ($submit) {
            $feedback = "Cancelled";
            $ok = true; # redirect on cancel
        }

        if ($ok)
        {
            header("Location: /policy.php?id=$dreamid\n");
            exit;
        }

        include "../header.inc";
        if ($feedback && (!$just_logged_in)) {
            print "<div class=\"error\"><h2>Modifying the policy not complete, please try again
                </h2><p>$feedback</div>";
        }
        else
        {
            print "<p>Here you can change the name or definition of the policy. From the description, everyone should be able to agree which way the policy votes in each division.";

        }

        if (!$ok)
        {
        ?>
            <P>
            <FORM ACTION="editpolicy.php?id=<?=$dreamid?>" METHOD="POST">
            <B>Name:</B><BR>
            <INPUT TYPE="TEXT" NAME="name" VALUE="<?=html_scrub($name)?>" SIZE="40" MAXLENGTH="50">
            <P>
            <B>Definition (describe the issue and position on the issue):</B><BR>
            <textarea name="description" rows="6" cols="80"><?=htmlspecialchars($description)?></textarea></p>

            <p>
            <INPUT TYPE="SUBMIT" NAME="submit" VALUE="Save" accesskey="S">
            <INPUT TYPE="SUBMIT" NAME="submit" VALUE="Cancel">
            </FORM>
        <?php
        }
    }
    include "../footer.inc";
}
else
{
    login_screen();
}
?>

