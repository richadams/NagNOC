<?php
/**
 *	Naglite3 - Nagios Status Monitor
 *	Inspired by Naglite (http://www.monitoringexchange.org/inventory/Utilities/AddOn-Projects/Frontends/NagLite)
 *	and Naglite2 (http://laur.ie/blog/2010/03/naglite2-finally-released/)
 *
 *	@author		Steffen Zieger <me@saz.sh>
 *	@version	1.0
 *	@license	GPL
 *
 *  Modified by Rich Adams (http://richadams.me)
 */

////////////////////////////////////////////////////////////////////////////////////////////////////
// Configuration

// Set file path to your nagios status log
$status_file = "/var/cache/nagios3/status.dat";

// Default refresh time in seconds
$refresh = 10;

// Nothing to change below here
////////////////////////////////////////////////////////////////////////////////////////////////////

// Disable caching and set refresh interval
header("Pragma: no-cache");
if (!empty($_GET["refresh"]) && is_numeric($_GET["refresh"]))
{
	$refresh = $_GET["refresh"];
}
header("Refresh: " .$refresh);

////////////////////////////////////////////////////////////////////////////////////////////////////
// Functions

function duration($end)
{
	$DAY = 86400;
	$HOUR = 3600;

	$now     = time();
	$diff    = $now - $end;
	$days    = floor($diff / $DAY);
	$hours   = floor(($diff % $DAY) / $HOUR);
	$minutes = floor((($diff % $DAY) % $HOUR) / 60);
	$secs    = $diff % 60;
	return sprintf("%dd, %02d:%02d:%02d", $days, $hours, $minutes, $secs);
}

////////////////////////////////////////////////////////////////////////////////////////////////////
// Read the status file input

// Nagios Status Map
$nagios["host"]["OK"]          = 0;
$nagios["host"]["DOWN"]        = 1;
$nagios["host"]["UNREACHABLE"] = 2;
$nagios["host"] += array_keys($nagios["host"]);
$nagios["service"]["OK"]       = 0;
$nagios["service"]["WARNING"]  = 1;
$nagios["service"]["CRITICAL"] = 2;
$nagios["service"]["UNKNOWN"]  = 3;
$nagios["service"] += array_keys($nagios["service"]);

// Check to make sure the file is readable, break out with an error if not.
if (is_readable($status_file))
{
	$nagiosStatus = file($status_file);
}
else
{
    die("ERROR: Unable to open status file: ".$status_file);
}


$in     = false;
$type   = "unknown";
$status = array();
$host   = null;
for ($i = 0; $i < count($nagiosStatus); $i++)
{
	if (false === $in)
	{
		$pos = strpos($nagiosStatus[$i], "{");
		if (false !== $pos)
		{
			$in = true;
			$type = substr($nagiosStatus[$i], 0, $pos-1);
            if (!empty($status[$type]))
            {
    			$arrPos = count($status[$type]);
            }
            else
            {
                $arrPos = 0;
            }
			continue;
		}
	}
	else
	{
		$pos = strpos($nagiosStatus[$i], "}");
		if(false !== $pos)
		{
			$in = false;
			$type = "unknown";
			continue;
		}

		// Line with data found
		list($key, $value) = explode("=", trim($nagiosStatus[$i]), 2);
		if ("hoststatus" === $type)
		{
			if("host_name" === $key)
			{
				$host = $value;
			}
			$status[$type][$host][$key] = $value;
		} else
		{
			$status[$type][$arrPos][$key] = $value;
		}
	}
}

// Initialize some counter arrays
$counts = array();
$objs   = array();

// Populate the counters
foreach (array_keys($status) as $type)
{
	switch ($type)
	{
	    case "hoststatus":
		    $hosts = $status[$type];
		    foreach ($hosts as $host)
		    {
			    if ($host["problem_has_been_acknowledged"] == "1")
			    {
			        $counts['hosts']['ACKd']++;
			        $objs['hosts']['ACKd'][] = $host;
			    }
			    else if ($host["notifications_enabled"] == 0)
			    {
			        $counts['hosts']['NOTIFS']++;
			        $objs['hosts']['NOTIFS'][] = $host;
			    }
			    else if ($host["has_been_checked"] == 0)
			    {
			        $counts['hosts']['PENDING']++;
			        $objs['hosts']['PENDING'][] = $host;
			    }
			    else
			    {
				    switch ($host["current_state"])
				    {
					    case $nagios["host"]["OK"]:
					        $counts['hosts']['OK']++;
						    break;
					    case $nagios["host"]["DOWN"]:
					        $counts['hosts']['DOWN']++;
					        $objs['hosts']['DOWN'][] = $host;
						    break;
					    case $nagios["host"]["UNREACHABLE"]:
					        $counts['hosts']['UNREACHABLE']++;
					        $objs['hosts']['UNREACHABLE'][] = $host;
						    break;
				    }
			    }
		    }
		    break;

	    case "servicestatus":
		    $services = $status[$type];
		    foreach ($services as $service)
		    {
			    // Ignore all services if host state is not OK
			    $state = $status["hoststatus"][$service["host_name"]]["current_state"];
			    if ($nagios["host"]["OK"] != $state)
			    {
				    continue;
			    }
			    // Service is in warning level
			    if ($service["problem_has_been_acknowledged"] == "1")
			    {
			        $counts['services']['ACKd']++;
			        $objs['services']['ACKd'][] = $service;
			    }
			    else if ($service["notifications_enabled"] == "0")
			    {
			        $counts['services']['NOTIFS']++;
			        $objs['services']['NOTIFS'][] = $service;
			    }
			    else if ($service["has_been_checked"] == "0")
			    {
			        $counts['services']['PENDING']++;
			        $objs['services']['PENDING'][] = $service;
			    }
			    else
			    {
				    switch ($service["current_state"])
				    {
					    case $nagios["service"]["OK"]:
					        $counts['services']['OK']++;
						    break;
					    case $nagios["service"]["WARNING"]:
					        $counts['services']['WARNING']++;
		                    $objs['services']['WARNING'][] = $service;
						    break;
					    case $nagios["service"]["CRITICAL"]:
					        $counts['services']['CRITICAL']++;
                            $objs['services']['CRITICAL'][] = $service;
						    break;
					    case $nagios["service"]["UNKNOWN"]:
					        $counts['services']['UNKNOWN']++;
			                $objs['services']['UNKNOWN'][] = $service;
						    break;
				    }

				    if ($nagios["service"]["OK"] != $service["current_state"]) {
					    $servicesNOKList[] = $service;
				    }
			    }
		    }
		    break;
	}
}

// Work out overall status
$overall = "unknown";
if (!isset($counts['hosts']['DOWN'])
    && !isset($counts['services']['WARNING'])
    && !isset($counts['services']['CRITICAL'])
    && !isset($counts['services']['UNKNOWN']) )
{
    $overall = "ok";
}
else if (isset($counts['hosts']['DOWN'])
         || isset($counts['services']['CRITICAL']))
{
    $overall = "critical";
}
else {
    $overall = "warning";
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
    <title>Nagios Monitoring</title>
    <meta http-equiv=\"content-type\" content=\"text/html;charset=utf-8\" />

    <style>
    * {
        padding: 0px;
        margin: 0px;
    }

    html, body {
        height: 99%;
    }

    body {
        font-family: Verdana, Tahoma, Helvetica, Arial, sans-serif;
        font-size: 14px;
        background: #000;
    }

    #content {
        background: #000;
        height: 99.5%;
        margin: 10px;                                                                                                         
    }

    .section {
        background: #000;
        color: #fff;
        font-weight: bold;
        overflow: auto;
        min-height: 100px;
        padding: 10px;
    }

    h2.title {
        text-align: left;
        font-size: 1.4em;
        font-weight: bold;
        padding-bottom: 10px;
    }

    .stats {
        float: right;
        margin-top: -2em;
    }

    .stat {
        display: inline;
        padding: 5px;
        font-size: 1.2em;
        margin-left: 10px;
    }

    .state {
        padding: 3px;
        margin: 10px 0px 0px 0px;
        text-align: center;
        text-transform: uppercase;
        font-size: 1.2em;
    }

    table {
        width: 100%;
        margin-top: 10px;
        border: none;
        border-spacing: 0px;
        font-size: 1em;
    }

    table th {
        background-color: #d3d3d3;
        padding: 2px 0px;
        color: #000;
        border-bottom: 2px solid #000;
    }

    table td {
        padding: 2px 10px;
    }

    table td.hostname {
        background: #d3d3d3;
        color: #000;
    }

    table td.duration {
        text-align: right;
        white-space: nowrap;
    }

    table td.service {
        white-space: nowrap;
    }
    
    table td.state {
        font-size: 1em;
    }

    /* Colours */
    .up, .ok         { background: #008000; }
    .warning         { background: #ffff00; color: #000; }
    .critical, .down { background: #ff0000; }
    .unknown         { background: #00ffff; }
    .pending         { background: #488acf; }
    .notifs          { background: #69b3b3; color: #000; }
    .ackd            { background: #d3d3d3; }
    .unreachable     { background: #ff8040; }
    </style>
</head>
<body class="<?=$overall;?>">

    <div id="content">
        <div id="hosts" class="section">
            <h2 class="title">Host Status</h2>
            <div class="stats">
            <?
                foreach ($counts['hosts'] as $type => $count)
                    echo "<div class=\"stat ".strtolower($type)."\">".$count." ".$type."</div>";
            ?>
            </div>

            <?
            if (!isset($counts['hosts']['DOWN']))
            {
            ?>
            <div class="state up">All Monitored Hosts Up</div>
            <?
            }

            if (isset($counts['hosts']['UNREACHABLE'])
                || isset($counts['hosts']['ACKD'])
                || isset($counts['hosts']['PENDING'])
                || isset($counts['hosts']['NOTIFS'])
                || isset($counts['hosts']['DOWN']))
            {
            ?>
                <table>
                <tr>
                    <th>Host</th>
                    <th>Status</th>
                    <th>Duration</th>
                    <th>Status Info</th>
                </tr>
                <?
                foreach ($objs['hosts'] as $type => $host)
                {
                ?>
                <tr class="<?=strtolower($type);?>">
                    <td class="hostname"><?=$host['host_name'];?></td>
                    <td class="state"><?=$nagios['host'][$host['current_state']];?></td>
                    <td class="duration"><?=duration($host['last_state_change']);?></td>
                    <td><?=$host['plugin_output'];?></td>
                </tr>
                <?
                }
                ?>
                </table>
            <?
            } ?>
        </div>

        <div id="services" class="section">
            <h2 class="title">Service Status</h2>
            <div class="stats">
            <?
                foreach ($counts['services'] as $type => $count)
                    echo "<div class=\"stat ".strtolower($type)."\">".$count." ".$type."</div>";
            ?>
            </div>

            <?
            if (!isset($counts['services']['WARNING'])
                && !isset($counts['services']['CRITICAL'])
                && !isset($counts['services']['UNKNOWN']))
            {
            ?>
            <div class="state up">All Monitored Services OK</div>
            <?
            }

            if (isset($counts['services']['ACKd'])
                || isset($counts['services']['PENDING'])
                || isset($counts['services']['NOTIFS'])
                || isset($counts['services']['WARNING'])
                || isset($counts['services']['CRITICAL'])
                || isset($counts['services']['UNKNOWN']))
            {
            ?>
                <table>
                <tr>
                    <th>Host</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Duration</th>
                    <th>Attempts</th>
                    <th>Status Info</th>
                </tr>
                <?
                foreach ($objs['services'] as $type => $obj)
                {
                    foreach ($obj as $service)
                    {
                        $state = $nagios["service"][$service["current_state"]];
                    ?>
                        <tr class="<?=strtolower($type);?>">
                            <td class="hostname"><?=$service['host_name'];?></td>
                            <td class="service"><?=$service['service_description'];?></td>
                            <td class="state"><?=$state;?><? if ($service['current_attempt'] != $service['max_attempts']) { echo " (Soft)"; }?></td>
                            <td class="duration"><?=duration($service['last_state_change']);?></td>
                            <td><?=$service['current_attempt']."/".$service['max_attempts'];?></td>
                            <td><?=$service['plugin_output'];?></td>
                        </tr>
                    <?
                    }
                }
                ?>
                </table>
            <? } ?>
         </div>
    </div>
</body>
</html>
