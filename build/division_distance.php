#!/usr/bin/php -q
<?php
# $Id: division_distance.php,v 1.1 2005/12/05 02:30:33 frabcus Exp $

# Run from the CLI to populate all of the division distance cache table.

# The Public Whip, Copyright (C) 2005 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

require_once "../website/config.php";
require_once "../website/db.inc";
require_once "../website/distances.inc";

$db = new DB();
$db2 = new DB();

fill_division_distances($db, $db2, 'commons', null);

