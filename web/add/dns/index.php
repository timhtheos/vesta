<?php
// Init
error_reporting(NULL);
ob_start();
session_start();
$TAB = 'DNS';
include($_SERVER['DOCUMENT_ROOT']."/inc/main.php");

// Header
include($_SERVER['DOCUMENT_ROOT'].'/templates/header.html');

// Panel
top_panel($user,$TAB);

if (!empty($_POST['ok'])) {
    // Check input
    if (empty($_POST['v_domain'])) $errors[] = __('domain');
    if (empty($_POST['v_ip'])) $errors[] = __('ip');

    // Protect input
    $v_domain = preg_replace("/^www./i", "", $_POST['v_domain']);
    $v_domain = escapeshellarg($v_domain);
    $v_domain = strtolower($v_domain);
    $v_ip = escapeshellarg($_POST['v_ip']);

    if (!empty($_POST['v_ns1'])) $v_ns1 = escapeshellarg($_POST['v_ns1']);
    if (!empty($_POST['v_ns2'])) $v_ns2 = escapeshellarg($_POST['v_ns2']);
    if (!empty($_POST['v_ns3'])) $v_ns3 = escapeshellarg($_POST['v_ns3']);
    if (!empty($_POST['v_ns4'])) $v_ns4 = escapeshellarg($_POST['v_ns4']);

    // Check for errors
    if (!empty($errors[0])) {
        foreach ($errors as $i => $error) {
            if ( $i == 0 ) {
                $error_msg = $error;
            } else {
                $error_msg = $error_msg.", ".$error;
            }
        }
        $_SESSION['error_msg'] = __('Field "%s" can not be blank.',$error_msg);
    } else {
        // Add DNS
        exec (VESTA_CMD."v-add-dns-domain ".$user." ".$v_domain." ".$v_ip." ".$v_ns1." ".$v_ns2." ".$v_ns3." ".$v_ns4, $output, $return_var);
        check_return_code($return_var,$output);
        unset($output);

        // Change Expiriation date
        if ((!empty($_POST['v_exp'])) && ($_POST['v_exp'] != date('Y-m-d', strtotime('+1 year')))) {
            $v_exp = escapeshellarg($_POST['v_exp']);
            exec (VESTA_CMD."v-change-dns-domain-exp ".$user." ".$v_domain." ".$v_exp, $output, $return_var);
            check_return_code($return_var,$output);
            unset($output);
        }

        // Change TTL
        if ((!empty($_POST['v_ttl'])) && ($_POST['v_ttl'] != '14400') && (empty($_SESSION['error_msg']))) {
            $v_ttl = escapeshellarg($_POST['v_ttl']);
            exec (VESTA_CMD."v-change-dns-domain-ttl ".$user." ".$v_domain." ".$v_ttl, $output, $return_var);
            check_return_code($return_var,$output);
            unset($output);
        }

        if (empty($_SESSION['error_msg'])) {
            $_SESSION['ok_msg'] = __('DNS_DOMAIN_CREATED_OK',$_POST[v_domain],$_POST[v_domain]);
            unset($v_domain);
        }
    }
}

// DNS Record
if (!empty($_POST['ok_rec'])) {
    // Check input
    if (empty($_POST['v_domain'])) $errors[] = 'domain';
    if (empty($_POST['v_rec'])) $errors[] = 'record';
    if (empty($_POST['v_type'])) $errors[] = 'type';
    if (empty($_POST['v_val'])) $errors[] = 'value';

    // Protect input
    $v_domain = escapeshellarg($_POST['v_domain']);
    $v_rec = escapeshellarg($_POST['v_rec']);
    $v_type = escapeshellarg($_POST['v_type']);
    $v_val = escapeshellarg($_POST['v_val']);
    $v_priority = escapeshellarg($_POST['v_priority']);

    // Check for errors
    if (!empty($errors[0])) {
        foreach ($errors as $i => $error) {
            if ( $i == 0 ) {
                $error_msg = $error;
            } else {
                $error_msg = $error_msg.", ".$error;
            }
        }
        $_SESSION['error_msg'] = __('Field "%s" can not be blank.',$error_msg);
    } else {
        // Add DNS Record
        exec (VESTA_CMD."v-add-dns-record ".$user." ".$v_domain." ".$v_rec." ".$v_type." ".$v_val." ".$v_priority, $output, $return_var);
        $v_type = $_POST['v_type'];
        check_return_code($return_var,$output);
        unset($output);
        if (empty($_SESSION['error_msg'])) {
            $_SESSION['ok_msg'] = __('DNS_RECORD_CREATED_OK',$_POST[v_rec],$_POST[v_domain]);
            unset($v_domain);
            unset($v_rec);
            unset($v_val);
            unset($v_priority);
        }
    }
}

if ((empty($_GET['domain'])) && (empty($_POST['domain'])))  {
    if ((empty($v_ns1)) && (empty($v_ns2))) {
        exec (VESTA_CMD."v-list-user-ns ".$user." json", $output, $return_var);
        $nameservers = json_decode(implode('', $output), true);
        $v_ns1 = $nameservers[0];
        $v_ns2 = $nameservers[1];
        $v_ns3 = $nameservers[2];
        $v_ns4 = $nameservers[3];
        unset($output);
    }
    if (empty($v_ttl)) $v_ttl = 14400;
    if (empty($v_exp)) $v_exp = date('Y-m-d', strtotime('+1 year'));
    if ($_SESSION['user'] == 'admin') {
        include($_SERVER['DOCUMENT_ROOT'].'/templates/admin/add_dns.html');
    } else {
        include($_SERVER['DOCUMENT_ROOT'].'/templates/user/add_dns.html');
    }
    unset($_SESSION['error_msg']);
    unset($_SESSION['ok_msg']);
} else {
    $v_domain = $_GET['domain'];
    include($_SERVER['DOCUMENT_ROOT'].'/templates/admin/add_dns_rec.html');
    unset($_SESSION['error_msg']);
    unset($_SESSION['ok_msg']);
}

// Footer
include($_SERVER['DOCUMENT_ROOT'].'/templates/footer.html');
