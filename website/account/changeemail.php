<?  

# $Id: changeemail.php,v 1.8 2005/01/14 16:07:27 frabcus Exp $

# The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

include('../database.inc');
include_once('user.inc');

$password1=mysql_escape_string($_POST["password1"]);
$new_email=mysql_escape_string($_POST["new_email"]);
$change_user_name=mysql_escape_string($_POST["change_user_name"]);
$submit=mysql_escape_string($_POST["submit"]);
$ok = false;
if ($submit) {
	$ok = user_change_email ($password1,$new_email,$change_user_name);
}

$title = "Change Email Address"; 
include "../header.inc";

if ($feedback) {
    if ($ok)
    {
	echo "<p>$feedback</p>";
    }
    else
    {
	echo "<div class=\"error\"><h2>Email address not changed</h2><p>$feedback</div>";
    }
}

if (!$ok)
{
echo ' <P>
    Quickly fill in the information below, and we\'ll send you
    an email for you to confirm your new address.
	<P>
	<FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
	<B>User Name:</B><BR>
	<INPUT TYPE="TEXT" NAME="change_user_name" VALUE="'.  $change_user_name .'" SIZE="15" MAXLENGTH="15">
	<P>
	<B>Password:</B><BR>
	<INPUT TYPE="password" NAME="password1" VALUE="" SIZE="15" MAXLENGTH="15">
	<P>
	<B>NEW Email (must be accurate to confirm):</B><BR>
	<INPUT TYPE="TEXT" NAME="new_email" VALUE="" SIZE="40" MAXLENGTH="50">
	<P>
	<INPUT TYPE="SUBMIT" NAME="submit" VALUE="Send My Confirmation">
	</FORM>';
}
?>
<?php include "../footer.inc" ?>
