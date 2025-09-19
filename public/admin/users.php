<?php
require __DIR__.'/../partials/auth.php'; require_role(['ADMIN','HR']);
$page_title='Users'; require __DIR__.'/../partials/layout_head.php'; require __DIR__.'/../partials/layout_nav.php';

$q=$db->query("SELECT u.id,u.username,u.role,u.employee_id,
                      COALESCE(CONCAT(e.emp_no,' · ',e.fname,' ',e.lname),'—') AS person
               FROM users u LEFT JOIN employees e ON e.id=u.employee_id
               ORDER BY u.id DESC");
$rows=$q->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="max-w-6xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold flex items-center gap-2"><span class="material-symbols-outlined">manage_accounts</span> Users</h1>
    <a href="/sems/public/admin/user_form.php" class="btn">Add User</a>
  </div>
  <div class="card overflow-x-auto">
    <table class="table text-sm">
      <thead><tr><th>Username</th><th>Role</th><th>Linked Employee</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['username']) ?></td>
          <td><?= htmlspecialchars($r['role']) ?></td>
          <td><?= htmlspecialchars($r['person']) ?></td>
          <td class="flex gap-2">
            <a class="btn" href="/sems/public/admin/user_form.php?id=<?= (int)$r['id'] ?>"><span class="material-symbols-outlined">edit</span></a>
            <form method="post" action="/sems/public/admin/user_delete.php" onsubmit="return confirm('Delete this user?');">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-danger" title="Delete"><span class="material-symbols-outlined">delete</span></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__.'/../partials/layout_foot.php'; ?>
