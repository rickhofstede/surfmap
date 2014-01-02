################################################################
#
# RPC.pm [SURFmap]
# Author: Rick Hofstede <r.j.hofstede@utwente.nl>
# University of Twente, The Netherlands
#
# LICENSE TERMS: 3-clause BSD license (outlined in license.html)
#
################################################################

package SURFmap::RPC;

use strict;
use warnings;

use SURFmap::Utils;

use Exporter;

our @ISA = qw(Exporter);
our @EXPORT = qw(
    get_backend_version
    get_nfdump_version
    get_nfsen_profiledatadir
);

sub get_backend_version {
    my $socket  = shift;
    my $opts    = shift;
    my %args;
    $args{'version'} = $SURFmap::SURFMAP_VERSION;
    Nfcomm::socket_send_ok($socket, \%args);
}

sub get_nfdump_version {
    my $socket = shift;
    my $opts = shift;
    my %args;
    $args{'nfdump_version'} = nfdump_version_check(0);
    Nfcomm::socket_send_ok($socket, \%args);
}

sub get_nfsen_profiledatadir {
    my $socket = shift;
    my $opts = shift;
    my %args;
    $args{'nfsen_profiledatadir'} = $NfConf::PROFILEDATADIR;
    Nfcomm::socket_send_ok($socket, \%args);
}

1;
