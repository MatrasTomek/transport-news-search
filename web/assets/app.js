(() => {
  'use strict';

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

  async function api(url, body) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify(body || {}),
    });
    if (res.status === 401) {
      window.location.href = 'login.php';
      throw new Error('Sesja wygasła.');
    }
    let data;
    try {
      data = await res.json();
    } catch {
      throw new Error('Serwer zwrócił nieczytelną odpowiedź (HTTP ' + res.status + '). Możliwe przekroczenie limitu czasu, spróbuj ponownie.');
    }
    if (!res.ok) throw new Error(data.error || 'Błąd HTTP ' + res.status);
    return data;
  }

  // ---------- wyszukiwanie ----------
  const progress = document.getElementById('progress');

  function showProgress(text, isError) {
    if (!progress) return;
    progress.hidden = false;
    progress.textContent = text;
    progress.classList.toggle('error', !!isError);
  }

  async function runSearch(searchId) {
    showProgress('Rozpoczynam…');
    for (;;) {
      let r;
      try {
        r = await api('api/search_step.php', { search_id: searchId });
      } catch (e) {
        showProgress(e.message + ' Odśwież stronę i kliknij „Wznów”, aby kontynuować.', true);
        return;
      }
      if (r.state === 'finished') {
        window.location.href = 'index.php?search=' + searchId;
        return;
      }
      if (r.state === 'busy') {
        showProgress('Krok „' + r.label + '” jest wykonywany w innym oknie. Czekam…');
        await sleep(5000);
        continue;
      }
      const steps = r.steps || [];
      const finished = steps.filter((s) => s.status === 'done' || s.status === 'error').length;
      const next = steps.find((s) => s.status !== 'done' && s.status !== 'error');
      let text = 'Krok ' + Math.min(finished + 1, steps.length) + '/' + steps.length + ': ' + (next ? next.label : 'kończenie') + '…';
      if (r.state === 'error') text += ' (krok „' + r.label + '” nie powiódł się: ' + r.message + ')';
      showProgress(text);
    }
  }

  const form = document.getElementById('search-form');
  if (form) {
    form.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      const button = form.querySelector('button[type="submit"]');
      button.disabled = true;
      try {
        const r = await api('api/search_start.php', { query: form.query.value, days: form.days.value });
        history.replaceState(null, '', 'index.php?search=' + r.search_id);
        await runSearch(r.search_id);
      } catch (e) {
        showProgress(e.message, true);
      } finally {
        button.disabled = false;
      }
    });
  }

  // ---------- post i grafika ----------
  function download(blob, name) {
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = name;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
  }

  function svgToPng(svg, w, h) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        canvas.getContext('2d').drawImage(img, 0, 0, w, h);
        canvas.toBlob((b) => (b ? resolve(b) : reject(new Error('Nie udało się utworzyć PNG.'))), 'image/png');
      };
      img.onerror = () => reject(new Error('Przeglądarka nie mogła odczytać grafiki SVG.'));
      img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
    });
  }

  async function copyText(textarea) {
    try {
      await navigator.clipboard.writeText(textarea.value);
    } catch {
      textarea.select();
      document.execCommand('copy');
    }
  }

  function setStatus(el, text, isError) {
    el.hidden = !text;
    el.textContent = text || '';
    el.classList.toggle('error', !!isError);
  }

  function initPanel(panel, topicId) {
    const q = (role) => panel.querySelector('[data-role="' + role + '"]');
    const state = { svg: null, width: 0, height: 0 };

    const updateCounter = () => {
      const n = [...q('output').value].length;
      const max = parseInt(q('max').value, 10) || 1000;
      q('counter').textContent = n + ' / ' + max + ' znaków';
      q('counter').classList.toggle('error', n > max);
    };

    async function generatePost() {
      const buttons = [q('generate'), q('generate-again')];
      buttons.forEach((b) => (b.disabled = true));
      setStatus(q('status'), 'Claude pisze post…');
      try {
        const r = await api('api/post.php', {
          topic_id: topicId, style: q('style').value, max_chars: q('max').value, hints: q('hints').value,
        });
        q('output').value = r.text;
        q('warning').textContent = r.over_limit ? 'Post przekracza limit znaków. Skróć go ręcznie.' : '';
        q('result').hidden = false;
        setStatus(q('status'), '');
        updateCounter();
      } catch (e) {
        setStatus(q('status'), e.message, true);
      } finally {
        buttons.forEach((b) => (b.disabled = false));
      }
    }

    async function generateImage() {
      const buttons = [q('image'), q('image-again')];
      buttons.forEach((b) => (b.disabled = true));
      setStatus(q('image-status'), 'Claude projektuje grafikę…');
      try {
        const r = await api('api/image.php', { topic_id: topicId, post: q('output').value, format: q('format').value });
        Object.assign(state, r);
        q('preview').src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(r.svg);
        q('image-result').hidden = false;
        setStatus(q('image-status'), '');
      } catch (e) {
        setStatus(q('image-status'), e.message, true);
      } finally {
        buttons.forEach((b) => (b.disabled = false));
      }
    }

    q('generate').addEventListener('click', generatePost);
    q('generate-again').addEventListener('click', generatePost);
    q('output').addEventListener('input', updateCounter);
    q('max').addEventListener('input', updateCounter);
    q('copy').addEventListener('click', async () => {
      await copyText(q('output'));
      setStatus(q('status'), 'Skopiowano do schowka.');
    });
    q('image').addEventListener('click', generateImage);
    q('image-again').addEventListener('click', generateImage);
    q('svg').addEventListener('click', () => download(new Blob([state.svg], { type: 'image/svg+xml' }), 'grafika-' + topicId + '.svg'));
    q('png').addEventListener('click', async () => {
      try {
        download(await svgToPng(state.svg, state.width, state.height), 'grafika-' + topicId + '.png');
      } catch (e) {
        setStatus(q('image-status'), e.message, true);
      }
    });
  }

  // ---------- przyciski (delegacja) ----------
  document.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('button');
    if (!btn) return;

    if (btn.dataset.resume) {
      btn.disabled = true;
      await runSearch(parseInt(btn.dataset.resume, 10));
      return;
    }
    if (btn.dataset.retryStep) {
      btn.disabled = true;
      try {
        const r = await api('api/search_retry.php', { step_id: parseInt(btn.dataset.retryStep, 10) });
        await runSearch(r.search_id);
      } catch (e) {
        showProgress(e.message, true);
        btn.disabled = false;
      }
      return;
    }
    if (btn.dataset.writePost) {
      const id = parseInt(btn.dataset.writePost, 10);
      const row = document.getElementById('post-row-' + id);
      const cell = row.querySelector('td');
      if (!cell.firstElementChild) {
        cell.appendChild(document.getElementById('post-panel-tpl').content.cloneNode(true));
        initPanel(cell.querySelector('.post-panel'), id);
      }
      row.hidden = !row.hidden;
    }
  });
})();
