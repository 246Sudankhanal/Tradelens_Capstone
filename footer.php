    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
