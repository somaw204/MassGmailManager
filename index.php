<?php
session_start();

// --- LOGIN CHECK ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// File to store accounts
define('ACCOUNTS_FILE', 'accounts.json');

// Helper: load accounts
function load_accounts() {
    if (!file_exists(ACCOUNTS_FILE)) {
        file_put_contents(ACCOUNTS_FILE, json_encode([]));
    }
    $data = file_get_contents(ACCOUNTS_FILE);
    return json_decode($data, true);
}

// Helper: save accounts
function save_accounts($accounts) {
    file_put_contents(ACCOUNTS_FILE, json_encode($accounts, JSON_PRETTY_PRINT));
}

// Handle form submissions and actions
$accounts = load_accounts();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_account'])) {
        // Add new account
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $twofa = trim($_POST['twofa']);
        $recovery = trim($_POST['recovery']);
        $sold = ($_POST['sold'] === 'yes') ? 'yes' : 'no';

        if ($email && $password) {
            $accounts[] = [
                'email' => $email,
                'password' => $password,
                'twofa' => $twofa,
                'recovery' => $recovery,
                'sold' => $sold
            ];
            save_accounts($accounts);
            $message = 'Account added successfully.';
        } else {
            $message = 'Email and password are required.';
        }
    } elseif (isset($_POST['delete_account'])) {
        // Delete account by index
        $index = intval($_POST['index']);
        if (isset($accounts[$index])) {
            array_splice($accounts, $index, 1);
            save_accounts($accounts);
            $message = 'Account deleted.';
        }
    } elseif (isset($_POST['import_accounts'])) {
        // Import accounts from uploaded TXT file
        if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === 0) {
            $fileContent = file_get_contents($_FILES['import_file']['tmp_name']);
            $lines = explode("\n", str_replace("\r", '', $fileContent));
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;
                $parts = explode('|', $line);
                $leftPart = trim($parts[0]);
                $sold = 'no';
                if (isset($parts[1])) {
                    if (stripos($parts[1], 'yes') !== false) {
                        $sold = 'yes';
                    }
                }
                $leftParts = explode(':', $leftPart);
                $email = $leftParts[0] ?? '';
                $password = $leftParts[1] ?? '';
                $twofa = $leftParts[2] ?? '';
                if ($email && $password) {
                    $accounts[] = [
                        'email' => $email,
                        'password' => $password,
                        'twofa' => $twofa,
                        'recovery' => '',
                        'sold' => $sold
                    ];
                }
            }
            save_accounts($accounts);
            $message = 'Accounts imported successfully.';
        } else {
            $message = 'Failed to upload file.';
        }
    } elseif (isset($_POST['export_accounts'])) {
        // Export accounts as TXT file
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="gmail_accounts.txt"');
        foreach ($accounts as $acc) {
            $line = $acc['email'] . ':' . $acc['password'];
            if (!empty($acc['twofa'])) {
                $line .= ':' . $acc['twofa'];
            }
            $line .= ' | Sold: ' . $acc['sold'];
            echo $line . "\n";
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Gmail Account Manager Dashboard</title>
  <style>
    body {
      background-color: #121212;
      color: #f0f0f0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 20px;
    }
    header {
      text-align: center;
      font-size: 28px;
      font-weight: bold;
      margin-bottom: 20px;
      border-bottom: 1px solid #333;
      padding-bottom: 10px;
      position: relative;
    }
    header a.logout {
      position: absolute;
      right: 20px;
      top: 10px;
      color: #ff4c4c;
      font-weight: normal;
      font-size: 16px;
      text-decoration: none;
      padding: 5px 10px;
      border: 1px solid #ff4c4c;
      border-radius: 5px;
      transition: background-color 0.3s;
    }
    header a.logout:hover {
      background-color: #ff4c4c;
      color: #121212;
    }
    form, table {
      background-color: #1f1f1f;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
      width: 100%;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
    }
    input, select, button {
      padding: 10px;
      margin-right: 10px;
      border-radius: 5px;
      border: none;
      font-size: 14px;
      color: #f0f0f0;
      background-color: #2b2b2b;
    }
    button {
      cursor: pointer;
      background-color: #3a3a3a;
    }
    button:hover {
      background-color: #555;
    }
    table {
      border-collapse: collapse;
      width: 100%;
    }
    th, td {
      padding: 12px 15px;
      border-bottom: 1px solid #333;
      text-align: left;
    }
    th {
      background-color: #2c2c2c;
    }
    .message {
      text-align: center;
      margin-bottom: 20px;
      color: #00ff00;
    }
  </style>
</head>
<body>

<header>
  📬 Gmail Account Manager Dashboard
  <a href="logout.php" class="logout">Logout</a>
</header>

<?php if ($message): ?>
  <div class="message"><?=htmlspecialchars($message)?></div>
<?php endif; ?>

<!-- Add Account Form -->
<form method="post" autocomplete="off">
  <input type="hidden" name="add_account" value="1" />
  <input type="text" name="email" placeholder="Gmail address" required />
  <input type="password" name="password" placeholder="Password" required />
  <input type="text" name="twofa" placeholder="2FA (optional)" />
  <input type="text" name="recovery" placeholder="Recovery Email (optional)" />
  <select name="sold">
    <option value="no">Not Sold</option>
    <option value="yes">Sold</option>
  </select>
  <button type="submit">Add Account</button>
</form>

<!-- Import Accounts Form -->
<form method="post" enctype="multipart/form-data" style="max-width: 900px; margin: 0 auto 20px auto;">
  <input type="file" name="import_file" accept=".txt" required />
  <button type="submit" name="import_accounts">📥 Import from TXT</button>
</form>

<!-- Export Accounts Form -->
<form method="post" style="max-width: 900px; margin: 0 auto 20px auto;">
  <button type="submit" name="export_accounts">📁 Export as TXT</button>
</form>

<!-- Accounts Table -->
<h2 style="text-align:center;">Saved Accounts (<?=count($accounts)?>)</h2>
<table>
  <thead>
    <tr>
      <th>Gmail</th>
      <th>Password</th>
      <th>2FA</th>
      <th>Recovery</th>
      <th>Sold</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($accounts as $idx => $acc): ?>
      <tr>
        <td><?=htmlspecialchars($acc['email'])?></td>
        <td><?=htmlspecialchars($acc['password'])?></td>
        <td><?=htmlspecialchars($acc['twofa'])?></td>
        <td><?=htmlspecialchars($acc['recovery'])?></td>
        <td><?=htmlspecialchars($acc['sold'])?></td>
        <td>
          <form method="post" style="display:inline;" onsubmit="return confirm('Delete this account?');">
            <input type="hidden" name="delete_account" value="1" />
            <input type="hidden" name="index" value="<?=$idx?>" />
            <button type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

</body>
</html>
