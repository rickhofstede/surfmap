# *******************************
# SURFmap.pm [SURFmap]
# Author: Rick Hofstede <r.j.hofstede@utwente.nl>
# University of Twente, The Netherlands
#
# LICENSE TERMS: 3-clause BSD license (outlined in license.html)
# *******************************

package SURFmap;

use Sys::Syslog;
use strict;
use warnings;

our $VERSION = 135;
our $nfdump_version;

our %cmd_lookup = (
    'get_nfdump_version'    => \&get_nfdump_version,
);

sub get_nfdump_version {
    my $socket  = shift;
    my $opts    = shift;
    
    my %args;
    $args{'version'} = $nfdump_version;
    Nfcomm::socket_send_ok($socket, \%args);
}

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

#
# The Init function is called when the plugin is loaded. It's purpose is to give the plugin 
# the possibility to initialize itself. The plugin should return 1 for success or 0 for 
# failure. If the plugin fails to initialize, it's disabled and not used. Therefore, if
# you want to temporarily disable your plugin return 0 when Init is called.
#
sub Init {
    # nfdump version check
    $nfdump_version = nfdump_version_check;
    log_info "Detected nfdump v$nfdump_version";
    
	return 1;
}

1;
