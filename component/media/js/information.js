(() => {
  'use strict';

  const onReady = (callback) => {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', callback, { once: true });
    else callback();
  };

  onReady(() => {
    const data = document.getElementById('competitions-information-diagnostic-data');
    const copyButton = document.querySelector('[data-competitions-info-copy]');
    const downloadButton = document.querySelector('[data-competitions-info-download]');
    const feedback = document.querySelector('[data-competitions-info-feedback]');
    if (!data || !feedback) return;

    const text = () => data.textContent || '';
    const message = (key, fallback) => Joomla.Text?._(key) || fallback;
    const setFeedback = (value) => { feedback.textContent = value; };

    const fallbackCopy = (value) => {
      const area = document.createElement('textarea');
      area.value = value;
      area.setAttribute('readonly', '');
      area.style.position = 'fixed';
      area.style.opacity = '0';
      document.body.appendChild(area);
      area.select();
      const ok = document.execCommand('copy');
      area.remove();
      return ok;
    };

    copyButton?.addEventListener('click', async () => {
      try {
        if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(text());
        else if (!fallbackCopy(text())) throw new Error('copy failed');
        setFeedback(message('COM_XDECAROCOMPETITIONS_INFO_COPIED', 'Diagnostica copiata.'));
      } catch (error) {
        setFeedback(message('COM_XDECAROCOMPETITIONS_INFO_COPY_FAILED', 'Impossibile copiare la diagnostica.'));
      }
    });

    downloadButton?.addEventListener('click', () => {
      const version = downloadButton.dataset.version || 'unknown';
      const blob = new Blob([text() + '\n'], { type: 'text/plain;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = `competitions-diagnostics-${version}.txt`;
      document.body.appendChild(anchor);
      anchor.click();
      anchor.remove();
      URL.revokeObjectURL(url);
      setFeedback(message('COM_XDECAROCOMPETITIONS_INFO_DOWNLOADED', 'Diagnostica scaricata.'));
    });
  });
})();
