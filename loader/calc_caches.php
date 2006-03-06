#!/usr/bin/php -q
<?php
# $Id: calc_caches.php,v 1.13 2006/03/06 15:56:22 publicwhip Exp $

# Calculate lots of cache tables, run after update.

# The Public Whip, Copyright (C) 2005 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

require_once "../website/config.php";
require_once "../website/db.inc";
require_once "../website/parliaments.inc";

$db = new DB();
$db2 = new DB();

count_party_stats($db, $db2);
guess_whip_for_all($db, $db2);
count_mp_info($db);
count_div_info($db);

# then we loop through the missing entries and fill them in
function count_party_stats($db, $db2)
{
    # TODO: redo this per parliament
	$db->query("DROP TABLE IF EXISTS pw_cache_partyinfo");
	$db->query("CREATE TABLE pw_cache_partyinfo (
						party varchar(100) not null,
						house enum('commons', 'lords') not null,
						total_votes int not null
                        )");

	$query = "SELECT party, house, COUNT(vote) AS total_votes
			  FROM pw_vote
			  LEFT JOIN pw_mp ON pw_vote.mp_id = pw_mp.mp_id
              WHERE party IS NOT NULL
			  GROUP BY party, house";

    #print $query;
	$db->query($query);

	while ($row = $db->fetch_row_assoc())
	{
        #print_r($row);
		$qattrs = "party, house, total_votes";
		$qvalues = "'".$row['party']."', '".$row['house']."', '".$row['total_votes']."'";
		$db2->query("INSERT INTO pw_cache_partyinfo ($qattrs) VALUES ($qvalues)");
    };
}


# party whip calc everything
function guess_whip_for_all($db, $db2)
{
	# this table runs parallel to the table of divisions
	$db->query("DROP TABLE IF EXISTS pw_cache_whip_tmp");
	$db->query("CREATE TABLE pw_cache_whip_tmp (
					division_id int not null,
					party varchar(200) not null,
					aye_votes int not null,
					aye_tells int not null,
					no_votes int not null,
					no_tells int not null,
					both_votes int not null,
					possible_votes int not null,
					whip_guess enum('aye', 'no', 'abstain', 'unknown', 'none') not null,
					unique(division_id, party)
			    )");

	$qselect = "SELECT sum(pw_vote.vote = 'aye') AS ayevotes,
					   sum(pw_vote.vote = 'tellaye') AS ayetells,
					   sum(pw_vote.vote = 'no') AS novotes,
					   sum(pw_vote.vote = 'tellno') AS notells,
					   sum(pw_vote.vote = 'both') AS boths,
					   sum(1) AS possible_votes,
					   pw_division.division_id AS division_id, party,
					   division_date, division_number, pw_division.house as house";
	$qfrom =  " FROM pw_division";

	$qjoin .= " LEFT JOIN pw_mp ON
            		pw_division.house = pw_mp.house AND
            		pw_mp.entered_house <= pw_division.division_date AND
            		pw_division.division_date < pw_mp.left_house";
    $qjoin .= " LEFT JOIN pw_vote ON
		            pw_vote.mp_id = pw_mp.mp_id AND
        		    pw_vote.division_id = pw_division.division_id";

	$qgroup = " GROUP BY pw_division.division_id, pw_mp.party, pw_division.house";
	$query = $qselect.$qfrom.$qjoin.$qgroup;

    #print $query;
	$db->query($query);

	while ($row = $db->fetch_row_assoc())
	{
		$party = $row['party'];
		$ayevotes = intval($row['ayevotes']);
		$ayetells = intval($row['ayetells']);
		$novotes = intval($row['novotes']);
		$notells = intval($row['notells']);
		$boths = intval($row['boths']);
		$possibles = intval($row['possible_votes']);

		$ayes = $ayevotes + $ayetells;
		$noes = $novotes + $notells;

		# this would be the point where we add in some if statements accounting for the exceptions
		# where the algorithm doesn't work.  Or we do it against another special table.

		# to detect abstentions we'd need an accurate partyinfo that worked per parliament
		$whip_guess = "unknown";
        if (whipless_party($party)) {
			$whip_guess = "none";
		    if ($party == "CWM" or $party == "DCWM")
                $whip_guess = "abstain";
        }

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

		$qattrs = "division_id, party, aye_votes, aye_tells, no_votes, no_tells, both_votes, possible_votes, whip_guess";
		$qvalues = $row['division_id'].", '".$row['party']."', $ayevotes, $ayetells, $novotes, $notells, $boths, $possibles, '".$whip_guess."'";
        $query = "INSERT INTO pw_cache_whip_tmp ($qattrs) VALUES ($qvalues)";
        #print_r($row);
        #print $query;
		$db2->query($query);
    }

	$db->query("DROP TABLE IF EXISTS pw_cache_whip");
	$db->query("RENAME TABLE pw_cache_whip_tmp TO pw_cache_whip");
}

function count_mp_info($db) {
    count_4d_info($db, "pw_cache_mpinfo", "pw_mp.mp_id", "mp_id", "votes_attended", "votes_possible");
}

function count_div_info($db) {
    count_4d_info($db, "pw_cache_divinfo", "pw_division.division_id", "division_id", "turnout", "possible_turnout");
}

function count_4d_info($db, $table, $group_by, $id, $votes_attended, $votes_possible) {
    $db->query( "DROP TABLE IF EXISTS ${table}_tmp" );
    $db->query(
        "CREATE TABLE ${table}_tmp (
        $id int not null,
        rebellions int not null,
        tells int not null,
        $votes_attended int not null,
        $votes_possible int not null,
        aye_majority int not null,
        index($id)
    )");
    // majority is meaningless in the case of the mp_info -- just how many more ayes than noes in the lifetime of the MP

    $query = "
        INSERT INTO ${table}_tmp
            ($id, rebellions, tells, $votes_attended, $votes_possible, aye_majority)
        SELECT
            $group_by,
            SUM((whip_guess = 'aye' AND (vote = 'no' or vote = 'tellno')) OR
                (whip_guess = 'no' AND (vote = 'aye' or vote = 'tellaye')) OR
                (whip_guess = 'abstain' AND (vote IS NOT NULL))) AS rebellions,
            SUM(vote = 'tellaye' OR vote = 'tellno') AS tells,
            SUM(vote IS NOT NULL) as $votes_attended,
            SUM(pw_division.division_id IS NOT NULL) AS $votes_possible,
            SUM((vote = 'aye' or vote = 'tellaye') - (vote = 'no' or vote = 'tellno')) AS aye_majority

        FROM pw_division

        LEFT JOIN pw_mp ON
            pw_division.house = pw_mp.house AND
            pw_mp.entered_house <= pw_division.division_date AND
            pw_division.division_date < pw_mp.left_house

        LEFT JOIN pw_vote ON
            pw_vote.mp_id = pw_mp.mp_id AND
            pw_vote.division_id = pw_division.division_id

        LEFT JOIN pw_cache_whip ON
            pw_cache_whip.party = pw_mp.party AND
            pw_cache_whip.division_id = pw_division.division_id

        GROUP BY $group_by
    ";

    #print $query;
    $db->query($query);

	$db->query("DROP TABLE IF EXISTS $table");
	$db->query("RENAME TABLE ${table}_tmp TO $table");
}


