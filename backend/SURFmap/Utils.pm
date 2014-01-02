################################################################
#
# Utils.pm [SURFmap]
# Author: Rick Hofstede <r.j.hofstede@utwente.nl>
# University of Twente, The Netherlands
#
# LICENSE TERMS: 3-clause BSD license (outlined in license.html)
#
################################################################

package SURFmap::Utils;

use strict;
use warnings;

use Exporter;
use Sys::Syslog;

our @ISA = qw(Exporter);
our @EXPORT = qw(
    log_debug
    log_error
    log_info
    nfdump_version_check
);

sub log_debug {
    syslog('info', "SURFmap: DEBUG - $_[0]");
    return $_[0];
}

sub log_error {
    syslog('info', "SURFmap: ERROR - $_[0]");
    return $_[0];
}

sub log_info {
    syslog('info', "SURFmap: $_[0]");
    return $_[0];
}

# Retrieves the nfdump version number.
# If the (first) argument is '1', then the potentially available patch level is returned as well (e.g., 1.6.10p1)
sub nfdump_version_check {
    my $include_patch_level = $_[0];
    my $cmd = "$NfConf::PREFIX/nfdump -V";
    my $full_version = qx($cmd);
    (my $version, my $patch_level) = $full_version =~ /nfdump: Version: ([\.0-9]+)((p[0-9]+)?)/;
    return ($include_patch_level) ? $version.$patch_level : $version;
}

1;
