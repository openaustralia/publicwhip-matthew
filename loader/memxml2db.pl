#!/usr/bin/perl -w
use strict;
use lib "PublicWhip";

# $Id: memxml2db.pl,v 1.6 2005/03/04 01:30:17 theyworkforyou Exp $

# Convert all-members.xml into the database format for Public Whip website

# The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

use XML::Twig;
use HTML::Entities;
use Text::Iconv;
my $iconv = new Text::Iconv('utf-8', 'iso-8859-1');

use PublicWhip::Error;
use PublicWhip::DB;
my $dbh = PublicWhip::DB::connect();

my $sth = PublicWhip::DB::query($dbh, "delete from pw_mp");
$sth = PublicWhip::DB::query($dbh, "delete from pw_moffice");
my %membertoperson;

my $twig = XML::Twig->new(
    twig_handlers => { 'member' => \&loadmember, 'person' => \&loadperson, 
            'moffice' => \&loadmoffice }, 
    output_filter => 'safe');
$twig->parsefile("../members/people.xml");
$twig->parsefile("../members/ministers.xml");
$twig->parsefile("../members/all-members.xml");

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

    my $id = $memb->att('id');
    $id =~ s#uk.org.publicwhip/member/##;

    my $person = $membertoperson{$memb->att('id')};
    die "mp " . $id . " " . $memb->att('firstname') . " " . $memb->att('lastname') . " has no person" if !defined($person);
    $person =~ s#uk.org.publicwhip/person/##;

    my $party = $memb->att('party');
    $party = 'Lab' if ($party eq 'Lab/Co-op');

    # We encode entities as e.g. &Ouml;, as otherwise non-ASCII characters
    # get lost somewhere between Perl, the database and the browser.
    my $sth = PublicWhip::DB::query($dbh, "insert into pw_mp (first_name, last_name, title, constituency, party, 
        entered_house, left_house, entered_reason, left_reason, mp_id, person) values
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
	# Attributes come out as UTF8, manually convert to latin-1
        encode_entities($iconv->convert($memb->att('firstname'))), 
        encode_entities($iconv->convert($memb->att('lastname'))), 
        $memb->att('title'), 
        encode_entities($iconv->convert($memb->att('constituency'))), 
        $party,
        $memb->att('fromdate'), 
        $memb->att('todate'), 
        $memb->att('fromwhy'), 
        $memb->att('towhy'), 
        $id,
        $person,
        );
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
    my $sth = PublicWhip::DB::query($dbh, "insert into pw_moffice (moffice_id, dept, position, 
        from_date, to_date, person) values
        (?, ?, ?, ?, ?, ?)", 
        $mofficeid,
        encode_entities($moff->att('dept')), 
        encode_entities($moff->att('position')), 
        $moff->att('fromdate'), 
        $moff->att('todate'), 
        $person,
        );
}

