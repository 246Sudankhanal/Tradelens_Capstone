    </main>
</div>

<!-- Sign out confirmation -->
<div class="modal-backdrop" id="logout-modal">
    <div class="modal logout-confirm-modal">
        <div class="logout-confirm-icon">
            <i class="fa-solid fa-right-from-bracket"></i>
        </div>
        <h3 class="logout-confirm-title">Sign out of TradeLens?</h3>
        <p class="logout-confirm-text">You will need to sign in again to view your journal and analytics.</p>
        <div class="logout-confirm-actions">
            <button type="button" class="btn btn-outline" onclick="closeLogoutModal()">Stay signed in</button>
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-signout-confirm">
                <i class="fa-solid fa-right-from-bracket"></i> Sign out
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>window.TRADELENS_BASE = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>/js/main.js"></script>
<?php if (isset($extraJs)): ?>
<script><?= $extraJs ?></script>
<?php endif; ?>

<?php
require_once __DIR__ . '/../config/ai.php';
include __DIR__ . '/chat_widget.php';
?>
</body>
</html>
