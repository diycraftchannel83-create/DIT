/**
 * SIAKAD STIT Darul Ilmi - Password change email notification
 *
 * Deploy as Web App:
 *   Execute as: Me
 *   Who has access: Anyone
 * Store the shared token in Project Settings > Script properties:
 *   SIAKAD_NOTIFY_TOKEN = <same long random token configured in backend/config/database.local.php>
 */
function doPost(e) {
  try {
    const props = PropertiesService.getScriptProperties();
    const expected = props.getProperty('SIAKAD_NOTIFY_TOKEN') || '';
    const body = JSON.parse((e && e.postData && e.postData.contents) || '{}');
    if (!expected || !body.token || !constantTimeEquals_(String(body.token), expected)) {
      return json_({ ok: false, error: 'unauthorized' });
    }

    const email = String(body.email || '').trim();
    const name = String(body.name || 'Pengguna SIAKAD').trim();
    const username = String(body.username || '').trim();
    const changedAt = String(body.changed_at || new Date().toISOString());
    if (!/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(email)) {
      return json_({ ok: false, error: 'invalid_email' });
    }
    if (MailApp.getRemainingDailyQuota() < 1) {
      return json_({ ok: false, error: 'daily_quota_exceeded' });
    }

    const subject = 'Pemberitahuan Perubahan Password Akun SIAKAD STIT Darul Ilmi';
    const plain = [
      'Yth. ' + name + ',',
      '',
      'Password akun SIAKAD STIT Darul Ilmi Anda telah berhasil diubah.',
      '',
      'Nama pengguna: ' + username,
      'Waktu perubahan: ' + changedAt,
      '',
      'Jika Anda tidak melakukan perubahan ini, segera hubungi Admin Akademik.',
      '',
      'Email ini dikirim otomatis. Mohon jangan membalas.'
    ].join('\\n');
    const safeName = escapeHtml_(name);
    const safeUsername = escapeHtml_(username);
    const safeTime = escapeHtml_(changedAt);
    const html = '<p>Yth. ' + safeName + ',</p>' +
      '<p>Password akun SIAKAD STIT Darul Ilmi Anda telah berhasil diubah.</p>' +
      '<ul><li>Nama pengguna: ' + safeUsername + '</li>' +
      '<li>Waktu perubahan: ' + safeTime + '</li></ul>' +
      '<p>Jika Anda tidak melakukan perubahan ini, segera hubungi Admin Akademik.</p>' +
      '<p><small>Email otomatis dari Sistem Informasi Akademik STIT Darul Ilmi Tasikmalaya.</small></p>';

    MailApp.sendEmail({ to: email, subject: subject, body: plain, htmlBody: html, name: 'SIAKAD STIT Darul Ilmi' });
    return json_({ ok: true });
  } catch (err) {
    console.error('Password notification error: ' + err);
    return json_({ ok: false, error: 'internal_error' });
  }
}

function json_(value) {
  return ContentService.createTextOutput(JSON.stringify(value))
    .setMimeType(ContentService.MimeType.JSON);
}

function constantTimeEquals_(a, b) {
  if (a.length !== b.length) return false;
  let diff = 0;
  for (let i = 0; i < a.length; i++) diff |= a.charCodeAt(i) ^ b.charCodeAt(i);
  return diff === 0;
}

function escapeHtml_(value) {
  return String(value).replace(/[&<>"']/g, function (c) {
    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
  });
}
