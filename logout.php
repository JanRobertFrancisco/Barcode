<?php
session_start();

// If user confirms logout
if (isset($_POST['confirm_logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// If user cancels, go back to dashboard
if (isset($_POST['cancel_logout'])) {
    header("Location: dashboard.php");
    exit();
}

// Set username to admin for demonstration
$username = "admin";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logout Confirmation</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
      --secondary-gradient: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);
      --accent-color: #6c5ce7;
      --light-bg: #f8f9fa;
      --dark-text: #2d3436;
      --light-text: #636e72;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: var(--primary-gradient);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding: 20px;
      position: relative;
      overflow: hidden;
    }
    
    /* Animated background elements */
    .bg-bubbles {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
      overflow: hidden;
    }
    
    .bg-bubbles li {
      position: absolute;
      list-style: none;
      display: block;
      width: 40px;
      height: 40px;
      background-color: rgba(255, 255, 255, 0.15);
      bottom: -160px;
      border-radius: 50%;
      animation: square 25s infinite;
      transition-timing-function: linear;
    }
    
    .bg-bubbles li:nth-child(1) {
      left: 10%;
      animation-delay: 0s;
      width: 80px;
      height: 80px;
    }
    
    .bg-bubbles li:nth-child(2) {
      left: 20%;
      animation-delay: 2s;
      animation-duration: 17s;
      width: 60px;
      height: 60px;
    }
    
    .bg-bubbles li:nth-child(3) {
      left: 25%;
      animation-delay: 4s;
      width: 120px;
      height: 120px;
    }
    
    .bg-bubbles li:nth-child(4) {
      left: 40%;
      animation-delay: 0s;
      animation-duration: 22s;
      width: 50px;
      height: 50px;
    }
    
    .bg-bubbles li:nth-child(5) {
      left: 70%;
      animation-delay: 3s;
      width: 70px;
      height: 70px;
    }
    
    .bg-bubbles li:nth-child(6) {
      left: 80%;
      animation-delay: 2s;
      width: 100px;
      height: 100px;
    }
    
    .bg-bubbles li:nth-child(7) {
      left: 32%;
      animation-delay: 6s;
      width: 150px;
      height: 150px;
    }
    
    .bg-bubbles li:nth-child(8) {
      left: 55%;
      animation-delay: 8s;
      animation-duration: 18s;
      width: 40px;
      height: 40px;
    }
    
    .bg-bubbles li:nth-child(9) {
      left: 25%;
      animation-delay: 9s;
      animation-duration: 20s;
      width: 20px;
      height: 20px;
    }
    
    .bg-bubbles li:nth-child(10) {
      left: 90%;
      animation-delay: 11s;
      width: 90px;
      height: 90px;
    }
    
    @keyframes square {
      0% {
        transform: translateY(0) rotate(0deg);
        opacity: 1;
        border-radius: 50%;
      }
      100% {
        transform: translateY(-1000px) rotate(720deg);
        opacity: 0;
        border-radius: 50%;
      }
    }
    
    .logout-container {
      position: relative;
      z-index: 2;
      width: 100%;
      max-width: 480px;
    }
    
    .logout-box {
      background: rgba(255, 255, 255, 0.95);
      padding: 40px;
      border-radius: 20px;
      text-align: center;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
      backdrop-filter: blur(10px);
      animation: fadeIn 0.6s ease-out;
      border-top: 5px solid var(--accent-color);
      transform-style: preserve-3d;
      perspective: 1000px;
    }
    
    @keyframes fadeIn {
      from { 
        opacity: 0; 
        transform: translateY(-30px) rotateX(10deg);
      }
      to { 
        opacity: 1; 
        transform: translateY(0) rotateX(0);
      }
    }
    
    .logout-icon {
      font-size: 5rem;
      margin-bottom: 1.5rem;
      background: var(--secondary-gradient);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
      animation: pulse 1.5s infinite, float 3s ease-in-out infinite;
      display: inline-block;
    }
    
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
    
    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }
    
    .logout-box h3 {
      margin-bottom: 1rem;
      color: var(--dark-text);
      font-weight: 700;
      font-size: 1.8rem;
    }
    
    .logout-box p {
      color: var(--light-text);
      margin-bottom: 1.5rem;
      line-height: 1.6;
      font-size: 1.1rem;
    }
    
    .user-info {
      background-color: var(--light-bg);
      padding: 15px;
      border-radius: 12px;
      margin-bottom: 2rem;
      border-left: 4px solid var(--accent-color);
      display: flex;
      align-items: center;
      justify-content: center;
      animation: slideIn 0.5s ease-out;
    }
    
    @keyframes slideIn {
      from { 
        opacity: 0; 
        transform: translateX(-20px); 
      }
      to { 
        opacity: 1; 
        transform: translateX(0); 
      }
    }
    
    .user-info i {
      margin-right: 10px;
      font-size: 1.2rem;
      color: var(--accent-color);
    }
    
    .admin-badge {
      background: var(--primary-gradient);
      color: white;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      margin-left: 12px;
      font-weight: 600;
      box-shadow: 0 4px 8px rgba(108, 92, 231, 0.3);
    }
    
    .btn-logout {
      padding: 12px 30px;
      border-radius: 12px;
      font-weight: 600;
      transition: all 0.3s;
      border: none;
      font-size: 1rem;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      position: relative;
      overflow: hidden;
    }
    
    .btn-logout:after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 5px;
      height: 5px;
      background: rgba(255, 255, 255, 0.5);
      opacity: 0;
      border-radius: 100%;
      transform: scale(1, 1) translate(-50%);
      transform-origin: 50% 50%;
    }
    
    .btn-logout:focus:not(:active)::after {
      animation: ripple 1s ease-out;
    }
    
    @keyframes ripple {
      0% {
        transform: scale(0, 0);
        opacity: 0.5;
      }
      20% {
        transform: scale(20, 20);
        opacity: 0.3;
      }
      100% {
        transform: scale(50, 50);
        opacity: 0;
      }
    }
    
    .btn-danger {
      background: var(--secondary-gradient);
      color: white;
    }
    
    .btn-danger:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 15px rgba(255, 107, 107, 0.4);
    }
    
    .btn-secondary {
      background: #636e72;
      color: white;
    }
    
    .btn-secondary:hover {
      background: #2d3436;
      transform: translateY(-3px);
      box-shadow: 0 8px 15px rgba(45, 52, 54, 0.3);
    }
    
    .btn-space {
      margin: 0 10px;
    }
    
    .footer {
      margin-top: 2.5rem;
      color: #b2bec3;
      font-size: 0.9rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 576px) {
      .logout-box {
        padding: 30px 20px;
      }
      
      .btn-logout {
        padding: 10px 20px;
        display: block;
        width: 100%;
        margin-bottom: 15px;
      }
      
      .btn-space {
        margin: 0 0 15px 0;
      }
      
      .logout-icon {
        font-size: 4rem;
      }
      
      .logout-box h3 {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <!-- Animated background elements -->
  <ul class="bg-bubbles">
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
  </ul>
  
  <div class="logout-container">
    <div class="logout-box">
      <i class="fas fa-sign-out-alt logout-icon"></i>
      <h3>Confirm Logout</h3>
      <p>Are you sure you want to log out of your account?<br>You'll need to sign in again to access the dashboard.</p>
      
      <div class="user-info">
        <i class="fas fa-user-circle"></i> 
        <strong><?php echo htmlspecialchars($username); ?></strong>
        <span class="admin-badge">ADMIN</span>
      </div>
      
      <form method="post">
        <button type="submit" name="confirm_logout" class="btn btn-danger btn-logout btn-space">
          <i class="fas fa-sign-out-alt"></i> Yes, Logout
        </button>
        <button type="submit" name="cancel_logout" class="btn btn-secondary btn-logout btn-space">
          <i class="fas fa-times"></i> Cancel
        </button>
      </form>
      
      <div class="footer">
        <p>© <?php echo date('Y'); ?> Admin Portal. All rights reserved.</p>
      </div>
    </div>
  </div>

  <script>
    // Add keyboard navigation support
    document.addEventListener('keydown', function(event) {
      // ESC key cancels logout
      if (event.key === 'Escape') {
        window.location.href = 'dashboard.php';
      }
      
      // Enter key confirms logout (but only if not focused on a button)
      if (event.key === 'Enter' && !event.target.matches('button')) {
        document.querySelector('[name="confirm_logout"]').click();
      }
    });
    
    // Add button click animations
    document.querySelectorAll('.btn-logout').forEach(button => {
      button.addEventListener('click', function(e) {
        // Add a small animation on click
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
          this.style.transform = '';
        }, 200);
      });
    });
  </script>
</body>
</html>