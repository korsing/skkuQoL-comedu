<?php

require($_SERVER["DOCUMENT_ROOT"].'/../dbconfig.php');
/*
dbconfig.php file outside of webroot has the following variables setup globally

$dbservername;
$dbusername;
$dbpassword;
$dbname;

*/

//set response header
header('Content-type:application/json;charset=utf-8');
$response='NULL';

//note : $_POST indexes using "name" attributes from the form.
$name = $_POST['name'];
$password = $_POST['password'];

//simple sanitization
if(strlen($name)>13 || strlen($name)<2){
    $response = "ERROR : 이름은 2자 이상 10자 이내로 입력해주세요.";
    echo json_encode(["response" => $response]);
    die();
}
else if(strlen($password)>15 || strlen($password)<3){
    $response = "ERROR : 비밀번호는 3자 이상 10자 이내로 입력해주세요.";
    echo json_encode(["response" => $response]);
    die();
}

//recaptcha
if(isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])){
			//your site secret key
			$secret = '6LfOBR8UAAAAAKnuenB-aiDeUIBajjuAGiVel5li';
    	    //get verify response data
    	    $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$_POST['g-recaptcha-response']);
			$responseData = json_decode($verifyResponse);
			if(!($responseData->success)){
				$response="인증에 실패하였습니다.";
                echo json_encode(["response" => $response]);
                die();
			}
		}
else{
	$response="인증 후 시도해주세요!";
    echo json_encode(["response" => $response]);
    die();
}
//recaptcha passed without issues

// Create connection
$conn = new mysqli($dbservername, $dbusername, $dbpassword, $dbname);
// Check connection
if ($conn->connect_error) {
    $response = $conn->connect_error;
    echo json_encode(["response" => $response]);
    die();
}
//set characterset that php thinks our database is using
if (!$conn->set_charset("utf8")) {
	$response = $conn->error." 현재 문자 세트 : ".$conn->character_set_name();
    echo json_encode(["response" => $response]);
    $conn->close();
    die();
}



//find all reserve_srl to delete
$sql = "SELECT * FROM admin.qol_seminarreservelist WHERE studentname='$name' and password='$password';";

//run mysql query
$result = $conn->query($sql);

$todelete=array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        //$jsonresponse[]=array("reserve_srl" => $row["reserve_srl"], "purpose" => $row["purpose"]);
        //same as above. varname[]=~~ adds element to array, not overwrite it.
        $todelete[]=$row;
    }
}
else{
    $response = "일치하는 예약내역이 없습니다.";
    echo json_encode(["response" => $response]);
    $conn->close();
    die();
}

//normal execution

$sql = '';
$result = TRUE;
$count = 0; // Var to count # of successful deletions
for($i=0; $i<count($todelete); ++$i){
    $sql="DELETE FROM admin.qol_seminarreservelist WHERE reserve_srl=".($todelete[$i]['reserve_srl']).";";
    if($conn->query($sql) === FALSE){
        $result = FALSE;
        # Upon any error, stop repetition and send Error Msg
        break;
    }
    // Upon successful deletion, increment # of success
    $count++;
}
if($result === TRUE){
    $response = $count."건의 예약이 모두 취소되었습니다!";
}
else{
    $response = "ERROR : DB 연산에 문제가 생겼습니다!"."총 ".count($todelete)."건 중 ".$count."건의 예약만이 취소되었습니다.";
}


echo json_encode(["response" => $response]);

//close connection
$conn->close();
?>