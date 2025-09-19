<?php
require __DIR__.'/../partials/auth.php';
require_role(['ADMIN','HR']);

$me = current_user($db);
$isAdmin = ($me['role'] === 'ADMIN');

$page_title = 'Employee Portal Accounts';
require __DIR__.'/../partials/layout_head.php';
require __DIR__.'/../partials/layout_nav.php';

// Only EMPLOYEE portal accounts
$q = $db->query("
  SELECT u.id, u.username, u.employee_id,
         COALESCE(CONCAT(e.emp_no,' · ',e.fname,' ',e.lname),'—') AS person
  FROM users u
  LEFT JOIN employees e ON e.id = u.employee_id
  WHERE u.role = 'EMPLOYEE'
  ORDER BY u.id DESC
");
$rows = $q->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="max-w-6xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold flex items-center gap-2">
      <span class="material-symbols-outlined">badge</span>
      Employee Portal Accounts
    </h1>
    <a href="/sems/public/hr/user_form.php?role=EMPLOYEE" class="btn">
      Add Employee Account
    </a>
  </div>

  <div class="card overflow-x-auto">
    <table class="table text-sm">
      <thead>
        <tr>
          <th>Username</th>
          <th>Linked Employee</th>
          <th style="width:140px">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['username']) ?></td>
          <td><?= htmlspecialchars($r['person']) ?></td>
          <td class="flex gap-2">
            <a class="btn" href="/sems/public/hr/user_form.php?id=<?= (int)$r['id'] ?>">
              <span class="material-symbols-outlined">edit</span>
            </a>
            <form method="post" action="/sems/public/admin/user_delete.php"
                  onsubmit="return confirm('Delete this employee portal account?');">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-danger" title="Delete">
                <span class="material-symbols-outlined">delete</span>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr><td colspan="3" class="text-center text-slate-500 py-6">No employee portal accounts yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p class="mt-3 text-xs text-slate-500">
    This view is limited to <strong>Employee</strong> portal accounts. For all accounts (Admin/HR/Manager/Employee), go to
    <em>Admin → Users</em>.
  </p>
</div>
<?php require __DIR__.'/../partials/layout_foot.php'; ?>
