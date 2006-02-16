#!/usr/bin/php -q
<?php
# $Id: calc_caches.php,v 1.2 2006/02/16 11:59:32 goatchurch Exp $

# Calculate lots of cache tables, run after update.

# The Public Whip, Copyright (C) 2005 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

require_once "../website/config.php";
require_once "../website/db.inc";
require_once "../website/distances.inc";

$db = new DB();
$db2 = new DB();

# then we loop through the missing entries and fill them in
function count_party_stats($db, $db2)
{
	$db->query("DROP TABLE IF EXISTS pw_cache_partyinfo");
	$db->query("CREATE TABLE pw_cache_partyinfo (
						party varchar(100) not null,
						house enum('commons', 'lords') not null,
						total_votes int not null,

											    )");

	$query = "SELECT party, house, COUNT(vote) AS total_votes
			  FROM pw_vote, pw_mp
			  WHERE pw_vote.mp_id = pw_mp.mp_id
			  GROUP BY party, house";

	$db->query($query);

	while ($row = $db->fetch_row_assoc())
	{
		$qattrs = "party, house, total_votes";
		$qvalues = $row['party'].", ".$row['house'].", ".$row['total_votes'];
		$db2->query("INSERT INTO pw_cache_divdiv_distance ($qattrs) VALUES ($qvalues)");
    };
}


# party whip calc everything
function guess_whip_for_all($db, $db2)
{
	# this table runs parallel to the table of divisions
	$db->query("DROP TABLE IF EXISTS pw_cache_whip");
	$db->query("CREATE TABLE pw_cache_whip (
					division_id int not null,
					party varchar(200) not null,
					whip_guess enum('aye', 'no', 'abstain', 'unknown', 'none') not null,
					unique(division_id, party)
			    )");

	$qselect = "SELECT count(pw_vote.vote = 'aye' or pw_vote.vote = 'tellaye') AS ayes,
					   count(pw_vote.vote = 'no' or pw_vote.vote = 'tellno') AS noes,
					   count(*) AS party_count,
					   pw_division.division_id AS division_id, party,
					   division_date, division_number, house";
	$qfrom =  " FROM pw_division";
	$qjoin =  " LEFT JOIN pw_vote
					ON pw_vote.division_id = pw_division.division_id";
	$qjoin .= " LEFT JOIN pw_mp
					ON pw_mp.mp_id = pw_vote.mp_id";
	$qgroup = " GROUP BY pw_division.division_id, pw_mp.party, pw_division.house";
	$query = $qselect.$qfrom.$qjoin.$qgroup;

	$db->query($query);

	while ($row = $db->fetch_row_assoc())
	{
		$party = $row['party'];
		$ayes = int($row['ayes']);
		$noes = int($row['noes']);
		$total = int($row['party_count']);


		# this would be the point where we add in some if statements accounting for the exceptions
		# where the algorithm doesn't work.  Or we do it against another special table.


		# to detect abstentions we'd need an accurate partyinfo that worked per parliament
		$whip_guess = "unknown";
		if ($party == "Cross-bench" or $party == "Ind")
			$whip_guess = "none";
		else if ($party == "Speaker" or $party == "Deputy Speaker")
			$whip_guess = "abstain";

		# keep it very simple so it doesn't change and we can easily keep the set of exceptions constant.
		# if it can be tuned then there will be a maintenance issue whenever the algorithm got changed
		else
		{
			if ($ayes > $noes)
				$whip_guess = "aye";
			else if ($ayes < $noes)
				$whip_guess = "no";
			else
				$whip_guess = "unknown";
		}


		$qattrs = "division_id, party, whip_guess";
		$qvalues = $row['division_id'].", ".$row['party'].", ".$row['total_votes'];
		$db2->query("INSERT INTO pw_cache_divdiv_distance ($qattrs) VALUES ($qvalues)");
	}
}


