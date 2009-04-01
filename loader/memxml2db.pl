#!/usr/bin/env perl -w
use strict;
use lib "PublicWhip";

# $Id: memxml2db.pl,v 1.14 2006/02/15 00:45:14 publicwhip Exp $

# Convert all-members.xml and all-lords.xml into the database format for Public
# Whip website

# The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

use XML::Twig;
use HTML::Entities;
use Text::Iconv;
use Data::Dumper;
my $iconv = new Text::Iconv('utf-8', 'iso-8859-1');

use PublicWhip::Config;
my $members_location = $PublicWhip::Config::members_location;

use PublicWhip::Error;
use PublicWhip::DB;
my $dbh = PublicWhip::DB::connect();

# Map from gid to the pw_mp.mp_id internal Public Whip ids, so we reload
# table with same new ids
our $gid_to_internal; 
my $last_mp_id = 0;
my $sth = PublicWhip::DB::query($dbh, "select mp_id, gid from pw_mp");
while ( my ($mp_id, $gid) = $sth->fetchrow_array() ) {
    $gid_to_internal->{$gid} = $mp_id;
    $last_mp_id = $mp_id if ($mp_id > $last_mp_id);
}

# We completely rebuild these two tables
$sth = PublicWhip::DB::query($dbh, "delete from pw_moffice");
$sth = PublicWhip::DB::query($dbh, "delete from pw_constituency");

my %membertoperson;
my $twig = XML::Twig->new(
    twig_handlers => { 
            'constituency' => \&loadcons, 
            'member' => \&loadmember, 
            'lord' => \&loadmember, 
            'person' => \&loadperson, 
            'moffice' => \&loadmoffice 
        }, 
    output_filter => 'safe');
$twig->parsefile("$members_location/constituencies.xml");
$twig->parsefile("$members_location/people.xml");
$twig->parsefile("$members_location/ministers.xml");
$twig->parsefile("$members_location/all-members.xml");
$twig->parsefile("$members_location/peers-ucl.xml");

# Delete things left that shouldn't be from this table
foreach my $gid (keys %$gid_to_internal) {
    $sth = PublicWhip::DB::query($dbh, "delete from pw_mp where gid = '$gid'");
}

sub loadperson
{
	my ($twig, $person) = @_;
    my $curperson = $person->att('id');

    for (my $office = $person->first_child('office'); $office;
        $office = $office->next_sibling('office'))
    {
        $membertoperson{$office->att('id')} = $curperson;
    }
}

sub loadmember
{ 
    my ($twig, $memb) = @_;

    my $house = $memb->att('house');
    my $gid = $memb->att('id');
    if ($gid =~ m#uk.org.publicwhip/member/#) {
        die if $house ne 'commons';
    } elsif ($gid =~ m#uk.org.publicwhip/lord/#) {
        die if $house ne 'lords';
    } else {
        die "unknown gid type $gid";
    }

    my $id = $gid;
    # /member and /lord ids are from same numberspace
    $id =~ s#uk.org.publicwhip/member/##;
    $id =~ s#uk.org.publicwhip/lord/##;

    my $person = $membertoperson{$memb->att('id')};
    if (!defined($person)) {
	    die "mp " . $id . " " . $memb->att('firstname') . " " . $memb->att('lastname') . " has no person" if $house eq 'commons';
	    die "lord " . $id . " " . $memb->att('lordofname') . " has no person" if $house eq 'lords';
	    die "unknown $id has no person";
    }
    $person =~ s#uk.org.publicwhip/person/##;

    my $party = $memb->att('party');
    my $title = $memb->att('title');
    my $firstname = $memb->att('firstname');
    my $lastname = $memb->att('lastname');
    my $constituency = $memb->att('constituency');
    my $fromdate = $memb->att('fromdate');
    my $todate = $memb->att('todate');
    my $fromwhy = $memb->att('fromwhy');
    my $towhy = $memb->att('towhy');
    if ($house eq 'lords') {
        if (!$memb->att('lordname')) {
            $title = "The " . $title;
        }
        $firstname = $memb->att('lordname');
        if ($memb->att('lordofname')) {
            $lastname = "of " . $memb->att('lordofname');
        } else {
            $lastname = "";
        }
        $constituency = "";
        $party = $memb->att('affiliation');
        $party = 'LDem' if ($party eq 'Dem');
        $fromwhy = 'unknown'; # TODO
        $towhy = 'unknown';
        if (!$todate) {
            $todate = "9999-12-31"; # TODO
        }
    }
    $party = 'Lab' if ($party eq 'Lab/Co-op');

    # We encode entities as e.g. &Ouml;, as otherwise non-ASCII characters
    # get lost somewhere between Perl, the database and the browser.
	# Attributes come out as UTF8, manually convert to latin-1.
    my $sth = PublicWhip::DB::query($dbh, "delete from pw_mp where gid = '$gid'");
    $sth = PublicWhip::DB::query($dbh, "insert into pw_mp
        (first_name, last_name, title, constituency, party, house,
        entered_house, left_house, entered_reason, left_reason, 
        mp_id, person, gid) values
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
        encode_entities($iconv->convert($firstname)), 
            encode_entities($iconv->convert($lastname)), 
            $title,
            encode_entities($iconv->convert($constituency)), 
            $party,
            $house,
        $fromdate, 
            $todate, 
            $fromwhy, 
            $towhy, 
        $id,
            $person,
            $gid,
        );

    # Store deleted
    delete $gid_to_internal->{$gid};
}

sub loadmoffice
{ 
	my ($twig, $moff) = @_;

    my $mofficeid = $moff->att('id');
    $mofficeid =~ s#uk.org.publicwhip/moffice/##;
    my $mpid = $moff->att('matchid');
    if (!$mpid) {
        return;
    }
    $mpid =~ s#uk.org.publicwhip/member/##;

    my $person = $membertoperson{$moff->att('matchid')};
    die "mp " . $mpid . " " . $moff->att('name') . " has no person" if !defined($person);
    $person =~ s#uk.org.publicwhip/person/##;

    # We encode entities as e.g. &Ouml;, as otherwise non-ASCII characters
    # get lost somewhere between Perl, the database and the browser.
    my $responsibility = $moff->att('responsibility') ? $moff->att('responsibility') : '';
    my $sth = PublicWhip::DB::query($dbh, "insert into pw_moffice 
        (moffice_id, dept, position, responsibility,
        from_date, to_date, person) values
        (?, ?, ?, ?, ?, ?, ?)", 
        $mofficeid,
        encode_entities($moff->att('dept')), 
        encode_entities($moff->att('position')), 
        encode_entities($responsibility), 
        $moff->att('fromdate'), 
        $moff->att('todate'), 
        $person,
        );
}

sub loadcons
{ 
	my ($twig, $cons) = @_;

    my $consid = $cons->att('id');
    $consid =~ s#uk.org.publicwhip/cons/##;

    my $main_name = 1;
    for (my $name = $cons->first_child('name'); $name; $name = $name->next_sibling('name')) {
	my $text = encode_entities($name->att('text'));
	
	# No idea why this isn't coming in from XML at unicode on sphinx
	# (it works on my laptop - Francis, 2005-10-12)
	$text =~ s/Ynys M&Atilde;&acute;n/Ynys M&ocirc;n/;

        # We encode entities as e.g. &Ouml;, as otherwise non-ASCII characters
        # get lost somewhere between Perl, the database and the browser.
        my $sth = PublicWhip::DB::query($dbh, "insert into pw_constituency
            (cons_id, name, main_name, from_date, to_date) values
            (?, ?, ?, ?, ?)", 
            $consid,
            $text, 
            $main_name,
            $cons->att('fromdate'), 
            $cons->att('todate'), 
            );
        $main_name = 0;
    }
}

