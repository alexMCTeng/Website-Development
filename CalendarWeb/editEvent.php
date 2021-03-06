<?php

require 'database.php';

header("Content-Type: application/json");

ini_set("session.cookie_httponly", 1);
session_start();

if (!isset($_SESSION['username'])) {
    echo json_encode(
        array(
            "success" => false
        )
    );
    exit;
}

$username = $_SESSION['username'];
$user_id = $_SESSION['userID'];

$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str, true);

$event_id = $json_obj['id'];

// Security part 3, escape the XSS
$event_name = addslashes($json_obj['name']);

$start_datetime = strtotime($json_obj['start']);
$event_start = date('Y-m-d H:i:s', $start_datetime);
$end_datetime = strtotime($json_obj['end']);
$event_end = date('Y-m-d H:i:s', $end_datetime);
$event_type = $json_obj['type'];

// Security part 3, escape the XSS
$event_share1 = addslashes($json_obj['share1']);
$event_share2 = addslashes($json_obj['share2']);
$share1_id = 0;
$share2_id = 0;
$token_temp = $json_obj['token'];
if(!hash_equals($_SESSION['token'], $token_temp)){
	die("Request forgery detected");
}


// Security part 1, filter out the input
// this makes white space in event titles invalid, which is not desired
// if(!preg_match('/^[\w_\.\-]+$/', $event_name)){
//     echo json_encode(array(
//         "message" => "Invalid event name!"
//     ));
//     exit;
// }


//check if share1 and share2 are valid
if (strlen($event_share1) > 0) {
    $stmt = $mysqli->prepare("SELECT COUNT(*), userID FROM users WHERE username=?");
    if (!$stmt) {
        printf("Query Prep Failed: %s\n", $mysqli->error);
    }
    $stmt->bind_param('s', $event_share1);
    $stmt->execute();
    $stmt->bind_result($cnt1, $share1_id);
    $stmt->fetch();
    $stmt->close();

    if ($cnt1 == 0) {
        echo json_encode(array(
            "success" => false,
            "message" => "Username 1 does not exist!"
        ));
        exit;
    }
}
if (strlen($event_share2) > 0) {
    $stmt = $mysqli->prepare("SELECT COUNT(*), userID FROM users WHERE username=?");
    if (!$stmt) {
        printf("Query Prep Failed: %s\n", $mysqli->error);
    }
    $stmt->bind_param('s', $event_share2);
    $stmt->execute();
    $stmt->bind_result($cnt2, $share2_id);
    $stmt->fetch();
    $stmt->close();

    if ($cnt2 == 0) {
        echo json_encode(array(
            "success" => false,
            "message" => "Username 2 does not exist!"
        ));
        exit;
    }
}

$stmt = $mysqli -> prepare("UPDATE eventinfo set eventName = ?, startDateTime = ?, endDateTime = ?, 
                            eventTag = ?, sharedUserID1 = ?, sharedUserID2 = ? WHERE eventID = ?");
if (!$stmt) {
    printf("Query Prep Failed: %s\n", $mysqli->error);
}
$stmt->bind_param('ssssiii', $event_name, $event_start, $event_end, $event_type, $share1_id, $share2_id, $event_id);
$stmt->execute();
$stmt->close();

echo json_encode(array(
    // Security part 3, prevent XSS attact by escape the output
    "success" => true,
    "name" => htmlentities($event_name),
    "username" => htmlentities($username),
    "id" => htmlentities($event_id),
    "start" => htmlentities($event_start),
    "end" => htmlentities($event_end),
    "type" => htmlentities($event_type),
    "share1" => htmlentities($share1_id),
    "share2" => htmlentities($share2_id),
    "message" => "Event updated!"
));

exit;

?>