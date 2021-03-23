<?php
// Always start this first
session_start();

if ( ! empty( $_POST ) ) {
    if ( isset( $_POST['username'] ) && isset( $_POST['pwd'] ) ) {
        // Getting submitted user data from database
        $con = new mysqli('localhost', '', '', ''); // enter db details here, to do: convert to vars from config json file
        $stmt = $con->prepare("SELECT * FROM users WHERE uname = ?");
        $stmt->bind_param('s', $_POST['username']);
        $stmt->execute();
        $result = $stmt->get_result();
    	$user = $result->fetch_object();
    		
    	// Verify user password and set $_SESSION
    	if ( password_verify( $_POST['pwd'], $user->password ) ) {
    		$_SESSION['user_id'] = $user->id;
    	}
    }
}
//$_SESSION['user_id'] = 5;
header("Location: /");
?>