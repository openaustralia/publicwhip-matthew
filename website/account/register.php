<?php require_once "../common.inc";

# $Id: register.php,v 1.23 2005/11/01 00:56:21 frabcus Exp $

# The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

require_once "../database.inc";
require_once "user.inc";

$user_name=mysql_escape_string($_POST["user_name"]);
$password1=mysql_escape_string($_POST["password1"]);
$password2=mysql_escape_string($_POST["password2"]);
$email=mysql_escape_string($_POST["email"]);
$real_name=mysql_escape_string($_POST["real_name"]);
$submit=mysql_escape_string($_POST["submit"]);

if (user_isloggedin()) {
	user_logout();
	$user_name='';
}

$ok = false;
if ($submit) {
	$ok = user_register($user_name,$password1,$password2,$email,$real_name);
}

$title = "Sign up for Newsletter, Forum and Policies"; 
$onload = "givefocus('real_name')";
include "../header.inc";

if ($feedback) {
    if ($ok)
    {
	echo "<p>$feedback</p>";
    }
    else
    {
	echo "<div class=\"error\"><h2>Registration not complete,
        please try again</h2><p>$feedback</div>";
    }
}
else
{
    print "<p>
    Quickly fill in the information below, and we'll send you
    a confirmation email.  You will then receive the Public
    Whip newsletter, which will be at most once a month.
    Occasionally we will send an extra small topical newsletter.
    You will also be able to edit division descriptions and policies.  After
    signing up you can unsubscribe from the newsletter, but still
    edit divisions and policies.";
    print "<p><a href=\"../newsletters/archive.php\">Read archive of previous newsletters</a>";
    print "<br><a href=\"settings.php\">Log in to change settings if you already signed up</a>";
    
}

if (!$ok)
{
    if (!$feedback) {
        print "<p>What are you waiting for?  It's free!";
    }
?>
    <P>
    <FORM ACTION="<?=$PHP_SELF?>" METHOD="POST">
    <B>Your name (first and last):</B><BR>
    <INPUT TYPE="TEXT" NAME="real_name" id="real_name" VALUE="<?=$real_name?>" SIZE="40" MAXLENGTH="50">
    <P>
    <B>User name (real or made up, no spaces):</B><BR>
    <INPUT TYPE="TEXT" NAME="user_name" VALUE="<?=$user_name?>" SIZE="40" MAXLENGTH="15">
    <P>
    <B>Password:</B><BR>
    <INPUT TYPE="password" NAME="password1" VALUE="" SIZE="40" MAXLENGTH="15">
    <P>
    <B>Password (again):</B><BR>
    <INPUT TYPE="password" NAME="password2" VALUE="" SIZE="40" MAXLENGTH="15">
    <P>
    <B>Email (must be accurate to confirm):</B><BR>
    <INPUT TYPE="TEXT" NAME="email" VALUE="<?=$email?>" SIZE="40" MAXLENGTH="50">
    <P>
    <p><span class="ptitle">Privacy Policy:</span>
    Your email address and info will never be given to or sold to third
    parties.  We will only send you the Public Whip newsletter, or 
    other occasional messages about the Public Whip.  Your login will
    also let you edit policies, division descriptions and will display your
    user name on your postings in the forum.  In the future it may give you
    access to other free services on the Public Whip website.  Any changes to
    this policy will require your explicit agreement.
<? if ($_GET['r']) { 
?>
        <INPUT TYPE="hidden" NAME="r" VALUE="<?=htmlspecialchars($_GET['r'])?>">
<?    } ?>
<p><INPUT TYPE="SUBMIT" NAME="submit" VALUE="Sign Up">
    </FORM>
<?php
}
?>

<?php include "../footer.inc" ?>
