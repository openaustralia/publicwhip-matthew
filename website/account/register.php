<?  

# $Id: register.php,v 1.11 2004/02/11 00:07:56 frabcus Exp $

# The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

include('database.inc');
include('user.inc');

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

$title = "Sign up for Newsletter"; 
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
    Whip newsletter, which will be at most once a month.";
    print "<p><a href=\"../newsletters/archive.php\">Read archive of previous newsletters</a>";
    print "<br><a href=\"settings.php\">Log in to change settings if you already signed up</a>";
    
}

if (!$ok)
{
?>
    <p><span class="ptitle">Privacy Policy:</span>
    Your email address and info will never be given to or sold to third
    parties.  We will only send you the Public Whip newsletter, or 
    other occasional messages about the Public Whip.  Your login will
    also give you access to the Dream MP feature.
    In the future it may give you access to other free services on the Public
    Whip website.  Any changes to this policy will require your explicit
    agreement.
<?
    if (!$feedback) {
        print "<p>What are you waiting for?  It's free!";
    }
?>
    <P>
    <FORM ACTION="<?=$PHP_SELF?>" METHOD="POST">
    <B>Real Name (first and last):</B><BR>
    <INPUT TYPE="TEXT" NAME="real_name" VALUE="<?=$real_name?>" SIZE="40" MAXLENGTH="50">
    <P>
    <B>Login Name (real or made up, no spaces):</B><BR>
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
    <p><INPUT TYPE="SUBMIT" NAME="submit" VALUE="Sign Up For Newsletter">
    </FORM>
<?php
}
?>

<?php include "../footer.inc" ?>
