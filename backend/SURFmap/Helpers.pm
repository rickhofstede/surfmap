# *******************************
# Helpers.pm [SURFmap]
# Author: Rick Hofstede <r.j.hofstede@utwente.nl>
# University of Twente, The Netherlands
#
# LICENSE TERMS: 3-clause BSD license (outlined in license.html)
# *******************************

package SURFmap::Helpers;

use strict;
use warnings;

use Exporter;
use Sys::Syslog;

our @ISA = qw(Exporter);
our @EXPORT = qw(
    log_info
    nfdump_version_check
    );

sub log_info {
    syslog('info', "SURFmap: $_[0]");

    return $_[0];
}

sub nfdump_version_check {
    my $cmd = "$NfConf::PREFIX/nfdump -V";
    my $full_version = qx($cmd);
    (my $version, my $patchlevel) = $full_version =~ /nfdump: Version: ([\.0-9]+)((p[0-9]+)?)/;
    
    return $version;
}

1;
