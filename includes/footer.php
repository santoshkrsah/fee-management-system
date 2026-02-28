    </div> <!-- End Main Content -->

    <!-- Footer -->
    <?php if(isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php'): ?>
    <footer class="bg-light mt-5">
        <div class="d-flex justify-content-between align-items-center px-4 py-2" style="background-color: rgba(0, 0, 0, 0.05);">
            <span>© <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteSettings['school_name']); ?> | All rights reserved.</span>
            <small>Developed & Managed By - <a href="https://santoshkr.in/" target="_blank" class="developer-link" data-tooltip="Know about me">Santosh Kr</a></small>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Print Footer (hidden on screen, shown on print) -->
    <div class="print-footer" style="display:none;">
        Printed on: <?php echo date('d M Y, h:i A'); ?> &bull;
        <?php echo htmlspecialchars($siteSettings['school_name'] ?? ''); ?> &bull;
        Computer Generated Report
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="/assets/js/script.js"></script>
</body>
</html>
