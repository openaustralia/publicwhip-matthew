<?
require_once "common.inc";
require_once "forummagic.inc";
require_once "db.inc";
print "<p>doing";

$db = new DB();
post_to_forum($db, "Policies", "Renaming Dream MPs as Policies");



?>
