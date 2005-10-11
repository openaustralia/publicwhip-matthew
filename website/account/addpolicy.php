<?php require_once "../common.inc";

# $Id: addpolicy.php,v 1.6 2005/10/11 10:14:17 goatchurch Exp $

# The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

include('../database.inc');
include_once('user.inc');
include "../db.inc";
include "../cache-tools.inc";

$just_logged_in = do_login_screen();

if (user_isloggedin()) # User logged in, show settings screen
{

    $name=db_scrub($_POST["name"]);
    $description=db_scrub($_POST["description"]);
    $submit=db_scrub($_POST["submit"]);

    $ok = false;
    if ($submit && (!$just_logged_in)) 
    {
        if (!$_POST["confirmprivacy"])
            $feedback = "Please check the box to confirm you have read the privacy notes.";
        else
        {
            if ($name == "" or $description == "")
                $feedback = "Please name your policy, and give a definition.";
            else
            {
                $db = new DB(); 
                $ret = $db->query_errcheck("insert into pw_dyn_dreammp (name, user_id, description, private) values
                    ('$name', '" . user_getid() . "', '$description', 0)"); 
                if ($ret)
                {
                    $new_dreamid = mysql_insert_id();
                    $ok = true;
                    $feedback = "Successfully added policy '" . html_scrub($name) . "'.  To 
                        select votes for your new policy, <a href=\"../search.php\">search</a> or
                        <a href=\"../divisions.php\">browse</a> for divisions.  On the page for
                        each division you can choose how somebody supporting your policy would have voted.";
                    if (user_getid()) {
                        $db->query("update pw_dyn_user set active_policy_id = $new_dreamid where user_id = " . user_getid());
                    }
                    audit_log("Added new policy '" . $name . "'");
                }
                else
                {
                    $feedback = "Failed to add new policy. " . mysql_error();
                }
            }
        }
    }

    $title = "Make a New Policy"; 
    include "../header.inc";

    if ($feedback && (!$just_logged_in)) {
        if ($ok)
        {
            echo "<p>$feedback</p>";
        }
        else
        {
            echo "<div class=\"error\"><h2>Creating a new policy not complete, please try again
                </h2><p>$feedback</div>";
        }
    }
    else
    {
?><p>Here you have to name and describe
        the policy.  Afterwards, you will be able to select which votes
        support the policy.  The new policy can represent anything you like.
        Some examples:
        <ul>
        <li>Equal laws for heterosexuals and homosexuals.
        <li>Privatise the NHS.
        <li>Pro-nuclear power.
        </ul>

        <p>All policies are public. Just because you made a policy does not
        mean you own it. Anybody can edit it. Although everyone will by no
        means agree with every policy, they should agree that its votes in divisions
        are logical consequences of its definition.

        <p> Some other things to note about policies:
        <ul>

		<li>Policies should be open to as little interpretation as possible.  For example,
        "Evangelical Christian" and "Fiscal Responsibility" are not policies
		since there are differing opinions about what would be implemented.
		Instead, things like "Ban research using embryos" and
		"Buy more nuclear weapons"
        are policies because, even if you don't agree with them, you can still
		agree on which way they would vote.</li>

        <li>Usually a policy can't be broken down into other policies.  If it can be,
		maybe you should split it into two sub-policies.  There are
		exceptions, for example "Pro-vegetarianism" may agree with all the votes for
		"Conserve North Sea fish stocks" although for quite different reasons.</li>

		<li>The opposite of a policy is not necessarily a policy, particularly
        if it is not one of the extremes. For example, there were three major
        parliamentary policies on the issue of fox hunting in the UK in
        2003/2004.  The obvious two policies were, roughly, to allow it and to
        ban it. The <a
        href="http://en.wikipedia.org/wiki/Fox_hunting#The_Middle_Way_group">Middle Way group</a>,
        which included both Labour and Conservative MPs, had a third policy
        which was to restrict hunting with a license scheme.  </li>

        <li>All policies must be convertable into votes.  So, for example,
		"Return Falklands to Argentina" doesn't work because there haven't been any such
		votes (recently).  But you may have success with a broader policy, such as
		"Withdraw from overseas territories".</li>

		<li>Don't be shy.  These policies belong to everyone.  If you have a point of
		view that a policy is wrongly voting, or it's ill-defined, or you can see how it should be broken
		down, share it on the forum.  We hope to have a page of discussion for each
		policy in the near future so you can communicate with the other people
		doing the same thing as you.</li>

		</ul>
<?

    }

    if (!$ok)
    {
        if (!$feedback) {
            #print "<p>What are you waiting for?  It's free!";
        }
    ?>
        <P>
        <FORM ACTION="<?=$PHP_SELF?>" METHOD="POST">
        <B>Policy Name:</B><BR>
        <INPUT TYPE="TEXT" NAME="name" VALUE="<?=html_scrub($name)?>" SIZE="40" MAXLENGTH="50">
        <P>
        <B>Definition (describe the issue and position on the issue):</B><BR>
        <textarea name="description" rows="6" cols="80"><?=html_scrub($description)?></textarea></p>

        <p><span class="ptitle">Privacy Notes:</span>
        By creating a policy you are making your user name
        <b><?=user_getname()?></b> and the policy's voting record public.  

        <p><INPUT TYPE="checkbox" NAME="confirmprivacy">Confirm you have read the
        above privacy notes
        <p><INPUT TYPE="SUBMIT" NAME="submit" VALUE="Make Policy">
        </FORM>

        <p>If you like you can <a href="/forum/viewforum.php?f=1">discuss policies on our forum</a>.

    <?php
    }
}
else
{
    login_screen();
}
?>

<?php include "../footer.inc" ?>
