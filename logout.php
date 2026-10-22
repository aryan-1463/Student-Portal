<?php
// logout.php - Terminate Session & Clear All Auth State
session_start();

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();

// Expire all cookies
setcookie("studentHubRole", "", time() - 3600, "/");
setcookie("studentHubLoggedIn", "", time() - 3600, "/");
setcookie("studentHubUserName", "", time() - 3600, "/");
setcookie("studentHubUserId", "", time() - 3600, "/");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Logging Out...</title>
</head>
<body style="background:#0f172a; color:#f8fafc; font-family:sans-serif; display:flex; align-items:center; justify-content:center; height:100vh;">
  <p>Logging out securely... Please wait...</p>
  <script>
    localStorage.removeItem("studentHubLoggedIn");
    localStorage.removeItem("studentHubUser");
    sessionStorage.clear();
    document.cookie = "studentHubLoggedIn=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    document.cookie = "studentHubRole=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    document.cookie = "studentHubUserName=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    document.cookie = "studentHubUserId=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    window.location.replace("login.php?msg=logout");
  </script>
</body>
</html>
