<?php $socials = $GLOBALS['config']['socials'] ?? []; ?>
<footer class="footer">
    <div class="footer-brand">
        <strong>LOS SANTOS ROLEPLAY VIETNAMESE</strong>
        <span>Heavy Roleplay Experience · Los Santos</span>
        <a href="<?= e(url('about.php')) ?>" style="display:inline-block;margin-top:8px;font-size:11px;color:#aeb5c1">GIỚI THIỆU LSRP →</a>
    </div>

    <div class="social-links" aria-label="Mạng xã hội">
        <a href="<?= e($socials['discord'] ?? '#') ?>" target="_blank" rel="noopener noreferrer" aria-label="Discord" title="Discord"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.5 5.3A17 17 0 0 0 15.4 4l-.5 1a15 15 0 0 0-5.8 0l-.5-1a17 17 0 0 0-4.1 1.3C1.9 9.9 1.2 14.4 1.6 18.8A17 17 0 0 0 6.7 21l1.2-1.7a10 10 0 0 1-1.9-.9l.5-.4a12.8 12.8 0 0 0 11 0l.5.4c-.6.3-1.2.6-1.9.9l1.2 1.7a17 17 0 0 0 5.1-2.2c.5-5.1-.8-9.5-2.9-13.5ZM8.2 16.1c-1.1 0-2-1-2-2.2s.9-2.2 2-2.2 2 1 2 2.2-.9 2.2-2 2.2Zm7.6 0c-1.1 0-2-1-2-2.2s.9-2.2 2-2.2 2 1 2 2.2-.9 2.2-2 2.2Z"/></svg></a>
        <a href="<?= e($socials['facebook'] ?? '#') ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.6 22v-9h3l.5-3.5h-3.5V7.3c0-1 .3-1.7 1.8-1.7h1.9V2.5c-.3 0-1.5-.1-2.8-.1-2.8 0-4.7 1.7-4.7 4.8v2.3H6.7V13h3.1v9h3.8Z"/></svg></a>
        <a href="<?= e($socials['youtube'] ?? '#') ?>" target="_blank" rel="noopener noreferrer" aria-label="YouTube" title="YouTube"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23 7.1a3 3 0 0 0-2.1-2.2C19 4.4 12 4.4 12 4.4s-7 0-8.9.5A3 3 0 0 0 1 7.1C.5 9 .5 12 .5 12s0 3 .5 4.9a3 3 0 0 0 2.1 2.2c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.2c.5-1.9.5-4.9.5-4.9s0-3-.5-4.9ZM9.7 15.4V8.6l6 3.4-6 3.4Z"/></svg></a>
    </div>

    <span class="footer-version">UCP V5 · <?= date('Y') ?></span>
</footer>
<script src="<?= e(url('public/js/app.js')) ?>"></script>
</body>
</html>
