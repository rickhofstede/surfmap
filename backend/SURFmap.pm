################################################################
#
# SURFmap.pm [SURFmap]
# Author: Rick Hofstede <r.j.hofstede@utwente.nl>
# University of Twente, The Netherlands
#
# LICENSE TERMS: 3-clause BSD license (outlined in license.html)
#
################################################################

package SURFmap;

use strict;
use warnings;

use SURFmap::RPC;
use SURFmap::Utils;

our $VERSION = 136;
our $SURFMAP_VERSION = "3.3";
our $nfdump_version;

use Exporter;
our @ISA = qw(Exporter);
our @EXPORT = qw(
    nfdump_version
);

our %cmd_lookup = (
    'get_backend_version'       => \&get_backend_version,
    'get_nfdump_version'        => \&get_nfdump_version,
    'get_nfsen_profiledatadir'  => \&get_nfsen_profiledatadir
);

#
# The Init function is called when the plugin is loaded. It's purpose is to give the plugin 
# the possibility to initialize itself. The plugin should return 1 for success or 0 for 
# failure. If the plugin fails to initialize, it's disabled and not used. Therefore, if
# you want to temporarily disable your plugin return 0 when Init is called.
#
sub Init {
    # nfdump version check
    $nfdump_version = nfdump_version_check();
    log_info("Detected nfdump v".$nfdump_version);
    
    return 1;
}

sub run {
    my $argref       = shift;
    my $profile      = $$argref{'profile'};
    my $profilegroup = $$argref{'profilegroup'};
    my $timeslot     = $$argref{'timeslot'};
}

sub Cleanup {
    log_info("Cleanup finished");
}

1;
