async function vote(postId, type){
  const res = await fetch(`${BASE_URL}votes/vote.php`,{
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({postId, type})
  });
  const data = await res.json();
  if (!data.ok){
    if (data.error === 'AUTH'){
      window.location.href = `${BASE_URL}auth/login.php`;
    } else {
      alert('Voting failed');
    }
    return data;
  }

  // Update score text
  const scoreEl = document.querySelector(`[data-score-for="${postId}"]`);
  if (scoreEl) scoreEl.textContent = data.score;

  // Toggle active classes on buttons
  const upBtn = document.querySelector(`.btn-up[data-question-id="${postId}"]`);
  const downBtn = document.querySelector(`.btn-down[data-question-id="${postId}"]`);
  if (upBtn && downBtn){
    upBtn.classList.toggle('active', data.state === 'up');
    downBtn.classList.toggle('active', data.state === 'down');
  }

  // Persist state locally so it survives logout/navigation
  if (data.state === 'up' || data.state === 'down'){
    localStorage.setItem(`vote:q:${postId}`, data.state);
  } else {
    localStorage.removeItem(`vote:q:${postId}`);
  }
  return data;
}

// Attach click handlers if buttons exist (progressive enhancement)
document.addEventListener('click', (e)=>{
  const btn = e.target.closest('.btn-vote');
  if (!btn) return;
  e.preventDefault();
  const qid = btn.getAttribute('data-question-id');
  const type = btn.classList.contains('btn-up') ? 'up' : 'down';
  vote(qid, type);
});

// Hydrate vote buttons from localStorage on load
document.addEventListener('DOMContentLoaded', () => {
  const groups = new Map();
  document.querySelectorAll('.btn-vote').forEach(btn => {
    const id = btn.getAttribute('data-question-id');
    if (!groups.has(id)) groups.set(id, { up: null, down: null });
    const g = groups.get(id);
    if (btn.classList.contains('btn-up')) g.up = btn;
    if (btn.classList.contains('btn-down')) g.down = btn;
  });
  for (const [id, g] of groups.entries()){
    const saved = localStorage.getItem(`vote:q:${id}`);
    if (!g.up || !g.down) continue;
    g.up.classList.toggle('active', saved === 'up');
    g.down.classList.toggle('active', saved === 'down');
  }
});
