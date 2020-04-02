<?php

/*
 * Copyright 2005-2020 Centreon
 * Centreon is developed by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give CENTREON
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of CENTREON choice, provided that
 * CENTREON also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */

require_once "../require.php";
require_once $centreon_path . 'bootstrap.php';
require_once $centreon_path . 'www/class/centreon.class.php';
require_once $centreon_path . 'www/class/centreonSession.class.php';
require_once $centreon_path . 'www/class/centreonWidget.class.php';

CentreonSession::start(1);
if (!isset($_SESSION['centreon']) || !isset($_REQUEST['widgetId'])) {
    exit;
}
$centreon = $_SESSION['centreon'];
$widgetId = (int)$_REQUEST['widgetId'];

try {
    $db = $dependencyInjector['configuration_db'];
    $widgetObj = new CentreonWidget($centreon, $db);
    $preferences = $widgetObj->getWidgetPreferences($widgetId);

    // convert the autoRefresh value in minutes
    $autoRefresh = (int)$preferences['refresh_interval'] > 0
        ? (int)$preferences['refresh_interval'] * 60
        : 30 * 60;
} catch (Exception $e) {
    echo $e->getMessage() . "<br/>";
    exit;
}

$country = filter_var(($preferences['list'] ?? false), FILTER_SANITIZE_STRING);
if ($country === false) {
    throw new InvalidArgumentException('Bad argument format');
}

$availableApi = [
    'API_NINJA' => 'https://corona.lmao.ninja',
    'API_HEROKU' => 'https://coronavirus-19-api.herokuapp.com',
];


$apiUrl = $availableApi[$preferences['api_name']] . '/countries/' . $country;

// set curl options
$curlOptions = [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_IPRESOLVE => true,
];

// check for proxy configuration
$proxyEnabled = false;
if (
    !empty($centreon->optGen['proxy_url'])
    && !empty($centreon->optGen['proxy_port'])
) {
    if (
        !filter_var($centreon->optGen['proxy_url'], FILTER_VALIDATE_IP)
        && !filter_var($centreon->optGen['proxy_url'], FILTER_VALIDATE_DOMAIN)
    ) {
        echo 'The proxy IP is not valid. Please check it in the administration form';
        throw new InvalidArgumentException('Bad proxy URL');
    } elseif (false === filter_var($centreon->optGen['proxy_port'], FILTER_VALIDATE_INT)) {
        echo 'The proxy port is not valid. Please check it in the administration form';
        throw new InvalidArgumentException('Bad proxy port');
    }
    $proxyEnabled = true;
}

// get API data
try {
    $request = curl_init();
    curl_setopt_array($request, $curlOptions);

    if (true === $proxyEnabled) {
        // add proxy parameters to the curl request
        curl_setopt($request, CURLOPT_PROXY, $centreon->optGen['proxy_url']);
        curl_setopt($request, CURLOPT_PROXYPORT, $centreon->optGen['proxy_port']);
        curl_setopt(
            $request,
            CURLOPT_PROXYUSERPWD,
            $centreon->optGen['proxy_user'] . ":" . $centreon->optGen['proxy_password']
        );
    }

    $apiData = curl_exec($request);
    $error = curl_error($request);

    if (!$apiData) {
        $errorMessage = 'Failed Curl request.';
        if ($proxyEnabled === true) {
            $errorMessage .= '\nPlease check your proxy configuration in the administration form';
        }
        echo "<PRE>$errorMessage</PRE>";
        throw new Exception("$errorMessage Err => $error");
    }
} catch (ExitStatusException $e) {
    throw $e;
} finally {
    curl_close($request);
}

// get checked preferences
$chartData = array_filter($preferences ?? false, function ($valid) {
    return (1 === $valid % 2);
});

// check API data consistency with the filters
$chosenPref = 0;
$apiData = json_decode($apiData, true);
foreach ($chartData as $key => $value) {
    if (isset($apiData[$key])) {
        $chartData[$key] = $apiData[$key];
    }
    $chosenPref++;
}

// sort the data if required
if ("desc" === $preferences['sort']) {
    asort($chartData);
} elseif ("asc" === $preferences['sort']) {
    arsort($chartData);
}

// get the max value and split the retrieved data
$titles = array_keys($chartData);
$values = array_values($chartData);
$max = 0;
foreach ($values as $value) {
    $max = ($value > $max) ? $value : $max;
}

//convert values to percentage to be able to display the values properly
$ratio = 100 / $max;
foreach ($values as $key => $value) {
    $values[$key] = $value * $ratio;
}

$path = $centreon_path . "www/widgets/covid19/src/";

$template = new Smarty();
$template = initSmartyTplForPopup($path, $template, "./", $centreon_path);
$template->assign('widgetId', $widgetId);
$template->assign('preferences', $preferences);
$template->assign('autoRefresh', $autoRefresh);
$template->assign('data', $apiData);
$template->assign('titles', json_encode($titles));
$template->assign('values', json_encode($values));
$template->assign('ratio', $ratio);
$template->assign('userPalette', $preferences['palette']);
$template->assign('displayValues', $preferences['displayValues']);
$template->display('index.ihtml');
