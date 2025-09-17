<?php
require __DIR__.'/../partials/auth.php'; require_role(['ADMIN','HR','MANAGER','EMPLOYEE']);
$page_title='Kiosk'; require __DIR__.'/../partials/layout_head.php';
?>
<div class="min-h-screen bg-gradient-to-b from-charcoal to-slate-900 text-white flex flex-col">
  <header class="h-14 px-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <img src="/sems/public/assets/logo.png" class="w-8 h-8" alt="">
      <div class="font-semibold">SEMS Attendance Station</div>
    </div>
    <div class="text-xs opacity-80">Reader: <span id="reader">DEV001</span> · Camera: Browser</div>
  </header>

  <main class="flex-1 grid lg:grid-cols-2 gap-6 p-6">
   
    <section class="bg-white/5 rounded-2xl border border-white/10 p-4">
      <div class="text-sm opacity-80 mb-2 flex items-center gap-2">
        <span class="material-symbols-outlined">videocam</span> Live Preview
      </div>
      <video id="cam" autoplay playsinline class="w-full aspect-video rounded-xl bg-black object-cover"></video>
      <canvas id="snap" class="hidden"></canvas>
    
      <div id="status" class="mt-4 h-12 rounded-xl grid place-items-center text-lg font-semibold bg-slate-700/70">
        Ready to tap your RFID card…
      </div>
    </section>

   
    <section class="bg-white/5 rounded-2xl border border-white/10 p-4 space-y-4">
    
      <div class="rounded-xl bg-white/5 border border-white/10 p-4">
        <div class="flex items-center justify-between gap-4">
          <div>
            <div id="bigTime" class="font-mono text-4xl md:text-5xl tracking-tight">--:--</div>
            <div class="flex items-center gap-2 mt-1 text-sm opacity-80">
              <span id="ampm" class="px-2 py-0.5 rounded-full bg-white/10">--</span>
              <span id="clockDay">---</span>
              <span id="clockFullDate">---</span>
            </div>
          </div>
          <div class="w-24">
            <div id="seconds" class="text-2xl font-mono text-right">--</div>
            <div class="h-1 bg-white/10 rounded-full mt-1">
              <div id="secBar" class="h-1 bg-white/60 rounded-full" style="width:0%"></div>
            </div>
          </div>
        </div>
      </div>

      <div>
        <div class="text-sm opacity-80 mb-2 flex items-center gap-2">
          <span class="material-symbols-outlined">hourglass_top</span> Last Tap
        </div>
        <div id="lastTap" class="rounded-xl bg-white/10 p-4">No taps yet.</div>
      </div>
    </section>
  </main>
</div>

<script>
const DEVICE = 'DEV001';                 
const RESET_MS = 5000;                  
document.getElementById('reader').textContent = DEVICE;

const video    = document.getElementById('cam');
const canvas   = document.getElementById('snap');
const ctx      = canvas.getContext('2d');
const lastBox  = document.getElementById('lastTap');
const statusEl = document.getElementById('status');

(async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
    video.srcObject = stream;
  } catch(e) {
    setStatus('UNREG','Camera permission denied.');
  }
})();

function statusClass(kind){
  return 'mt-4 h-12 rounded-xl grid place-items-center text-lg font-semibold ' +
    (kind==='IN'    ? 'bg-emerald-600/80' :
     kind==='OUT'   ? 'bg-amber-600/80'  :
     kind==='UNREG' ? 'bg-rose-600/80'   :
                      'bg-slate-700/70'); // READY
}
let resetTimer = null;
function setStatus(kind, text){
  statusEl.className = statusClass(kind);
  statusEl.textContent = text;
  if (kind === 'IN' || kind === 'OUT' || kind === 'UNREG') {
    if (resetTimer) clearTimeout(resetTimer);
    resetTimer = setTimeout(() => {
      statusEl.className = statusClass('READY');
      statusEl.textContent = 'Ready to tap your RFID card…';
    }, RESET_MS);
  }
}

setStatus('READY', 'Ready to tap your RFID card…');

function drawFrame(){
  const w = video.videoWidth || 640, h = video.videoHeight || 480;
  canvas.width = w; canvas.height = h;
  ctx.drawImage(video, 0, 0, w, h);
}
async function uploadSnapshot(uid){
  try{
    drawFrame();
    const blob = await new Promise(res => canvas.toBlob(res, 'image/jpeg', 0.9));
    if(!blob) return;
    const fd = new FormData();
    fd.append('photo', blob, `cam_${Date.now()}.jpg`);
    fd.append('rfid_uid', uid);
    fd.append('device_id', DEVICE);
    await fetch('/sems/api/attendance_photo_upload.php', { method:'POST', body: fd, credentials:'same-origin' });
  }catch(_){}
}

const bigTime      = document.getElementById('bigTime');
const ampmEl       = document.getElementById('ampm');
const clockDay     = document.getElementById('clockDay');
const clockFull    = document.getElementById('clockFullDate');
const secText      = document.getElementById('seconds');
const secBar       = document.getElementById('secBar');

function tickClock(){
  const now  = new Date();
  const hours = now.getHours();
  const h12   = ((hours + 11) % 12) + 1;
  const mm    = String(now.getMinutes()).padStart(2,'0');
  const ss    = String(now.getSeconds()).padStart(2,'0');
  bigTime.textContent = `${String(h12).padStart(2,'0')}:${mm}`;
  ampmEl.textContent  = hours >= 12 ? 'PM' : 'AM';

  clockDay.textContent  = now.toLocaleDateString(undefined,{ weekday:'short' });
  clockFull.textContent = now.toLocaleDateString(undefined,{ month:'short', day:'numeric', year:'numeric' });

  secText.textContent = ss;
  const pct = (now.getSeconds() + now.getMilliseconds()/1000)/60 * 100;
  secBar.style.width = `${pct}%`;
}
setInterval(tickClock, 100); tickClock();

let lastTs = 0, lastId = 0;

function pill(text, cls){
  return `<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs ${cls} border border-white/10">${text}</span>`;
}

async function poll(){
  try{
    const url = `/sems/api/last_tap.php?device_id=${encodeURIComponent(DEVICE)}&since=${lastTs}&_=${Date.now()}`;
    const r = await fetch(url, {cache:'no-store', credentials:'same-origin'});
    if (r.ok) {
      const j = await r.json();

      if (j && j.rfid_uid && ((j.ts && j.ts > lastTs) || (j.id && j.id != lastId))) {
        lastTs = j.ts || lastTs;
        lastId = j.id || lastId;

        const when = j.ts ? new Date(j.ts*1000).toLocaleString() : '—';

        if (j.registered) {
         
          if (j.type === 'IN')  setStatus('IN',  'Time-IN recorded');
          else if (j.type === 'OUT') setStatus('OUT', 'Time-OUT recorded');
          else setStatus('READY','Registered card tapped');

          const typeBadge = j.type === 'IN'
            ? pill('IN','bg-emerald-700/40 text-emerald-200')
            : j.type === 'OUT'
              ? pill('OUT','bg-amber-700/40 text-amber-200')
              : '';

          lastBox.innerHTML = `
            <div class="flex items-center justify-between gap-3">
              <div>
                <div class="text-sm opacity-80">UID: ${j.rfid_uid}</div>
                <div class="text-lg font-semibold">${j.emp_no ? `${j.emp_no} · ` : ''}${j.name || '—'}</div>
                <div class="flex items-center gap-2 mt-2">
                  ${typeBadge}
                  ${pill('Registered','bg-emerald-700/40 text-emerald-200')}
                </div>
                <div class="text-xs opacity-70 mt-1">Seen @ ${when}</div>
              </div>
            </div>
          `;
          uploadSnapshot(j.rfid_uid);

        } else {
          setStatus('UNREG','Unregistered card');
          lastBox.innerHTML = `
            <div class="flex items-center justify-between gap-3">
              <div>
                <div class="text-sm opacity-80">UID: ${j.rfid_uid}</div>
                <div class="flex items-center gap-2 mt-2">
                  ${pill('Unregistered','bg-rose-700/40 text-rose-200')}
                </div>
                <div class="text-xs opacity-70 mt-1">Seen @ ${when}</div>
              </div>
            </div>
            <div class="text-xs text-rose-300 mt-2">Bind this UID in Admin → Register Card.</div>
          `;
        }
      }
    }
  }catch(_){/* ignore */}
  setTimeout(poll, 600);
}
poll();
</script>

<?php require __DIR__.'/../partials/layout_foot.php'; ?>
