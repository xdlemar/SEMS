  </div>
</div>

<script>
(function(){
  const sb = document.getElementById('sb');
  const overlay = document.getElementById('overlay');
  const btn = document.getElementById('toggle');
  const mq = window.matchMedia('(min-width: 992px)');
  const STORAGE_KEY = 'sems_sb_open';

  let open = (localStorage.getItem(STORAGE_KEY) ?? '1') === '1';
  const isDesktop = () => mq.matches;

  function hideOverlayHard(){
    overlay.classList.remove('show');
    overlay.style.display = 'none';
    overlay.style.pointerEvents = 'none';
  }
  function showOverlay(){
    overlay.classList.add('show');
    overlay.style.display = 'block';
    overlay.style.pointerEvents = 'auto';
  }

  function render(){
    if (isDesktop()){
      document.body.classList.toggle('with-sb', open);
      sb.classList.toggle('open', open);
      hideOverlayHard();                 // <- never show overlay on desktop
    } else {
      document.body.classList.remove('with-sb');
      sb.classList.toggle('open', open);
      if (open) showOverlay(); else hideOverlayHard();
    }
  }

  render();

  btn?.addEventListener('click', () => {
    open = !open;
    if (isDesktop()) localStorage.setItem(STORAGE_KEY, open ? '1' : '0');
    render();                            // <- final authority on overlay state
  });

  overlay.addEventListener('click', () => { open = false; render(); });
  mq.addEventListener('change', render);
})();
</script>
</body>
</html>
