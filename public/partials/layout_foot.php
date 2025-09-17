  </div>
</div>

<script>
(function(){
  const sb = document.getElementById('sb');
  const overlay = document.getElementById('overlay');
  const btn = document.getElementById('toggle');
  const mq = window.matchMedia('(min-width: 992px)');
  const menu = document.getElementById('profileMenu');
  const STORAGE_KEY = 'sems_sb_open';

  let open = (localStorage.getItem(STORAGE_KEY) ?? '1') === '1';
  const isDesktop = () => mq.matches;

  function render(){
    if (isDesktop()){
      document.body.classList.toggle('with-sb', open);
      sb.classList.toggle('open', open);
      overlay.classList.remove('show');
    } else {
      document.body.classList.remove('with-sb');
      sb.classList.toggle('open', open);
      overlay.classList.toggle('show', open);
    }
  }
  render();

  btn?.addEventListener('click', () => {
    open = !open;
    if (isDesktop()) localStorage.setItem(STORAGE_KEY, open ? '1' : '0');
    render();
  });

  overlay.addEventListener('click', () => { open = false; render(); });
  mq.addEventListener('change', render);

  // Profile dropdown
  const btnMenu = menu?.querySelector('.menu-btn');
  btnMenu?.addEventListener('click', (e)=>{ e.stopPropagation(); menu.classList.toggle('open'); });
  document.addEventListener('click', ()=> menu?.classList.remove('open'));
})();
</script>
</body>
</html>
