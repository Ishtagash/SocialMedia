<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Successful</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --navy: #051650;
  --navy-mid: #0a2270;
  --lime: #ccff00;
  --lime-dim: #b8e800;
  --bg: #f0f3f9;
  --surface: #ffffff;
  --border: #e4e8f0;
  --text: #0d1b3e;
  --text-muted: #6b7a99;
  --green: #22c55e;
  --radius: 16px;
  --shadow: 0 4px 20px rgba(5, 22, 80, 0.08);
  --shadow-lg: 0 8px 32px rgba(5, 22, 80, 0.14);
}

*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: "DM Sans", "Segoe UI", Arial, sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

.success-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
  padding: 48px 40px;
  max-width: 460px;
  width: 100%;
  text-align: center;
  animation: fadeUp 0.5s ease both;
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

.success-icon-wrap {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(34, 197, 94, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 24px;
}

.success-icon-wrap i {
  font-size: 36px;
  color: var(--green);
}

.success-card h2 {
  font-size: 24px;
  font-weight: 700;
  color: var(--navy);
  margin-bottom: 10px;
}

.success-card p {
  font-size: 14px;
  color: var(--text-muted);
  line-height: 1.6;
  margin-bottom: 32px;
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 28px;
  border-radius: 10px;
  background: var(--navy);
  color: white;
  font-size: 14px;
  font-weight: 600;
  font-family: inherit;
  text-decoration: none;
  border: 2px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-back:hover {
  background: var(--lime);
  color: var(--navy);
  transform: translateY(-2px);
  box-shadow: 0 4px 14px rgba(204, 255, 0, 0.35);
}
</style>
</head>
<body>

<div class="success-card">
  <div class="success-icon-wrap">
    <i class="fa-solid fa-check"></i>
  </div>
  <h2>Payment Successful</h2>
  <p>Your payment has been received. Your document request is now being processed by the barangay staff.</p>
  <a href="residentrequest.php" class="btn-back">
    <i class="fa-solid fa-arrow-left"></i>
    Back to My Requests
  </a>
</div>

</body>
</html>