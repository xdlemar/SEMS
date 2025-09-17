<?php
require __DIR__.'/../partials/auth.php';
if(!empty($_SESSION['uid'])){ header("Location: /sems/public/index.php"); exit; }

$err=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
  $u=$_POST['username']??''; $p=$_POST['password']??'';
  $q=$db->prepare("SELECT * FROM users WHERE username=?"); $q->execute([$u]);
  $user=$q->fetch(PDO::FETCH_ASSOC);
  if($user){
    $ok = password_verify($p,$user['password_hash']) || (sha1($p)===$user['password_hash']);
    if($ok){ $_SESSION['uid']=$user['id']; header("Location: /sems/public/index.php"); exit; }
  }
  $err="Invalid credentials";
}
$page_title='Login';
require __DIR__.'/../partials/layout_head.php';
?>

<style>
:root{
  --sems-top:#0f172a;       
  --sems-bottom:#0b1220;
  --accent:rgba(16,185,129,.18); 
  --logo-url:url("/sems/public/assets/logo.png");
}


.sems-bg{
  min-height:100vh;
  background: linear-gradient(180deg, var(--sems-top) 0%, var(--sems-bottom) 100%);
  position:relative; overflow:hidden;
}
.sems-bg::before{
  content:""; position:fixed; inset:0; z-index:0; pointer-events:none;
  background-image: var(--logo-url);
  background-repeat: repeat;
  background-size: 220px 220px;     
  opacity:.18; filter: blur(1px) brightness(1.1);
  animation: logosShift 40s linear infinite alternate;
}
.sems-bg::after{
  content:""; position:fixed; inset:-20%; z-index:1; pointer-events:none;
  background:
    radial-gradient(40vmax 30vmax at 20% 25%, rgba(255,255,255,.14), transparent 60%),
    radial-gradient(35vmax 28vmax at 80% 70%, var(--accent), transparent 65%),
    radial-gradient(28vmax 24vmax at 50% 85%, rgba(255,255,255,.10), transparent 70%);
  filter: blur(35px);
  animation: hvhGlow 25s ease-in-out infinite alternate;
}
@keyframes logosShift { 0%{background-position:0 0;} 100%{background-position:-200px -150px;} }
@keyframes hvhGlow    { 0%{transform:scale(1);} 100%{transform:scale(1.05) translate(-2%,-1%);} }

@media (max-width:640px){
  .sems-bg::before{ background-size:150px 150px; opacity:.14; } /* same as your mobile tweak */
}

.login-shell{ background:transparent; }
.login-left { background:#eef2f6; }     
.login-right{ background:#cbd5e1; }    
</style>
<div class="sems-bg flex flex-col items-center justify-start md:justify-center pt-4 md:pt-0 px-4">
  <div class="relative z-[3] w-full max-w-md md:max-w-3xl rounded-2xl overflow-hidden shadow-lg flex flex-col md:grid md:grid-cols-2 login-shell">
    <div class="flex items-center justify-center pt-2 pb-3 md:hidden">
      <div class="bg-white rounded-full flex items-center justify-center w-60 h-60 shadow-md ring-3 ring-gray-300">
        <img src="/sems/public/assets/logo.png" alt="SEMS logo" class="max-h-36 w-auto object-contain"/>
      </div>
    </div>
    <div class="login-left p-6 md:p-10 md:rounded-l-2xl rounded-t-2xl md:rounded-t-none">
      <div class="text-center mb-6 md:mb-8">
        <h1 class="text-2xl font-bold">Welcome back</h1>
        <p class="text-gray-600">Login to your SEMS account</p>
      </div>
      <?php if($err): ?>
        <div class="mb-4 text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded p-3"><?=htmlspecialchars($err)?></div>
      <?php endif; ?>
      <form method="post" class="space-y-5 md:space-y-6">
        <div>
          <label for="username" class="block text-sm font-medium">Username</label>
          <input id="username" name="username" required
                 class="mt-2 w-full rounded-md border-2 border-gray-400 px-3 py-2 focus:outline-none focus:border-gray-600"
                 placeholder="your.username" />
        </div>
        <div>
          <label for="password" class="block text-sm font-medium">Password</label>
          <input id="password" type="password" name="password" required
                 class="mt-2 w-full rounded-md border-2 border-gray-400 px-3 py-2 focus:outline-none focus:border-gray-600" />
        </div>
        <div class="text-right -mt-2">
          <a href="#" class="text-sm font-semibold hover:underline">Forgot your password?</a>
        </div>
        <button type="submit" class="w-full rounded-md bg-black py-2 text-white hover:bg-gray-800 transition">
          Sign in
        </button>

      </form>
    </div>
    <div class="hidden md:flex items-center justify-center login-right md:rounded-r-2xl p-8">
      <img src="/sems/public/assets/logo.png" alt="SEMS logo" class="max-h-80 w-auto object-contain"/>
    </div>
  </div>
  <p class="relative z-[3] mt-4 md:mt-6 text-center text-xs text-gray-300 max-w-md md:max-w-none">
    By clicking continue, you agree to our
    <a href="#" class="underline hover:text-white">Terms of Service</a> and
    <a href="#" class="underline hover:text-white">Privacy Policy</a>.
  </p>
</div>
<?php require __DIR__.'/../partials/layout_foot.php'; ?>
