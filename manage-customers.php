# manage-customers.php

```php
<?php
include 'db.php';
$customers = mysqli_query($conn, "SELECT * FROM customers ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head><title>Manage Customers</title></head>
<body>
<h1>Manage Customers</h1>
<table border="1" cellpadding="10">
<tr><th>ID</th><th>Email</th><th>Created</th><th>Action</th></tr>
<?php while($row = mysqli_fetch_assoc($customers)): ?>
<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo htmlspecialchars($row['email']); ?></td>
<td><?php echo $row['created_at']; ?></td>
<td><a href="delete-customer.php?id=<?php echo $row['id']; ?>">Delete</a></td>
</tr>
<?php endwhile; ?>
</table>
</body>
</html>
```

# manage-shops.php

```php
<?php
include 'db.php';
$shops = mysqli_query($conn, "SELECT * FROM shops ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head><title>Manage Shops</title></head>
<body>
<h1>Manage Shops</h1>
<table border="1" cellpadding="10">
<tr><th>ID</th><th>Email</th><th>Created</th><th>Action</th></tr>
<?php while($row = mysqli_fetch_assoc($shops)): ?>
<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo htmlspecialchars($row['email']); ?></td>
<td><?php echo $row['created_at']; ?></td>
<td><a href="delete-shop.php?id=<?php echo $row['id']; ?>">Delete</a></td>
</tr>
<?php endwhile; ?>
</table>
</body>
</html>
```

# reports.php

```php
<?php
include 'db.php';
$totalCustomers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM customers"))['total'];
$totalShops = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM shops"))['total'];
$totalContacts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM contacts"))['total'];
?>
<!DOCTYPE html>
<html>
<head><title>Reports</title></head>
<body>
<h1>System Reports</h1>
<p>Total Customers: <?php echo $totalCustomers; ?></p>
<p>Total Shops: <?php echo $totalShops; ?></p>
<p>Total Contacts: <?php echo $totalContacts; ?></p>
</body>
</html>
```

# settings.php

```php
<!DOCTYPE html>
<html>
<head><title>Settings</title></head>
<body>
<h1>System Settings</h1>
<form>
<label>Site Name</label><br>
<input type="text" value="Shop Finder"><br><br>
<label>Admin Email</label><br>
<input type="email" value="admin@shopfinder.com"><br><br>
<button type="submit">Save Settings</button>
</form>
</body>
</html>
```

# logout.php

```php
<?php
session_start();
session_unset();
session_destroy();
header('Location: index.php');
exit();
?>
```
