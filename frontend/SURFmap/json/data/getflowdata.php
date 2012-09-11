<?php
/******************************************************
 # getflowdata.php
 # Author:		Rick Hofstede <r.j.hofstede@utwente.nl>
 # University of Twente, The Netherlands
 #
 # LICENSE TERMS: 3-clause BSD license (outlined in license.html)
 *****************************************************/

require_once("../../config.php");
require_once("../../objects.php");

function ReportLog($message) {
	// dummy function to avoid PHP errors
}

session_start();
header("content-type: application/json");

if (isset($_POST['request'])) {
    $date1 = $_POST['request']['date1'];
    $date2 = $_POST['request']['date2'];
    $hours1 = $_POST['request']['hours1'];
    $hours2 = $_POST['request']['hours2'];
    $minutes1 = $_POST['request']['minutes1'];
    $minutes2 = $_POST['request']['minutes2'];
    $entry_count = $_POST['request']['entry_count'];

    $nfsen_filter = $_POST['request']['nfsen_filter'];
    $nfsen_option = $_POST['request']['nfsen_option'];
    $nfsen_profile = $_POST['request']['nfsen_profile'];
    $nfsen_profile_type = $_POST['request']['nfsen_profile_type'];
    $nfsen_selected_sources = $_POST['request']['nfsen_selected_sources'];
    $nfsen_stat_order = $_POST['request']['nfsen_stat_order'];

    $nfsen_html_dir = $_POST['request']['nfsen_html_dir'];
} else {
    $date1 = "20120908";
    $date2 = "20120908";
    $hours1 = "15";
    $hours2 = "15";
    $minutes1 = "10";
    $minutes2 = "10";
    $entry_count = 10;

    $nfsen_filter = "";
    $nfsen_option = 0;
    $nfsen_profile = "live";
    $nfsen_profile_type = "real";
    $nfsen_selected_sources = "utwente-core";
    $nfsen_stat_order = "bytes";

    $nfsen_html_dir = "../../..";
}

require_once($nfsen_html_dir."/conf.php");
require_once($nfsen_html_dir."/nfsenutil.php");

// Queries
$run = "-R nfcapd.".$date1.$hours1.$minutes1.":nfcapd.".$date2.$hours2.$minutes2
        ." -Nq -o \"fmt:%td;%sa;%da;%sp;%dp;%pr;%pkt;%byt;%fl\"";
if ($nfsen_option == 0) {
	$run .= " -c ".$entry_count;
} else {
	$run .= " -n ".$entry_count." -s record/".$nfsen_stat_order." -A proto,srcip,srcport,dstip,dstport";
}

if ($nfsen_option == 0 && $SORT_FLOWS_BY_START_TIME == 1) {
	$run .= " -m";
}

$cmd_opts['args'] = "-T $run";
$cmd_opts['profile'] = $nfsen_profile;
$cmd_opts['type'] = $nfsen_profile_type;
$cmd_opts['srcselector'] = implode(":", $nfsen_selected_sources);
$cmd_opts['filter'] = array($nfsen_filter);

$result = array();

if (!isset($_SESSION['profile'])) {
    $result['status'] = 9; // Filter error
    $result['flow_record_count'] = 0;
    echo json_encode($result);
    die();
}

// Execute NfSen query
$cmd_out = nfsend_query("run-nfdump", $cmd_opts);
nfsend_disconnect();
$result['query'] = "nfdump ".$cmd_out['arg'];
			
if (isset($cmd_out['nfdump']) && $cmd_out["exit"] > 0) {
	$result['status'] = 1; // Filter error
				
	if (count($cmd_out['nfdump']) > 0) {
        $result['error_message'] = $cmd_out['nfdump'][0];
					
		if ($sessionData->errorMessage == "Killed") {
            $result['status'] = 8; // Flow query execution killed
		}
	}

	$result['flow_record_count'] = 0;
    echo json_encode($result);
    die();
} else if (isset($_SESSION['error']) && isset($_SESSION['error'][0])) {
    $result['status'] = 6; // Profile error
    $result['error_message'] = $_SESSION['error'][0];
	$result['flow_record_count'] = 0;
    echo json_encode($result);
    die();			
} else if (!isset($cmd_out['nfdump']) || sizeof($cmd_out['nfdump']) == 1) {
    $result['status'] = 5; // No flow records error
	$result['flow_record_count'] = 0;
    echo json_encode($result);
    die();
}

$result['flow_record_count'] = sizeof($cmd_out['nfdump']);
$result['flow_data'] = array();
foreach ($cmd_out['nfdump'] as $line) {
	// Remove unused characters.
	for ($i = 0; $i < strlen($line); $i++) {
        if (ord(substr($line, $i, 1)) < 32) {
            $line = substr_replace($line, '', $i, 1);
        }
    }
    
    $line_array = explode(";", $line);
    
    $record = new FlowRecord();
    $record->duration = trim($line_array[0]);
    $record->ipv4_src = trim($line_array[1]);
    $record->ipv4_dst = trim($line_array[2]);
    $record->port_src = trim($line_array[3]);
    $record->port_dst = trim($line_array[4]);
    $record->protocol = trim($line_array[5]);
    $record->packets = trim($line_array[6]);
    $record->octets = trim($line_array[7]);
    $record->flows = trim($line_array[8]);
    
    array_push($result['flow_data'], $record);
}

$result['status'] = 0;
echo json_encode($result);
die();

?>