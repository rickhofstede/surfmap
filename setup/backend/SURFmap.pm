# SURFmap back-end plugin
# Author: Rick Hofstede

package SURFmap;

use strict;
use NfConf;
use NfProfile;
use Sys::Syslog;

our $VERSION = 130;

my($ENABLE_GEOCODING) = 0;
my($LOG_DEBUG) = 1;
my($LOG_GEOCODING_ERRORS) = 1;
my($SURFMAP_PATH) = "$NfConf::FRONTEND_PLUGINDIR"."/SURFmap/";

#
# The Init function is called when the plugin is loaded. It's purpose is to give the plugin 
# the possibility to initialize itself. The plugin should return 1 for success or 0 for 
# failure. If the plugin fails to initialize, it's disabled and not used. Therefore, if
# you want to temporarily disable your plugin return 0 when Init is called.
#
sub Init {
	if ($LOG_DEBUG == 1) {
		syslog("info", "[SURFmap Back-end]: Init");
	}
	return 1;
}

#
# The Cleanup function is called, when nfsend terminates. It's purpose is to give the
# plugin the possibility to cleanup itself. It's return value is discard.
#
sub Cleanup {
	if ($LOG_DEBUG == 1) {
		syslog("info", "[SURFmap Back-end]: Cleanup");
	}
}

#
# Periodic data processing function
#       input:  hash reference including the items:
#               'profile'       profile name
#               'profilegroup'  profile group
#               'timeslot'      time of slot to process: Format yyyymmddHHMM e.g. 200503031200
#
sub run {
	if ($LOG_DEBUG == 1) {
		syslog("info", "[SURFmap Back-end]: Run");
	}	

	my $argref       = shift;
	my $profile      = $$argref{'profile'};
	my $profilegroup = $$argref{'profilegroup'};
	my $timeslot     = $$argref{'timeslot'};
	
	my %profileinfo  = NfProfile::ReadProfile($profile, $profilegroup);
	my $allsources   = join ':', keys %{$profileinfo{'channel'}};

	if ($ENABLE_GEOCODING == 1) {
		unless(-e $SURFMAP_PATH && -d $SURFMAP_PATH) {
			syslog("info", "[SURFmap Back-end]: The specified SURFmap directory (${SURFMAP_PATH}) could not be found!");
			return;
		}
		
		my $phpLocation = `which php`;
		unless($phpLocation) {
			syslog("info", "[SURFmap Back-end]: PHP CLI not found! Add PHP CLI to you PATH variable!");
			return;
		}
		
		my $phpOutput = `php ${SURFMAP_PATH}backend.php -p $profile -t $profileinfo{'type'} -s $allsources`;
		unless($phpOutput) {
			syslog("info", "[SURFmap Back-end]: Empty result from PHP CLI! Check the file permissions of backend.php");
			return;
		}
		
		if ($LOG_DEBUG == 1) {
			syslog("info", "[SURFmap Back-end]: Command: 'php ${SURFMAP_PATH}backend.php -p $profile -t $profileinfo{'type'} -s $allsources'");
		}
		
		my @splitResult = split(/##/, $phpOutput);
		foreach my $line (@splitResult) {
			if($line =~ m/^(successful|erroneous|skipped|total|flow records)/i) { # String starts with geocoding statistics
				# Check whether the geocoding completed with errors when successful geocodings == 0 and the total amount of geocodings > 0
				if ($line !~ m/successful/i || ($line =~ m/successful: 0/i && $line =~ m/skipped: 0/i && $line !~ m/total: 0/i)) {
					syslog("info", "[SURFmap Back-end]: Done (with errors)");
				} elsif ($line =~ m/successful: 0/i && $line !~ m/skipped: 0/i && $line !~ m/total: 0/i) {
					syslog("info", "[SURFmap Back-end]: Done (with errors: your machine might be blacklisted by Google GeoCoder)");
				} else {
					syslog("info", "[SURFmap Back-end]: Done");
				}
				
				if ($LOG_DEBUG == 1) {
					syslog("info", "[SURFmap Back-end]: Result: '$line'");
				}
			} elsif ($LOG_GEOCODING_ERRORS == 1) {
				syslog("info", "[SURFmap Back-end]: $line");
			}
		}
	} else {
		syslog("info", "[SURFmap Back-end]: Backend geocoding disabled");
	}
}
