-- $Id: create.sql,v 1.35 2005/10/27 01:44:09 frabcus Exp $
-- SQL script to create the empty database tables for publicwhip.
--
-- The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
-- This is free software, and you are welcome to redistribute it under
-- certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
-- For details see the file LICENSE.html in the top level of the source.

--
-- You need to do the following to use this:
--
-- 1. Install MySQL.
--
-- 2. Create a new database. Giving your user account permission to access
--    it from the host you will be running the client on.  Try using
--    mysql_setpermission to do this with.
-- 
-- 3. Type something like "cat create.sql | mysql --database=yourdb -u username -p"
--    Or you can load this file into a GUI client and inject it.
-- 

-------------------------------------------------------------------------------
-- Static tables
--   those based on Hansard data, which get updated, but not altered by scripts

drop table if exists pw_seat, pw_division, pw_vote, pw_moffice;

-- A seat in parliament for a period of time, also changes if party changes.
-- MPs and Peers are in this table.  The fields originally just stored data
-- about MPs; they have been overloaded to also store info about Lords
create table pw_mp (
    mp_id int not null primary key, -- internal to Public Whip

    gid text not null, -- uk.org.publicwhip/member/123, uk.org.publicwhip/lord/123
    source_gid text not null, -- global identifier
    
    first_name varchar(100) not null, -- Lords: "$lordname" or empty string for "The" lords
    last_name varchar(100) not null, -- Lords: "of $lordofname"
    title varchar(100) not null, -- Lords: (The) Bishop / Lord / Earl / Viscount etc...
    constituency varchar(100) not null, -- Lords: NOT USED
    party varchar(100) not null, -- Lords: affiliation
    house enum('commons', 'lords') not null,

    -- these are inclusive, and measure days when the mp could vote
    entered_house date not null default '1000-01-01',
    left_house date not null default '9999-12-31',
    entered_reason enum('unknown', 'general_election', 'by_election', 'changed_party',
        'reinstated') not null default 'unknown',
    -- general_election has three types 1) unknown 2) MP didn't try to stand
    -- again (or was deselected, etc.) 3) MP did try to stand again (if successful
    -- they will appear in another record)
    left_reason enum('unknown', 'still_in_office', 
        'general_election', 'general_election_standing', 'general_election_notstanding',
        'changed_party', 'died', 'declared_void', 'resigned',
        'disqualified', 'became_peer') not null default 'unknown',

    person int,

    index(entered_house),
    index(left_house),
    index(person),
    index(house),
    unique(first_name, last_name, constituency, entered_house, left_house)
);

-- Has multiple entries for different spellings of each constituency
create table pw_constituency (
    cons_id int not null,
    name varchar(100) not null,
    main_name bool not null,

    -- these are inclusive, and measure days when the boundaries were active
    from_date date not null default '1000-01-01',
    to_date date not null default '9999-12-31',

    index(from_date),
    index(to_date),
    index(name),
    index(cons_id, name)
);

create table pw_division (
    division_id int not null primary key auto_increment,
    valid bool,

    division_date date not null,
    division_number int not null,
    house enum('commons', 'lords') not null,

    division_name text not null,
    source_url blob not null, -- exact source of division
    debate_url blob not null, -- start of subsection
    motion blob not null,
    notes blob not null,
    clock_time text,

    source_gid text not null, -- global identifier
    debate_gid text not null, -- global identifier
    
    index(division_date),
    index(division_number),
    index(house),
    unique(division_date, division_number)
);

create table pw_vote (
    division_id int not null,
    gidmp_id int not null,
    vote enum("aye", "no", "both", "tellaye", "tellno") not null,

    index(division_id),
    index(mp_id),
    index(vote),
    unique(division_id, mp_id, vote)
);

-- Ministerial offices
create table pw_moffice (
    moffice_id int not null primary key auto_increment,

    dept varchar(100) not null,
    position varchar(100) not null,

    -- these are inclusive, the person became a minister at some time
    -- on the start date, and finished on the end date
    from_date date not null default '1000-01-01',
    to_date date not null default '9999-12-31',

    person int,

    index(person)
);

-- Define a sort order for displaying votes
create table pw_vote_sortorder (
    vote enum("aye", "no", "both", "tellaye", "tellno") not null,
    position int not null
);
insert into pw_vote_sortorder(vote, position) values('aye', 10);
insert into pw_vote_sortorder(vote, position) values('no', 5);
insert into pw_vote_sortorder(vote, position) values('both', 1);
insert into pw_vote_sortorder(vote, position) values('tellaye', 10);
insert into pw_vote_sortorder(vote, position) values('tellno', 5);

-------------------------------------------------------------------------------
-- Dynamic tables
--   those which people using the website can alter 
--   prefixed dyn_ for dynamic

CREATE TABLE pw_dyn_user (
  user_id int(11) NOT NULL auto_increment,
  user_name text,
  real_name text,
  email text,
  password text,
  remote_addr text,
  confirm_hash text,
  confirm_return_url text,
  is_confirmed int(11) NOT NULL default '0',
  is_newsletter int(11) NOT NULL default '1',
  reg_date datetime default NULL,
  active_policy_id int,
  PRIMARY KEY  (user_id)
) TYPE=MyISAM;

create table pw_dyn_dreammp (
    dream_id int not null primary key auto_increment,
    name varchar(100) not null,
    user_id int not null,
    description blob not null,
    private bool not null,

    index(user_id),
    unique(dream_id, name, user_id)
);

create table pw_dyn_dreamvote (
    division_date date not null,
    division_number int not null,
    dream_id int not null,
    vote enum("aye", "no", "both", "aye3", "no3") not null,

    index(division_date),
    index(division_number),
    index(dream_id),
    unique(division_date, division_number, dream_id)
);

-- changes people have been making are stored here for debugging
create table pw_dyn_auditlog (
    auditlog_id int not null primary key auto_increment,
    user_id int not null,
    event_date datetime,
    event text,
    remote_addr text
);

-- for wiki text objects.  this is a transaction table, we only
-- insert rows into it, so we can show history.  when reading
-- from it, use the most recent row for a given key.
create table pw_dyn_wiki_motion (
    wiki_id int not null primary key auto_increment,
    -- name/id of object this is an edit of 
    division_date date not null,
    division_number int not null,
    house enum('commons', 'lords') not null,

    -- the new text that has change
    text_body text not null,

    -- who and when this changes was made
    user_id int not null, 
    edit_date datetime,

    index(division_date, division_number, house)
);

-- who each issue of newsletter has been sent to so far
create table pw_dyn_newsletters_sent (
    user_id int not null,
    newsletter_name varchar(100) not null,

    unique(user_id, newsletter_name)
);

-------------------------------------------------------------------------------
-- Cache tables
--   Those written to by the website itself during PHP requests are here.
--   There are also lots more cache tables made automatically by loader.pl,
--   which is run once a day.

-- information about one Dream MP
create table pw_cache_dreaminfo (
    dream_id int not null primary key,

    -- 0 - nothing is up to date
    -- 1 - calculation has been done
    cache_uptodate int NOT NULL,

    votes_count int not null,
    edited_motions_count int not null,
    consistency_with_mps float
);

-- information about a real MP for a particular Dream MP
-- e.g. Scores for how well they follow the Dream MP's whip.
create table pw_cache_dreamreal_distance (
    dream_id int not null,
    person int not null,

    -- number of votes same / different / MP absent
    nvotessame int,
    nvotessamestrong int,
    nvotesdiffer int,
    nvotesdifferstrong int,
    nvotesabsent int,
    nvotesabsentstrong int,

    distance_a float, -- use abstentions
    distance_b float, -- ignore abstentions

    index(dream_id),
    index(person),
    unique(dream_id, person)
);

-- Stores the most recent wiki item for this division
create table pw_cache_divwiki (
    division_date date not null,
    division_number int not null,
    house enum('commons', 'lords') not null,
    wiki_id int not null,
    unique(division_date, division_number, house)
);

-------------------------------------------------------------------------------
